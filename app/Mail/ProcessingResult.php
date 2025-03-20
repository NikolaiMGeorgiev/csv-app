<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessingResult extends Mailable
{
    use Queueable, SerializesModels;

    public $status;
    public $uploadId;
    public $username;

    /**
     * Create a new message instance.
     */
    public function __construct($status, $filename, $userId) {
        $uploadData = DB::table('uploads')
            ->where([
                ['users_id', '=', $userId],
                ['file_path', '=', $filename]
            ])
            ->select('id')
            ->first();
        $userData = DB::table('users')
            ->where([
                ['id', '=', $userId]
            ])
            ->select('name')
            ->first();
        $this->status = $status;
        $this->uploadId = $uploadData->id;
        $this->username = $userData->name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope {
        return new Envelope(
            subject: 'CSV Processing Result',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content {
        return new Content(
            view: 'result_email',
            with: [
                'result' => $this->status == 'completed' ? 'successfully' : 'unsuccessfully',
                'appName' => config('mail.from.name')
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array {
        return [];
    }
}
