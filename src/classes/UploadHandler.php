<?php
namespace App\Backend;

class UploadHandler
{
    protected $dir_upload;
    protected $dir_chunks;
    protected $logger;
    protected $db;

	/**
     * Construct class from data array
     * @param $uploaddir - Target directory for uploads
     * @param $chunkdir - Temp directory for upload chunks
     * @param $logger - Reference to monologger.
     * @param $logger - Reference to database.
     */
    public function __construct($uploaddir, $chunkdir, $logger, $db) {
        $this->dir_upload = $uploaddir;
        $this->dir_chunks = $chunkdir;
        $this->logger = $logger;
        $this->db = $db;
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
    public function handleChunk(\Slim\Http\Request $request, \Slim\Http\Response $response) {
        $dir_chunks = $this->dir_chunks;
        $dir_upload = $this->dir_upload;

        /* Validate the chunk directory and target directory are writable */
        if (!is_dir($dir_chunks) || !is_writable($dir_chunks)) {
            $this->logger->addInfo("UploadHandler: " . "Error: " . $dir_chunks . "not writable" . PHP_EOL);

            return $response->withStatus(500); // we dun goofed
        }

        if (!is_dir($dir_upload) || !is_writable($dir_upload)) {
            $this->logger->addInfo("UploadHandler: " . "Error: " . $dir_upload . "not writable" . PHP_EOL);

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

            $mediainfo = [
                'title' => $file['name'],
                'filename' => $file['name']
            ];
            $broadcast = $this->createBroadcast($request, $mediainfo);

            $message = [
                "type" => "success",
                "name" => $file['name'],
                "filetype" => $file['type'],
                "error" => $file['error'],
                "size" => $file['size'],
                "fullpath" => $dest
            ];

            $this->logger->addInfo("UploadHandler: " . "Saving file: " . $file['name'] . PHP_EOL);
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
     * @return bool - True if a database entry was successful
     */
    private function createBroadcast(\Slim\Http\Request $request, array $mediainfo)
    {
        /* Get UserID of file uploader */
        $uid = UserSessionHandler::getId($request);
        if($uid < 0) // This should not be possible
        {
            $this->logger->addInfo("UploadHandler: " . "Warning: Invalid user uploaded file" . PHP_EOL);
            return;
        }

        /* Create a broadcast entity */
        $broadcast_data = [
            'user_id' => $uid,
            'title' => $mediainfo['title'],
            'filename' => $mediainfo['filename'],
            'length' => 0,
            'visibility' => 'public'
        ];
        $broadcast = new BroadcastEntity($broadcast_data);

        /* Serialize entity to database via mapper */
        if($broadcast)
        {
            $bmapper = new BroadcastMapper($this->db);
            if($bmapper)
            {
                $bmapper->save($broadcast);
                return true;
            }
        }
    }
}

?>