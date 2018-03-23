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
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
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
     * @ORM\Column(name="description", type="string")
     */
    protected $description;

    /**
     * @var state
     * @ORM\Column(name="state", type="string")
     */
    protected $state;

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
     * @var int
     * @ORM\Column(name="views", type="integer")
     */
    protected $views;

    /**
     * @var boolean
     * @ORM\Column(name="visibility", type="boolean")
     */
    protected $visibility;

    /**
     * @var datetime
     * @ORM\Column(name="upload_date", type="datetime")
     */
    protected $upload_date;

    /**
     * @var string
     * Set by the API when sending broadcasts to client. Not a db field
     */
    public $uploader;

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
        $this->description = $data['description'];
        $this->state = $data['state'];

        if (isset($data['views'])) {
            $this->views = $data['views'];
        } else {
            $this->views = 0;
        }
        if (isset($data['upload_date'])) {
            $this->upload_date = $data['upload_date'];
        } else {
            $this->upload_date = new \DateTime("now");
        }
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

    public function getDescription()
    {
        return $this->description;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getViews()
    {
        return $this->views;
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

    public function getUploadDate()
    {
        return $this->upload_date;
    }

    public function setState(string $state)
    {
        $this->state = $state;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function setDescription(string $desc)
    {
        $this->description = $desc;
    }

    public function setVisibility(string $vis)
    {
        $this->visibility = $vis;
    }
}
