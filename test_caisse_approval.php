<?php

use App\Models\User;

echo "Testing caisse approval system...\n";

$users = User::take(3)->get(['id', 'name', 'email']);

foreach($users as $user) {
    $canApprove = $user->hasPermissionTo('caisse.approve') ? 'YES' : 'NO';
    echo "{$user->name} can approve caisse: {$canApprove}\n";
}

echo "Done.\n";
