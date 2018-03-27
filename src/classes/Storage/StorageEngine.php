<?php

namespace VodHost\Storage;

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

    /**
     * Attempts to push/upload an object to the storage endpoint.
     * @param string $local_path Full path of the object in local storage
     * @param string $remote_path Full path of the object in remote storage
     */
    abstract public function put($local_path, $remote_path);

    abstract public function get($remote_path, $local_path);

    /**
     * Returns the contents of a specified directory
     * @param string $remote_path Full path of the directory
     * @return array[string] array of filepaths in this location
     */
    abstract public function listDirectory($remote_path);

    /**
     * Attempts to delete an object stored on the remote endpoint.
     * @param string $remote_path Full path of the object in remote storage
     */
    abstract public function delete($remote_path);

    /**
     * Returns whether an object exists with the path specified on the storage endpoint.
     * @param string $remote_path Full path of the object in remote storage
     * @return bool
     */
    abstract public function exists($remote_path);

    public static function BuildStorageEngine(array $setup, $log)
    {
        switch($setup['engine'])
        {
            case 's3': return new \VodHost\Storage\S3StorageEngine($setup, $log);
            default: return null;
        }
    }
}
