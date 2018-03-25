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
     * Subject of email
     */
    protected $subject;

    /**
     * @var string
     * Type of 'mail' being sent. Used to render the correct template
     */
    protected $mailtype;

    /**
     * Initialize
     * @param $mq - Reference to message/job queue
     * @param $address - Recipient Email Address
     * @param $subject - Subject of email
     * @param $mailtype - Used by the worker to render the correct template
     */
    public function __construct($mq, $address, $subject, $mailtype)
    {
        parent::__construct($mq);
        $this->setQueue('mail_queue');

        $this->address = $address;
        $this->subject = $subject;
        $this->mailtype = $mailtype;
    }

    public function decode($json_data)
    {
        $values = parent::decode($json_data);

        $this->address = $values['address'];
        $this->subject = $values['subject'];
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

    public function getSubject()
    {
        return $this->subject;
    }
}
