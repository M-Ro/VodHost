<?php

namespace VodHost\TaskHandler;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use VodHost\Task;

/**
 * Handler for sending emails published to the job queue
 */
class MailSender extends TaskHandler
{
    /**
     * @var PHPMailer
     */
    private $mailer;

    /**
     * @var PhpRenderer
     * template renderer
     */
    private $renderer;

    /**
     * @var string
     * server domain for linking in emails
     */
    private $domain;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->domain = $config['server_domain'];

        /* Configure the template renderer */
        $this->renderer = new \Slim\Views\PhpRenderer('assets/templates/email/');

        /* Configure the PHP Mailer */
        $mailconfig = $config['mail'];

        $this->mailer = new PHPMailer();
        $this->mailer->IsSMTP();
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->SMTPDebug  = 0;
        $this->mailer->SMTPAuth   = true;
        $this->mailer->setFrom('noreply@' . $config['server_domain'], 'VodHost');

        $this->mailer->SMTPSecure = $mailconfig['security'];
        $this->mailer->Host       = $mailconfig['host'];
        $this->mailer->Port       = $mailconfig['port'];
        $this->mailer->Username   = $mailconfig['username'];
        $this->mailer->Password   = $mailconfig['password'];
    }

    /**
     * Renders the required email template and sends the email via phpmailer
     */
    public function processTask($msg)
    {
        $params = json_decode($msg->body, true);

        $type = '\\VodHost\\Task\\' . $params['mailtype'];
        $task = new $type(null, null, null, null, $msg->body);

        // Set the send address and subject
        $this->mailer->addAddress($task->getAddress(), $task->getUsername());
        $this->mailer->Subject = $task->getSubject();

        // Render the template
        $params['domain'] = $this->domain;
        $template = $this->renderer->fetch($params['mailtype'] . '.phtml', $params);
        $this->mailer->msgHTML($template);

        if(!$this->mailer->send()) {
            error_log("Email not sent. " . $this->mailer->ErrorInfo . PHP_EOL);
        }

        $this->cleanup();

        // Acknowledge job handled
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    /**
     * Fetches mail queue jobs
     */
    public function run()
    {
        // Inform AMQP which job queue we consume from
        $this->channel->basic_consume('mail_queue', '', false, false, false, false, array($this, 'processTask'));

        /* Start processing jobs */
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * phpmailer state cleanup
     */
    private function cleanup()
    {
        $this->mailer->ClearAllRecipients();
        $this->mailer->ClearAttachments();
        $this->mailer->ClearCustomHeaders();

        $this->mailer->Subject = '';
        $this->mailer->AltBody = '';
    }
}
