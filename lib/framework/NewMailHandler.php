<?php

namespace framework;

use framework\render\html\SmartyFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class NewMailHandler extends Singleton
{
    private Mailer $mailer;
    private Email $mail;

    public function __construct()
    {
        $dns = match (strtolower(trim($_ENV['MAIL_METHOD']))) {
            'sendmail' => 'sendmail://default',
            'smtp' => // smtp://user:pass@smtp.example.com:25
                "smtp://{$_ENV['MAIL_SMTP_USER']}:{$_ENV['MAIL_SMTP_PASSWORD']}@{$_ENV['MAIL_SMTP_HOST']}:{$_ENV['MAIL_SMTP_PORT']}",
        };
        $this->initMsg();
        $transport = Transport::fromDsn($dns);
        $this->mailer = new Mailer($transport);
    }

    /**
     * @param string $mailTemplateName name of the file <name>.(txt|html).tpl in template/mail/
     * @param array $parameters [varName => value]
     * @return array [$success, $msg]
     */
    public function send(string $mailTemplateName, array $parameters = []): array
    {
        // TODO: IT HAS TWIG INTEGRATION!
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
        $this->mail->text($body);
        if (isset($bodyHtml)) {
            $this->mail->html($bodyHtml);
        }
        $this->mailer->send($this->mail);
        $this->initMsg(); // reset msg

        return [true, 'Mail wurde versandt'];
    }

    public function sendText(string $text): array
    {
        $text = strip_tags($text);
        $this->mail->text($text);
        $this->mailer->send($this->mail);
        $this->initMsg(); // reset msg
        return [true, 'Mail wurde versandt'];
    }

    public function initMsg(): self
    {
        $msg = new Email();
        $msg->from(new Address((string) [$_ENV['MAIL_FROM_EMAIL'], $_ENV['MAIL_FROM_NAME']]));
        $msg->returnPath($_ENV['MAIL_BOUNCE_EMAIL']);
        $this->mail = $msg;
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
            $this->mail->addTo(new Address($address, $alias));
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
            $this->mail->addCc(new Address($address, $alias));
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
            $this->mail->addBcc(new Address($address, $alias));
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
            $this->mail->addReplyTo(new Address($address, $alias));
        }
        return $this;
    }

    /**
     * @param string $subject subject of the mail
     */
    public function setSubject(string $subject): self
    {
        $this->mail->subject($subject);
        return $this;
    }
}
