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
}
