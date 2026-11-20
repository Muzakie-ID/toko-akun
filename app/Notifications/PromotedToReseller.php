<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotedToReseller extends Notification
{
    public function __construct(public int $threshold) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Selamat, kamu sekarang Reseller!')
            ->greeting("Halo {$notifiable->name},")
            ->line('Total belanjamu sudah mencapai Rp'.number_format($this->threshold, 0, ',', '.').'. Akunmu otomatis naik menjadi Reseller.')
            ->line('Mulai pesanan berikutnya, harga reseller (lebih murah) langsung berlaku di katalog dan checkout.')
            ->action('Lihat Katalog', route('home'));
    }
}
