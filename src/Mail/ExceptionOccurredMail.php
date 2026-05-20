<?php

namespace BahriCanli\ExceptionMailer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExceptionOccurredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @var string
     */
    public $subjectLine;

    /**
     * @var array
     */
    public $exceptionData;

    /**
     * @var array
     */
    public $requestData;

    /**
     * @var array
     */
    public $fromConfig;

    public function __construct(string $subjectLine, array $exceptionData, array $requestData, array $fromConfig = [])
    {
        $this->subjectLine = $subjectLine;
        $this->exceptionData = $exceptionData;
        $this->requestData = $requestData;
        $this->fromConfig = $fromConfig;
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
