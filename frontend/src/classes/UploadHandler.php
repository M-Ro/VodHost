<?php
namespace VodHost;

use PhpAmqpLib\Message\AMQPMessage;

class UploadHandler
{
    protected $dir_upload;
    protected $dir_chunks;
    protected $logger;
    protected $em;
    protected $mq;

    /**
     * Construct class from data array
     * @param $uploaddir - Target directory for uploads
     * @param $chunkdir - Temp directory for upload chunks
     * @param $logger - Reference to monologger.
     * @param $em - Reference to entity mapper
     * @param $mq - Reference to message/job queue
     */
    public function __construct($uploaddir, $chunkdir, $logger, $em, $mq)
    {
        $this->dir_upload = $uploaddir;
        $this->dir_chunks = $chunkdir;
        $this->logger = $logger;
        $this->em = $em;
        $this->mq = $mq;
    }

    /**
     * Saves a chunk uploaded by a client or finalizes the file
     * on final chunk.
     *
     * @param Request $request - HTTP Request. Contains cookie data to map upload to user
     * @param Response $response - Response to client
     *
     * @return Response - Returns the response after being modified
     */
    public function handleChunk(\Slim\Http\Request $request, \Slim\Http\Response $response)
    {
        $dir_chunks = $this->dir_chunks;
        $dir_upload = $this->dir_upload;

        /* Validate the chunk directory and target directory are writable */
        if (!is_dir($dir_chunks) || !is_writable($dir_chunks)) {
            $this->logger->critical("UploadHandler: " . $dir_chunks . " not writable" . PHP_EOL);

            return $response->withStatus(500); // we dun goofed
        }

        if (!is_dir($dir_upload) || !is_writable($dir_upload)) {
            $this->logger->critical("UploadHandler: " . $dir_upload . " not writable" . PHP_EOL);

            return $response->withStatus(500); // we dun goofed
        }

        /* Handle incoming upload chunk */
        $req = new \Flow\Request();
        $config = new \Flow\Config(['tempDir' => $dir_chunks ]);

        $dest = $dir_upload . DIRECTORY_SEPARATOR . $req->getFileName();

        $message = [
          'type' => 'error',
          'message' =>'no data'
        ];

        if (\Flow\Basic::save($dest, $config, $req)) {
            /* File has been finalized, map a broadcast and create final response */
            $file = $req->getFile();

            $data = $request->getParsedBody();

            $mediainfo = [
                'title' => $data['title'],
                'desc' => $data['desc'],
                'vis' => $data['vis'],
                'filename' => $file['name']
            ];
            $broadcast = $this->createBroadcast($request, $mediainfo);

            // Create a work task to process this upload
            $this->createTask($broadcast);

            $this->logger->addInfo("UploadHandler: Stored: " . $file['name'] . " id: " . $broadcast->getId() . PHP_EOL);

            /* Return success response to client */
            $message = [
                "type" => "success",
                "name" => $file['name'],
                "filetype" => $file['type'],
                "error" => $file['error'],
                "size" => $file['size'],
                "fullpath" => $dest
            ];
        } else {
            // Do nothing for now, file has not finished uploading
        }

        return $response->withJson($message, 200);
    }

    /**
     * Creates a broadcast database entry to represent an uploaded file.
     *
     * @param Request $request - HTTP Request. Contains cookie data to map upload to user
     * @param array $mediainfo - Contains file information we store in the broadcast
     *
     * @return Entity\BroadcastEntity - The BroadcastEntity that was inserted
     */
    private function createBroadcast(\Slim\Http\Request $request, array $mediainfo)
    {
        /* Get UserID of file uploader */
        $uid = UserSessionHandler::getId($request);
        if ($uid < 0) { // This should not be possible
            $this->logger->notice("UploadHandler: " . "Warning: Invalid user uploaded file" . PHP_EOL);
            return;
        }

        $bmapper = new EntityMapper\BroadcastMapper($this->em);
        
        // Convert 'public' 'private' visibility to bool true/false
        $vis = false;
        if ($mediainfo['vis'] == 'Public') {
            $vis = true;
        }

        /* Create a broadcast entity */
        $broadcast_data = [
            'id' => $bmapper->generateUniqueID(),
            'user_id' => $uid,
            'title' => $mediainfo['title'],
            'filename' => $mediainfo['filename'],
            'description' => $mediainfo['desc'],
            'state' => 'processing',
            'length' => 0,
            'visibility' => $vis
        ];
        $broadcast = new Entity\BroadcastEntity($broadcast_data);

        /* Serialize entity to database via mapper */
        if ($broadcast) {
            if ($bmapper) {
                $bmapper->save($broadcast);
                return $broadcast;
            }
        }
    }

    /**
     * Creates a task in the processing queue for this upload
     *
     * @param Entity\BroadcastEntity $broadcast - The broadcast entity created for this upload
     */
    private function createTask(Entity\BroadcastEntity $broadcast)
    {
        $task_data = [
            'broadcastid' => $broadcast->getId()
        ];

        $msg = new AMQPMessage(
            json_encode($task_data),
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

        $this->mq->basic_publish($msg, '', 'vprocessing');
    }
}
