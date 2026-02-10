<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password is hashed when creating user', function () {
    $plainPassword = 'my-secure-password';

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => $plainPassword,
        'role' => 'user',
    ]);

    expect($user->password)->not->toBe($plainPassword);
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();
});

test('password is hashed when updating user', function () {
    $user = User::factory()->create([
        'password' => 'old-password',
    ]);

    $newPassword = 'new-secure-password';
    $user->update(['password' => $newPassword]);

    $user->refresh();

    expect($user->password)->not->toBe($newPassword);
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
    expect(Hash::check('old-password', $user->password))->toBeFalse();
});

test('password setter works when directly assigning to property', function () {
    $user = User::factory()->create();

    $newPassword = 'directly-assigned-password';
    $user->password = $newPassword;
    $user->save();

    $user->refresh();

    expect($user->password)->not->toBe($newPassword);
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});
