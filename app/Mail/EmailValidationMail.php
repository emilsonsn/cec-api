<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailValidationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;


    /**
     * Create a new message instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the message envelope.
     */
    
    public function build()
    {
        return $this->view('emails.email_validation')
                    ->with([
                        'code' => $this->code,
                    ])
                    ->subject('Validação de email');
    }

}
