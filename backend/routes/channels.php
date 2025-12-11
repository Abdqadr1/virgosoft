<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'match-up.{id}',
    fn($user, $id) => intval($user->id) === intval($id)
);
