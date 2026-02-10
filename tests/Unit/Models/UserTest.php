<?php

use App\Domain\Enums\UserRole;
use App\Models\User;

test('user with admin role has is_admin true', function () {
    $admin = new User(['role' => 'admin']);

    expect($admin->is_admin)->toBeTrue();
    expect($admin->role)->toBe(UserRole::ADMIN);
});

test('user with user role has is_admin false', function () {
    $user = new User(['role' => 'user']);

    expect($user->is_admin)->toBeFalse();
    expect($user->role)->toBe(UserRole::USER);
});

test('user with null role has is_admin false', function () {
    $user = new User(['role' => null]);

    expect($user->is_admin)->toBeFalse();
});
