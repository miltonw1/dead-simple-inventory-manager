<?php

use App\Models\Brand;
use App\Models\User;
use App\Policies\BrandPolicy;

beforeEach(function () {
    $this->policy = new BrandPolicy;
    $this->admin = new User(['id' => 99, 'role' => 'admin']);
    $this->user = new User(['id' => 1, 'role' => 'user']);
    $this->anotherUser = new User(['id' => 2, 'role' => 'user']);

    $this->user->id = 1;
    $this->anotherUser->id = 2;

    $this->ownBrand = new Brand(['user_id' => 1]);
    $this->ownBrand->user_id = 1;

    $this->otherBrand = new Brand(['user_id' => 2]);
    $this->otherBrand->user_id = 2;
});

// viewAny tests
test('any authenticated user can view any brands', function () {
    expect($this->policy->viewAny())->toBeTrue();
});

// view tests
test('admin can view any brand', function () {
    expect($this->policy->view($this->admin, $this->ownBrand))->toBeTrue();
    expect($this->policy->view($this->admin, $this->otherBrand))->toBeTrue();
});

test('user can view own brand', function () {
    expect($this->policy->view($this->user, $this->ownBrand))->toBeTrue();
});

test('user cannot view other users brand', function () {
    expect($this->policy->view($this->user, $this->otherBrand))->toBeFalse();
});

// create tests
test('any authenticated user can create brand', function () {
    expect($this->policy->create())->toBeTrue();
});

// update tests
test('admin can update any brand', function () {
    expect($this->policy->update($this->admin, $this->ownBrand))->toBeTrue();
    expect($this->policy->update($this->admin, $this->otherBrand))->toBeTrue();
});

test('user can update own brand', function () {
    expect($this->policy->update($this->user, $this->ownBrand))->toBeTrue();
});

test('user cannot update other users brand', function () {
    expect($this->policy->update($this->user, $this->otherBrand))->toBeFalse();
});

// delete tests
test('admin can delete any brand', function () {
    expect($this->policy->delete($this->admin, $this->ownBrand))->toBeTrue();
    expect($this->policy->delete($this->admin, $this->otherBrand))->toBeTrue();
});

test('user can delete own brand', function () {
    expect($this->policy->delete($this->user, $this->ownBrand))->toBeTrue();
});

test('user cannot delete other users brand', function () {
    expect($this->policy->delete($this->user, $this->otherBrand))->toBeFalse();
});

// restore tests
test('admin can restore brand', function () {
    expect($this->policy->restore($this->admin))->toBeTrue();
});

test('user cannot restore brand', function () {
    expect($this->policy->restore($this->user))->toBeFalse();
});

// forceDelete tests
test('admin can force delete brand', function () {
    expect($this->policy->forceDelete($this->admin, $this->ownBrand))->toBeTrue();
});

test('user cannot force delete brand', function () {
    expect($this->policy->forceDelete($this->user, $this->ownBrand))->toBeFalse();
});
