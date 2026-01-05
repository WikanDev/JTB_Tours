<?php

namespace App\Notifications;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AssignmentDeclinedNotification extends Notification
{
    use Queueable;

    public $assignment;
    public $declinedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Assignment $assignment, User $declinedBy)
    {
        $this->assignment = $assignment;
        $this->declinedBy = $declinedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $date = $this->assignment->order && $this->assignment->order->pickup_time 
            ? \Carbon\Carbon::parse($this->assignment->order->pickup_time)->format('d M H:i')
            : 'Jadwal belum ditentukan';

        $role = $this->declinedBy->role === 'driver' ? 'Driver' : 'Guide';
        $reason = $this->assignment->rejection_reason ?? 'Tidak ada alasan';

        return [
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'declined_by' => $this->declinedBy->name,
            'declined_by_role' => $role,
            'reason' => $reason,
            'message' => "⚠️ {$role} {$this->declinedBy->name} menolak tugas (Jemput {$date}). Alasan: {$reason}",
            'link' => route('assignments.index'),
            'type' => 'assignment_declined'
        ];
    }
}
