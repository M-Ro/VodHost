<?php

namespace VodHost\Storage;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

class S3StorageEngine extends StorageEngine
{
    private $s3;
    private $s3_bucket;

    /**
     * Initializes the S3 connection instance
     */
    public function __construct(array $setup, $log)
    {
        parent::__construct($setup, $log);

        $this->s3_bucket = $setup['bucket'];

        $this->s3 = new S3Client([
            'version'     => 'latest',
            'region'      => $setup['region'],
            'credentials' => [
                'key'    => $setup['key'],
                'secret' => $setup['secret'],
            ],
        ]);
    }

    /**
     * Uploads files via the AWS S3 API. If the file is less than 5MB
     * it is uploaded in a single chunk. Files larger than 5MB are uploaded
     * using the MultipartUpload interface.
     */
    public function put($local_path, $remote_path)
    {
        if (!file_exists($local_path)) {
            throw new \Exception("File not found");
        }

        $filesize = filesize($local_path);

        if ($filesize < 5 * 1024 * 1024) { // Upload as single object
            try {
                $result = $this->s3->putObject(array(
                    'Bucket' => $this->s3_bucket,
                    'Key'    => $remote_path,
                    'SourceFile'   => $local_path,
                    'ACL'    => 'public-read'
                ));

                // Print the URL to the object.
                $this->log->info($result['ObjectURL'] . PHP_EOL);
            } catch (S3Exception $e) {
                $this->log->error($e->getMessage() . PHP_EOL);
            }
        } else { // MultiPart Upload

            $result = $this->s3->createMultipartUpload(array(
                'Bucket'       => $this->s3_bucket,
                'Key'          => $remote_path,
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ACL'          => 'public-read',
            ));
            $uploadId = $result['UploadId'];

            // Upload the file in parts.
            try {
                $file = fopen($local_path, 'r');
                $parts = array();
                $partNumber = 1;
                while (!feof($file)) {
                    $result = $this->s3->uploadPart(array(
                        'Bucket'     => $this->s3_bucket,
                        'Key'        => $remote_path,
                        'UploadId'   => $uploadId,
                        'PartNumber' => $partNumber,
                        'Body'       => fread($file, 16 * 1024 * 1024),
                    ));
                    $parts[] = array(
                        'PartNumber' => $partNumber++,
                        'ETag'       => $result['ETag'],
                    );

                    echo "Uploading part {$partNumber} of {$local_path}.\n";
                }

                fclose($file);
            } catch (S3Exception $e) {
                $result = $this->s3->abortMultipartUpload(array(
                    'Bucket'   => $this->s3_bucket,
                    'Key'      => $remote_path,
                    'UploadId' => $uploadId
                ));

                $this->log->error("Upload of {$local_path} failed" . PHP_EOL);
            }

            // Complete multipart upload.
            $result = $this->s3->completeMultipartUpload(array(
                'Bucket'   => $this->s3_bucket,
                'Key'      => $remote_path,
                'UploadId' => $uploadId,
                'MultipartUpload' => array(
                    'Parts' => $parts,
                ),
            ));

            $url = $result['Location'];

            $this->log->info("Uploaded {$local_path} to {$url}." . PHP_EOL);
        }
    }

    public function get($remote_path)
    {
        throw new \Exception("Stub");
    }
}
