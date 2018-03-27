<?php

namespace VodHost\TaskHandler;

use VodHost\Storage;
use VodHost\Processing;

/**
 * Worker class for the processing of uploaded video streams.
 */
class ProcessVideoHandler extends TaskHandler
{
    private $storage;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->storage = Storage\StorageEngine::BuildStorageEngine(
            $this->config['storage'], $this->log
        );
    }

    /**
     * Processes tasks from the video processing queue.
     * Responsible for generating thumbnails and transmuxing uploaded content if required.
     */
    public function run()
    {
        if(!$this->storage) {
            $this->log->error("Failed to build storage engine" . PHP_EOL);
            return;
        }

        /* Callback function everytime we receive a task from the queue */
        $callback = function ($msg) {
            $this->log->debug("Received task for processing: " . $msg->body . PHP_EOL);

            $data = json_decode($msg->body, true);

            $id = $data['broadcastid'];

            /* Retrieve information from the frontend about the job */
            $broadcast_details = $this->api->getBroadcastInfo($id);
            if (!$broadcast_details) {
                $this->log->error("No broadcast details: " . $id . PHP_EOL);
                return;
            }

            $broadcast_details = json_decode($broadcast_details, true);

            /* Fetch the file from the processing queue using curl */

            $file_loc = 'temp/' . $id . '/' . $broadcast_details['filename'];
            $dest = $broadcast_details['filename'];

            $ret = $this->storage->get($file_loc, $dest);

            if (!$ret) {
                $this->log->error("Could not fetch video for processing: " . $file_loc . PHP_EOL);
                return;
            }

            $this->log->debug("Pulled $file_loc from storage" . PHP_EOL);

            $vprocessor = new Processing\VProcessor($dest);

            $v_setup = [
                'width' => '320',
                'height' => '180',
                'thumbcount' => 6,
                'target' => $this->config['processed_directory'] . DIRECTORY_SEPARATOR . $data['broadcastid'] . DIRECTORY_SEPARATOR,
                'output_filename' => $data['broadcastid'] . ".mp4"
            ];

            if (!mkdir($v_setup['target'], 0750, true)) {
                $this->log->critical("Could not create processing directory: " . $v_setup['target'] . PHP_EOL);
                return;
            }

            /* Start processing */
            /* Transmux if required */
            $vprocessor->transmuxToMP4($v_setup);
            $this->log->debug("Transmuxed content to MP4 for id " . $data['broadcastid'] . PHP_EOL);

            $vprocessor = new Processing\VProcessor($v_setup['target'] . $v_setup['output_filename']);

            /* We generate the thumbnails *after* transmuxing as ffmpeg can't seek some filetypes */
            $vprocessor->generateThumbnailSet($v_setup);
            $this->log->debug("Generated thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            $vprocessor->scaleThumbnails($v_setup);
            $this->log->debug("Scaled thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            /* Upload the processed content */
            $this->pushToStorage($v_setup, $data['broadcastid']);

            // Acknowledge job processed
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

            $outputfilepath = $v_setup['target'] . $v_setup['output_filename'];

            $broadcast_metadata = [
                'state' => 'processed',
                'length' => $vprocessor->getVideoLength($outputfilepath),
                'filesize' => filesize($outputfilepath)
            ];

            $this->api->modifyBroadcast($data['broadcastid'], json_encode($broadcast_metadata));

            /* Delete the temporary unprocessed file from S3 */
            $this->storage->delete($file_loc);
            $this->log->debug("Deleted $file_loc " . PHP_EOL);

            /* Cleanup local files we created */
            $this->cleanupWorkspace($v_setup['target'], $dest);
        };

        // Inform AMQP which job queue we consume from
        $this->channel->basic_consume('vprocessing', '', false, false, false, false, $callback);

        /* Start processing jobs */
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    private function pushToStorage($settings, $id)
    {
        /* First, upload the thumbnails we generated */
        for ($i=0; $i<$settings['thumbcount']; $i++) {
            $keyname = "processed/thumb/" . $id . '/' . "thumb_$i.jpg";
            $path = $settings['target'] . "thumb_$i.jpg";
            $this->storage->put($path, $keyname);
        }

        /* Push the transmuxed/transcoded MP4 */
        $localpath = $settings['target'] . "$id.mp4";
        $remotepath = "processed/video/$id.mp4";

        $this->storage->put($localpath, $remotepath);
    }

    /**
     * Deletes the input file we downloaded and the output files that were generated
     *
     * @param $outputdir - Output workspace directory to purge
     * @param $inputfile - Input video file to purge
     */
    private function cleanupWorkspace($outputdir, $inputfile)
    {
        /* Delete local unprocessed input file */
        unlink($inputfile);

        /* Delete output workspace containing the thumbs and mp4 */\
        array_map('unlink', glob("$outputdir/*.*"));
        rmdir($outputdir);
    }
}
