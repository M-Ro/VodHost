<?php

namespace VodHost\Backend;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

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

            $id = $data['broadcastid'];

            /* Retrieve information from the frontend about the job */
            $broadcast_details = $this->getBroadcastInfo($id);
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
            $vprocessor->generateThumbnailSet($v_setup);
            $this->log->debug("Generated thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            $vprocessor->scaleThumbnails($v_setup);
            $this->log->debug("Scaled thumbnails for id " . $data['broadcastid'] . PHP_EOL);

            /* Transmux if required */
            $vprocessor->transmuxToMP4($v_setup);
            $this->log->debug("Transmuxed content to MP4 for id " . $data['broadcastid'] . PHP_EOL);

            /* Upload the processed content */
            $this->uploadThumbsToS3($data['broadcastid'], $v_setup);
            $this->uploadVideoToS3($data['broadcastid'], $v_setup);
        };

        // Inform AMQP which job queue we consume from
        $this->channel->basic_consume('vprocessing', '', false, false, false, false, $callback);

        /* Start processing jobs */
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * Calls /api/backend/retrieve/$id and returns the json result
     *
     * @param int $id - id of the broadcast
     * @return json array contained in the response, or null on error
     */
    private function getBroadcastInfo(int $id)
    {
        $url = $this->config['server_domain'] . '/api/backend/retrieve/' . $id;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array('X-API-KEY: ' . $this->config['api_key'])
        ));

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;        
    }

    private function uploadThumbsToS3($id, $settings)
    {
        $bucket = 'vodhost';

        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => 'eu-central-1',
            'credentials' => [
                'key'    => $this->config['s3_key'],
                'secret' => $this->config['s3_secret'],
            ],
        ]);

        for($i=0; $i<$settings['thumbcount']; $i++) {
            $keyname = "thumb/" . $id . '/' . "thumb_$i.jpg";
            $path = $settings['target'] . "thumb_$i.jpg";

            try {
                $result = $s3->putObject(array(
                    'Bucket' => $bucket,
                    'Key'    => $keyname,
                    'SourceFile'   => $path,
                    'ACL'    => 'public-read'
                ));

                // Print the URL to the object.
                $this->log->info($result['ObjectURL'] . PHP_EOL);
            } catch (S3Exception $e) {
                $this->log->error($e->getMessage() . PHP_EOL);
            }
        }
    }

    private function uploadVideoToS3($id, $settings)
    {
        $bucket = 'vodhost';

        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => 'eu-central-1',
            'credentials' => [
                'key'    => $this->config['s3_key'],
                'secret' => $this->config['s3_secret'],
            ],
        ]);

        $keyname = "video/$id.mp4";
        $path = $settings['target'] . "$id.mp4";

        // Create a new multipart upload and get the upload ID.
        $result = $s3->createMultipartUpload(array(
            'Bucket'       => $bucket,
            'Key'          => $keyname,
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'ACL'          => 'public-read',
        ));
        $uploadId = $result['UploadId'];

        // Upload the file in parts.
        try {
            $file = fopen($path, 'r');
            $parts = array();
            $partNumber = 1;
            while (!feof($file)) {
                $result = $s3->uploadPart(array(
                    'Bucket'     => $bucket,
                    'Key'        => $keyname,
                    'UploadId'   => $uploadId,
                    'PartNumber' => $partNumber,
                    'Body'       => fread($file, 5 * 1024 * 1024),
                ));
                $parts[] = array(
                    'PartNumber' => $partNumber++,
                    'ETag'       => $result['ETag'],
                );

                echo "Uploading part {$partNumber} of {$path}.\n";
            }

            fclose($file);
        } catch (S3Exception $e) {
            $result = $s3->abortMultipartUpload(array(
                'Bucket'   => $bucket,
                'Key'      => $keyname,
                'UploadId' => $uploadId
            ));

            $this->log->error("Upload of {$path} failed" . PHP_EOL);
        }

        // 4. Complete multipart upload.
        $result = $s3->completeMultipartUpload(array(
            'Bucket'   => $bucket,
            'Key'      => $keyname,
            'UploadId' => $uploadId,
            'MultipartUpload' => Array(
                'Parts' => $parts,
            ),
        ));

        $url = $result['Location'];

        $this->log->info("Uploaded {$path} to {$url}." . PHP_EOL);
    }
}