<?php

namespace App\Providers;

use App\Models\Reservation;
use App\Observers\ReservationObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage())
                ->subject('Potvrdite email adresu | Sportski Centar Arena')
                ->view('emails.auth.verify-email', [
                    'url' => $url,
                    'user' => $notifiable,
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage())
                ->subject('Reset lozinke | Arena SC')
                ->greeting('Zahtev za reset lozinke')
                ->line('Primili smo zahtev za promenu lozinke na vasem nalogu.')
                ->action('Resetuj lozinku', $url)
                ->line('Ako niste trazili reset lozinke, nije potrebna nikakva dodatna akcija.');
        });
    }
}
