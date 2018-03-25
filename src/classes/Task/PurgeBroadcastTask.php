<?php 
namespace VodHost\Task;

class PurgeBroadcastTask extends Task
{
    /**
     * @var string
     * id of the broadcast being removed
     */
    protected $broadcast_id;

    /**
     * Initialize
     * @param $mq - Reference to message/job queue
     * @param $id - broadcast id
     * @param $json_data - Used if we are decoding an already sent task
     */
    public function __construct($mq, $id, $json_data = null)
    {
        if (!is_null($json_data)) {
            $this->decode($json_data);
        } else {
            parent::__construct($mq);
            $this->setQueue('purge_broadcast');

            $this->broadcast_id = $id;
        }
    }

    public function decode($json_data)
    {
        $values = parent::decode($json_data);

        $this->broadcast_id = $values['broadcast_id'];

        return $values;
    }

    public function jsonSerialize()
    {
        $vars = parent::jsonSerialize();
        return array_merge($vars, get_object_vars($this));
    }

    public function getBroadcastId()
    {
        return $this->broadcast_id;
    }
}
