<?php

use App\APIResponse;

it('should create API response with status and data', function() {
    $response = new APIResponse('success', 42);
    expect($response->status)->toBe('success');
    expect($response->data)->toBe(42);
});

it('should create API response with null values', function() {
    $response = new APIResponse();
    expect($response->status)->toBeNull();
    expect($response->data)->toBeNull();
});

it('should create API response with only status', function() {
    $response = new APIResponse('error');
    expect($response->status)->toBe('error');
    expect($response->data)->toBeNull();
});

it('should create API response with only data', function() {
    $response = new APIResponse(null, ['key' => 'value']);
    expect($response->status)->toBeNull();
    expect($response->data)->toBe(['key' => 'value']);
});