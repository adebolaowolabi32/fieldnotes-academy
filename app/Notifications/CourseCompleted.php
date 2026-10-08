<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enrollment $enrollment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Your Fieldnotes certificate is ready')->line('You completed '.$this->enrollment->course->title.'.')->action('View certificate', route('certificate', $this->enrollment));
    }
}
