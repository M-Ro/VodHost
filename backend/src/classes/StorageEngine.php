<?php

namespace VodHost\Backend;

/**
 * An abstract class that represents the functionality of storing processed files.
 * This allows for separate storage backends (S3, Google Cloud, even basic FTP) to
 * be implemented as derived classes.
 */
abstract class StorageEngine
{
    protected $log;

    public function __construct(array $setup, $log)
    {
        $this->log = $log;
    }

    abstract public function put($local_path, $remote_path);

    abstract public function get($remote_path);
}
