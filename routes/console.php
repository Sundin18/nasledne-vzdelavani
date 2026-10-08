<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email} {--revoke : Remove administrator rights instead}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("Uživatel {$email} neexistuje.");

        return 1;
    }

    $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

    $this->info($user->is_admin ? "{$email} je nyní administrátor." : "{$email} už není administrátor.");

    return 0;
})->purpose('Grant or revoke administrator rights for a user');
