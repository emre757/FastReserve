<?php

use App\Models\Offering;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('offerings.{offering}', function (User $user, Offering $offering) {
    return $user->can('view', $offering);
});
