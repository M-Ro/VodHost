<?php 
namespace App\Frontend\Task;

use PhpAmqpLib\Message\AMQPMessage;

abstract class Task implements \JsonSerializable
{
    /**
     * reference to the job server interface
     */
    private $mq;

    /**
     * @var string
     * queue we will publish to
     */
    private $queue;
    
    /**
     * Initialize
     * @param $mq - Reference to message/job queue
     */
    public function __construct($mq)
    {
        $this->mq = $mq;
    }

    /**
     * Publishes the task to the queue specified in $queue.
     */
    public function publish()
    {
        if(!$this->mq || !$this->queue) {
            return false;
        }

        // Encode variables to data array
        $data = json_encode($this->jsonSerialize());

        $msg = new AMQPMessage(
            $data,
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

        $this->mq->basic_publish($msg, '', $this->queue);
    }

    /**
     * Serializes the task into an array and removes any fields we
     * dont want published to the queue, such as mq object references and
     * queue name
     *
     * @return array - modified array of class fields
     */
    public function jsonSerialize()
    {
        $vars = get_object_vars($this);

        // Remove mail queue references and return
        return array_diff_key($vars, ['mq' => null, 'queue' => null]);
    }

    /**
     * Decodes a json string into class variables
     *
     * @return array decoded from the json string
     */
    public function decode($json_data)
    {
        return json_decode($json_data, true);
    }

    protected function setQueue($queue)
    {
        $this->queue = $queue;
    }
}
