<?php

namespace App\Service;

class Mailer
{
    private $mailer;
    private $from;

    /**
     * Mailer constructor.
     * @param \Swift_Mailer $mailer
     * @param string $from
     */
    public function __construct(\Swift_Mailer $mailer, string $from)
    {
        $this->mailer = $mailer;
        $this->from = $from;
    }

    /**
     * @param $to
     * @param string $subject
     * @param string $text
     */
    public function send($to, string $subject, string $text)
    {
        $message = (new \Swift_Message($subject))
            ->setFrom($this->from)
            ->setTo($to)
            ->setBody(
                $text,
                'text/html'
            );

        $this->mailer->send($message);
    }
}