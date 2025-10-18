<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('doctor.{id}', function ($user, $id) {
    return $user->doctor && $user->doctor->id === (int) $id;
});
