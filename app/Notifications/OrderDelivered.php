<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email ke pembeli saat kredensial terkirim. Kredensial sengaja tidak ditulis di email;
 * pembeli membukanya di halaman pesanan (selalu versi terbaru bila ada klaim garansi).
 */
class OrderDelivered extends Notification
{
    public function __construct(public Order $order) {}

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
            ->subject("Akun untuk pesanan {$this->order->no_invoice} sudah siap")
            ->greeting("Halo {$notifiable->name},")
            ->line("Pembayaran pesanan {$this->order->no_invoice} sudah diterima dan akun sudah dikirim.")
            ->action('Lihat Kredensial', route('orders.show', $this->order))
            ->line('Ada masalah dengan akun? Klaim garansi lewat admin, kredensial pengganti otomatis muncul di halaman pesanan.');
    }
}
