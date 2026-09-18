<?php

namespace App\Mail;

use App\Models\MonthlyApproval;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class HoursReadyForApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public MonthlyApproval $approval,
    ) {}

    public function envelope(): Envelope
    {
        $period = Carbon::createFromDate($this->approval->year, $this->approval->month, 1)->translatedFormat('F Y');

        return new Envelope(
            subject: "Uren ter goedkeuring: {$this->project->name} — {$period}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.hours-ready-for-approval',
            with: [
                'period' => Carbon::createFromDate($this->approval->year, $this->approval->month, 1)->translatedFormat('F Y'),
            ],
        );
    }
}
