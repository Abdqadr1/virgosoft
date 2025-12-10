<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'user.{id}',
    fn($user, $id) => intval($user->id) === intval($id)
);
