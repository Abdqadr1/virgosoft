<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'matchup.{id}',
    fn(User $user, int $id) => intval($user->id) === intval($id)
);
