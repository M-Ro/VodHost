<?php

namespace VodHost\Backend;

/**
 * Represents the functionality of a background worker.
 */
abstract class Worker
{
    protected $log;

    protected $connection;
    protected $channel;

    protected $config;

    protected $api;

    /**
     * Initializes the worker object
     * @param array $config - Various configuration variables used
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->api = new APIMapper($config);

        $this->connectLogger();
        $this->connectTaskQueue();
    }

    /** Cleans up any local references / remote connections.
     * Should be called once finished with the class.
     */
    public function shutdown()
    {
        $this->channel->close();
        $this->connection->close();
    }

    abstract public function run();

    /** Connects to the remote redis db for logging.
     *
     */
    private function connectLogger()
    {
        $logger = new \Monolog\Logger('Log');

        $client = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => $this->config['log_host'],
            'port'   => $this->config['log_port'],
            'password' => $this->config['log_pass']
        ]);
        $redis_handler = new \Monolog\Handler\RedisHandler($client, 'backend_logs');

        $logger->pushHandler($redis_handler);

        $this->log = $logger;
    }

    /** Connects to the remote task queue.
     *
     */
    private function connectTaskQueue()
    {
        $this->connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            $this->config['jobq_host'],
            $this->config['jobq_port'],
            $this->config['jobq_user'],
            $this->config['jobq_pass']
        );

        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('vprocessing', false, true, false, false);
    }
}
