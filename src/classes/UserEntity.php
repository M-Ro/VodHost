<?php
namespace App\Backend;

class UserEntity
{
	protected $id;
	protected $username;
	protected $email;
	protected $password;

	/**
     * Construct class from data array
     * @param array $data The data to use to create
     */
    public function __construct(array $data) {
        // no id if we're creating
        if(isset($data['id'])) {
            $this->id = $data['id'];
        }

        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    public function getId() {
        return $this->id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPassword() {
        return $this->password;
    }
}

?>