<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AnnouncementPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pengumuman Baru: '.$this->announcement->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->announcement->title)
            ->line(Str::limit($this->announcement->body, 200))
            ->action('Baca Pengumuman', route('announcements.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'announcement_published',
            'message' => 'Pengumuman baru: '.$this->announcement->title,
            'announcement_id' => $this->announcement->id,
        ];
    }
}
