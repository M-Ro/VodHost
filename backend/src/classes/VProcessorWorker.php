<?php

namespace VodHost\Backend;

require_once 'vprocessor.php';

/**
 * Worker class for the processing of uploaded video streams.
 */
class VProcessorWorker extends Worker
{

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

            $path = $this->config['upload_directory'] . DIRECTORY_SEPARATOR . $data['filename'];
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
            $vprocessor->generateThumbnailSet($v_setup);
            $this->log->debug("Generated thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            $vprocessor->scaleThumbnails($v_setup);
            $this->log->debug("Scaled thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            /* Transmux if required */
            $vprocessor->transmuxToMP4($v_setup);
            $this->log->debug("Transmuxed content to MP4 for id " . $data['broadcastid'] . PHP_EOL);
        };

        // Inform AMQP which job queue we consume from
        $this->channel->basic_consume('vprocessing', '', false, false, false, false, $callback);

        /* Start processing jobs */
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}