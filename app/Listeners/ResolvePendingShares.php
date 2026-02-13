<?php

namespace App\Listeners;

use App\Models\ContentShare;
use Illuminate\Auth\Events\Registered;

class ResolvePendingShares
{
    public function handle(Registered $event): void
    {
        ContentShare::whereNull('recipient_id')
            ->where('recipient_email', $event->user->email)
            ->update(['recipient_id' => $event->user->id]);
    }
}
