<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Formatter\HtmlFormatter;
use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_Message;
use Illuminate\Support\Facades\Auth;

class MailLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger('mail');
        $transporter = new Swift_SmtpTransport(config('mail.mailers.smtp.host'), config('mail.mailers.smtp.port'), config('mail.mailers.smtp.encryption'));
        $transporter->setUsername(config('mail.mailers.smtp.username'));
        $transporter->setPassword(config('mail.mailers.smtp.password'));
        $mailer = new Swift_Mailer($transporter);

        $subject = 'Exception MailLogger env='.\App::environment();
        $user = Auth::user();
        if ($user)
            $subject .= ' user_id='.$user->id.', mail='.$user->email;

        // Create a message
        $message = new Swift_Message($subject);
        $message->setFrom(config('mail.mailers.smtp.username'));
        $message->setTo(config('mail.to.support.address'));
        $message->setContentType("text/html");

        $handler = new SwiftMailerHandler(
            $mailer,
            $message,
            Logger::INFO,
            true
        );
        $handler->setFormatter(new HtmlFormatter());
        $logger->pushHandler($handler);
        return $logger;
    }
}
