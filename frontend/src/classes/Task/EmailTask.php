<?php 
namespace VodHost\Task;

abstract class EmailTask extends Task
{
    // Protected instead of private for get_object_vars subclass visibility

    /**
     * @var string
     * Recipient email address
     */
    protected $address;

    /**
     * @var string
     * Type of 'mail' being sent. Used to render the correct template
     */
    protected $mailtype;

    /**
     * Initialize
     * @param $mq - Reference to message/job queue
     * @param $address - Recipient Email Address
     * @param $mailtype - Used by the worker to render the correct template
     */
    public function __construct($mq, $address, $mailtype)
    {
        parent::__construct($mq);
        $this->setQueue('mail_queue');

        $this->address = $address;
        $this->mailtype = $mailtype;
    }

    public function decode($json_data)
    {
        $values = __parent::decode($json_data);

        $this->address = $values['address'];
        $this->mailtype = $values['mailtype'];

        return $values;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getMailtype()
    {
        return $this->mailtype;
    }
}
