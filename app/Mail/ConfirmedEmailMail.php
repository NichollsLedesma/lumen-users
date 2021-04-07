<?php


namespace App\Mail;

use Illuminate\Mail\Mailable;

class ConfirmedEmailMail extends Mailable
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * @return ConfirmedEmailMail
     */
    public function build()
    {
        return $this->subject('Confirm email to ' . env('APP_NAME'))
            ->markdown('mails.confirm_email', [
                "email" => $this->user->email,
                "name" => $this->user->name,
                "code" => $this->user->code_activation,
            ]);
    }
}
