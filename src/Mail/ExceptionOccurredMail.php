<?php

namespace BahriCanli\ExceptionMailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExceptionOccurredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public array $exceptionData,
        public array $requestData,
        public array $fromConfig = []
    ) {
    }

    public function build(): self
    {
        $mail = $this->view('exception-mailer::exception')
            ->subject($this->subjectLine)
            ->with([
                'exception' => $this->exceptionData,
                'request' => $this->requestData,
            ]);

        if (! empty($this->fromConfig['address'])) {
            $mail->from($this->fromConfig['address'], $this->fromConfig['name'] ?: null);
        }

        return $mail;
    }
}
