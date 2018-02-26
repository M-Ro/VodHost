<?php
namespace App\Frontend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BroadcastEntity
 * @package App\Entity
 *
 * @ORM\Table(name="broadcast")
 * @ORM\Entity
 */
class BroadcastEntity implements \JsonSerializable
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var integer
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $user_id;

    /**
     * @var string
     * @ORM\Column(name="title", type="string")
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="filename", type="string")
     */
    protected $filename;

    /**
     * @var float
     * @ORM\Column(name="length", type="float")
     */
    protected $length;

    /**
     * @var boolean
     * @ORM\Column(name="visibility", type="boolean")
     */
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
