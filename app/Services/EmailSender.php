<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class EmailSender
{
    /**
     * Send the email right away (queueing is the caller's concern, see the jobs).
     */
    public function send(string $to, Mailable $mail): void
    {
        Mail::to($to)->send($mail);
    }
}
