<?php

namespace framework;

use framework\render\html\SmartyFactory;
use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;

class NewMailHandler extends Singleton
{
    private Swift_Mailer $mailer;
    private Swift_Message $msg;

    public function __construct()
    {
        $transport = match (strtolower(trim($_ENV['MAIL_METHOD']))) {
            'sendmail' => new Swift_SendmailTransport(),
            'smtp' => (new Swift_SmtpTransport(
                $_ENV['MAIL_SMTP_HOST'],
                (int) $_ENV['MAIL_SMTP_PORT'],
                $_ENV['MAIL_SMPT_ENCRYPT'])
            )->setUsername($_ENV['MAIL_SMTP_USER'])
            ->setPassword($_ENV['MAIL_SMTP_PASSWORD']),
        };
        $this->initMsg();

        $this->mailer = new Swift_Mailer($transport);
    }

    /**
     * @param string $mailTemplateName name of the file <name>.(txt|html).tpl in template/mail/
     * @param array $parameters [varName => value]
     * @return array [$success, $msg]
     */
    public function send(string $mailTemplateName, array $parameters = []): array
    {
        $smarty = SmartyFactory::make();
        foreach ($parameters as $name => $value) {
            $smarty->assign($name, $value);
        }
        try {
            if ($smarty->templateExists($mailTemplateName . 'html.tpl')) {
                $bodyHtml = $smarty->fetch($mailTemplateName . 'html.tpl');
            }
            if ($smarty->templateExists($mailTemplateName . 'txt.tpl')) {
                $bodyTxt = $smarty->fetch($mailTemplateName . 'txt.tpl');
            }
        } catch (\Exception $exception) {
            return [false, $exception->getMessage()];
        }

        $body = $bodyTxt ?? strip_tags($bodyHtml) ?? $mailTemplateName;
        $this->msg->setBody($body, 'text/plain');
        if (isset($bodyHtml)) {
            $this->msg->addPart($bodyHtml, 'text/html');
        }
        $failedDelivery = [];
        $this->mailer->send($this->msg, $failedDelivery);
        $this->initMsg(); // reset msg

        return [count($failedDelivery) === 0, count($failedDelivery) > 0 ? implode(', ' . $failedDelivery) .' not delivered' : 'Success'];
    }

    public function sendText(string $text): array
    {
        $text = strip_tags($text);
        $this->msg->setBody($text, 'text/plain');
        $failedDelivery = [];
        $this->mailer->send($this->msg, $failedDelivery);
        $this->initMsg(); // reset msg
        return [count($failedDelivery) === 0, count($failedDelivery) > 0 ? implode(', ' . $failedDelivery) .' not delivered' : 'Success'];
    }

    public function initMsg(): self
    {
        $msg = new Swift_Message();
        $msg->setFrom([$_ENV['MAIL_FROM_EMAIL'] => $_ENV['MAIL_FROM_NAME']]);
        $msg->setReturnPath($_ENV['MAIL_BOUNCE_EMAIL']);
        $this->msg = $msg;
        return $this;
    }

    /**
     * @param string|array $to 'mail@example.com' or ['mail@example.com' => 'John Doe']
     */
    public function addTo(string|array $to): self
    {
        if (!is_array($to)) {
            $to = [$to => $to];
        }
        foreach ($to as $address => $alias) {
            $this->msg->addTo($address, $alias);
        }
        return $this;
    }

    /**
     * @param string|array $cc 'mail@example.com' or ['mail@example.com' => 'John Doe']
     */
    public function addCC(string|array $cc): self
    {
        if (!is_array($cc)) {
            $cc = [$cc => $cc];
        }
        foreach ($cc as $address => $alias) {
            $this->msg->addCc($address, $alias);
        }
        return $this;
    }

    /**
     * @param string|array $bcc 'mail@example.com' or ['mail@example.com' => 'John Doe']
     */
    public function addBcc(string|array $bcc): self
    {
        if (!is_array($bcc)) {
            $bcc = [$bcc => $bcc];
        }
        foreach ($bcc as $address => $alias) {
            $this->msg->addBcc($address, $alias);
        }
        return $this;
    }

    /**
     * @param string|array $replyTo 'mail@example.com' or ['mail@example.com' => 'John Doe']
     */
    public function addReplyTo(string|array $replyTo): self
    {
        if (!is_array($replyTo)) {
            $replyTo = [$replyTo => $replyTo];
        }
        foreach ($replyTo as $address => $alias) {
            $this->msg->addReplyTo($address, $alias);
        }
        return $this;
    }

    /**
     * @param string $subject subject of the mail
     */
    public function setSubject(string $subject): self
    {
        $this->msg->setSubject($subject);
        return $this;
    }

    public function getTo(): array
    {
        return $this->msg->getTo();
    }
}
