<?php
namespace App\Backend;

class BroadcastEntity implements \JsonSerializable
{
    protected $id;
    protected $user_id;
    protected $title;
    protected $filename;
    protected $length;
    protected $visibility;

    /**
     * Construct class from data array
     * @param array $data The data to use to create
     */
    public function __construct(array $data)
    {
        // no id if we're creating
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        $this->user_id = $data['user_id'];
        $this->title = $data['title'];
        $this->filename = $data['filename'];
        $this->length = $data['length'];
        $this->visibility = $data['visibility'];
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }
}
