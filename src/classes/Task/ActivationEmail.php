<?php 
namespace VodHost\Task;

class ActivationEmail extends EmailTask
{
    /**
     * @var string
     * Username of newly registered user
     */
    private $username;

    /**
     * @var string
     * Account validation hash
     */
    private $hash;

    /**
     * Initialize
     * @param $mq - Reference to message/job queue
     * @param $address - Recipient Email Address
     * @param $username - Recipient account username
     * @param $hash - Account authentication hash
     * @param $json_data - Used if we are decoding an already sent task
     */
    public function __construct($mq, $address, $username, $hash, $json_data = null)
    {
        if (!is_null($json_data)) {
            $this->decode($json_data);
        } else {
            parent::__construct($mq, $address, 'Activation', 'ActivationEmail');

            $this->username = $username;
            $this->hash = $hash;
        }
    }

    public function decode($json_data)
    {
        $values = parent::decode($json_data);

        $this->username = $values['username'];
        $this->hash = $values['hash'];

        return $values;
    }

    public function jsonSerialize()
    {
        $vars = parent::jsonSerialize();
        return array_merge($vars, get_object_vars($this));
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getHash()
    {
        return $this->hash;
    }
}