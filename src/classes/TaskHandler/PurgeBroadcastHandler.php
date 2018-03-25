<?php

namespace VodHost\TaskHandler;

use VodHost\Storage;
use VodHost\Task;

/**
 * Handler for purging broadcasts deleted by the user or backend
 */
class PurgeBroadcastHandler extends TaskHandler
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

        $this->storage = new Storage\S3StorageEngine($s3_setup, $this->log);
    }

    /**
     * Renders the required email template and sends the email via phpmailer
     */
    public function processTask($msg)
    {
        $params = json_decode($msg->body, true);

        $task = new Task\PurgeBroadcastTask(null, null, $msg->body);
        $id = $task->getBroadcastId();

        if (!$id) {
            $this->log->warn("Received PurgeBroadcastTask with null or empty id" . PHP_EOL);
            return;
        }

        // List of all files deleted
        $files = array();

        /* Delete thumbnails */
        $thumbs = $this->storage->listDirectory("thumb/$id/");

        foreach ($thumbs as $thumbnail) {
            $this->storage->delete($thumbnail);
            $files[] = $thumbnail;
        }

        $this->storage->delete("thumb/$id/"); // Delete thumbnail dir

        /* Delete processed video */
        $this->storage->delete("video/$id.mp4");
        $files[] = "video/$id.mp4";

        $this->log->debug("Worker deleted files: " . implode(", ", $files) . PHP_EOL);

        // Acknowledge job handled
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * Fetches mail queue jobs
     */
    public function run()
    {
        // Inform AMQP which job queue we consume from
        $this->channel->basic_consume('purge_broadcast', '', false, false, false, false, array($this, 'processTask'));

        /* Start processing jobs */
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}
