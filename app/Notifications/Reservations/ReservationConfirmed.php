<?php

namespace App\Notifications\Reservations;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ReservationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Reservation $reservation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // phpstorm generated pipe operator
        // bad design; just a placeholder
        return route('reservations.show', $this->reservation->id)
            |> url(...)
            |> (fn ($x) => (new MailMessage)->line("Your booking has been {$this->reservation->status->value}")->action('View Reservation', $x)
                ->line("Status: {$this->reservation->status->value}\nReference: {$this->reservation->reference}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
        ];
    }
}
