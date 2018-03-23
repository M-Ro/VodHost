<?php
namespace App\Frontend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BroadcastEntity
 * @package App\Backend
 *
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class UserEntity
{

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="username", type="string")
     */
    protected $username;

    /**
     * @var string
     * @ORM\Column(name="email", type="string")
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="password", type="string")
     */
    protected $password;

    /**
     * @var boolean
     * @ORM\Column(name="admin", type="boolean")
     */
    protected $admin;

    /**
     * @var boolean
     * @ORM\Column(name="activated", type="boolean")
     */
    protected $activated;

    /**
     * @var string
     * @ORM\Column(name="hash", type="string")
     */
    protected $hash;

    /**
     * @var datetime
     * @ORM\Column(name="date_registered", type="datetime")
     */
    protected $date_registered;


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

        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->admin = false;
        $this->activated = true;
        $this->hash = bin2hex(random_bytes(16));
        $this->date_registered = new \DateTime("now");
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getActivated()
    {
        return $this->activated;
    }

    public function getDateRegistered()
    {
        return $this->date_registered;
    }

    public function getAdmin()
    {
        return $this->admin;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setAdmin($val)
    {
        $this->admin = $val;
    }
}
