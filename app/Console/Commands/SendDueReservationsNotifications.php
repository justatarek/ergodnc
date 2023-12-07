<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\HostReservationStarting;
use App\Notifications\VisitorReservationStarting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDueReservationsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ergodnc:send-reservations-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Reservation::query()
            ->with(['user', 'office.user'])
            ->active()
            ->where('start_date', now()->toDateString())
            ->each(function (Reservation $reservation) {
                Notification::send($reservation->user, new VisitorReservationStarting($reservation));
                Notification::send($reservation->office->user, new HostReservationStarting($reservation));
            });
    }
}
