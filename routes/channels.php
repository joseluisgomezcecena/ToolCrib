<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('alerts.admin', function ($user) {
    return $user->hasRole('super_admin');
});

Broadcast::channel('alerts.toolcrib', function ($user) {
    return $user->hasAnyRole(['super_admin', 'toolcrib']);
});

Broadcast::channel('alerts.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
