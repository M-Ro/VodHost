<?php

namespace VodHost\Backend;

require_once 'vprocessor.php';

/**
 * Worker class for the processing of uploaded video streams.
 */
class VProcessorWorker extends Worker
{
    private $storage;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $s3_setup = [
            'bucket' => 'vodhost',
            'region' => 'eu-central-1',
            'key' => $this->config['s3_key'],
            'secret' => $this->config['s3_secret']
        ];

        $this->storage = new S3StorageEngine($s3_setup, $this->log);
    }

    /**
     * Processes tasks from the video processing queue.
     * Responsible for generating thumbnails and transmuxing uploaded content if required.
     */
    public function run()
    {
        /* Callback function everytime we receive a task from the queue */
        $callback = function($msg) {
            $this->log->debug("Received task for processing: " . $msg->body . PHP_EOL);

            // Acknowledge job received
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

            $data = json_decode($msg->body, true);

            $id = $data['broadcastid'];

            /* Retrieve information from the frontend about the job */
            $broadcast_details = $this->api->getBroadcastInfo($id);
            if(!$broadcast_details) {
                return;
            }

            $broadcast_details = json_decode($broadcast_details, true);

            /* Fetch the file from the processing queue using curl */

            $url = $this->config['server_domain'] . '/uploads/processing/' . $broadcast_details['filename'];
            $path = $broadcast_details['filename'];

            $fp = fopen ($path, 'w+');
            $curl_handle = curl_init(str_replace(" ","%20", $url));
            curl_setopt($curl_handle, CURLOPT_FILE, $fp); 
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
            $ret = curl_exec($curl_handle);
            curl_close($curl_handle);
            fclose($fp);

            if(!$ret)
            {
                $this->log->error("Could not fetch video for processing: " . $url . PHP_EOL);
                return;
            }

            $vprocessor = new VProcessor($path);

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

            $vprocessor = new VProcessor($v_setup['target'] . $v_setup['output_filename']);

            /* We generate the thumbnails *after* transmuxing as ffmpeg can't seek some filetypes */
            $vprocessor->generateThumbnailSet($v_setup);
            $this->log->debug("Generated thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            $vprocessor->scaleThumbnails($v_setup);
            $this->log->debug("Scaled thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            /* Upload the processed content */
            $this->pushToStorage($v_setup, $data['broadcastid']);

            /* Cleanup local files we created */
            $this->cleanupWorkspace($v_setup['target'], $path);

            /* Inform the frontend application the video is processed */
            $this->api->tagBroadcastAsProcessed($data['broadcastid']);
        };

        // Inform AMQP which job queue we consume from
        $this->channel->basic_consume('vprocessing', '', false, false, false, false, $callback);

        /* Start processing jobs */
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    private function pushToStorage($settings, $id)
    {
        /* First, upload the thumbnails we generated */
        for($i=0; $i<$settings['thumbcount']; $i++) {
            $keyname = "thumb/" . $id . '/' . "thumb_$i.jpg";
            $path = $settings['target'] . "thumb_$i.jpg";
            $this->storage->put($path, $keyname);
        }

        /* Push the transmuxed/transcoded MP4 */
        $localpath = $settings['target'] . "$id.mp4";
        $remotepath = "video/$id.mp4";

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