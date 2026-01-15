<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenericNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $title;
    public $body;
    public $data;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct(string $title, string $body, array $data = [], string $type = 'general')
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->title)
                    ->view('emails.generic-notification')
                    ->with([
                        'title' => $this->title,
                        'body' => $this->body,
                        'data' => $this->data,
                        'type' => $this->type,
                    ]);
    }
}
