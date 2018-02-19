<?php
namespace App\Backend;

class UploadHandler
{
    protected $dir_upload;
    protected $dir_chunks;
    protected $logger;

	/**
     * Construct class from data array
     * @param $uploaddir - Target directory for uploads
     * @param $chunkdir - Temp directory for upload chunks
     * @param $logger - Reference to monologger.
     */
    public function __construct($uploaddir, $chunkdir, $logger) {
        $this->dir_upload = $uploaddir;
        $this->dir_chunks = $chunkdir;
        $this->logger = $logger;
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
}

?>