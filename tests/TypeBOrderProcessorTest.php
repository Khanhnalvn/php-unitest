<?php

use App\Order;
use App\TypeBOrderProcessor;
use App\APIClient;
use App\APIException;
use App\APIResponse;

beforeEach(function() {
    $this->apiClient = Mockery::mock(APIClient::class);
    $this->processor = new TypeBOrderProcessor($this->apiClient);
});

afterEach(function() {
    Mockery::close();
});

it('processes order successfully when data >= 50 and amount < 100', function() {
    $order = new Order(1, 'B', 80, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->once()
        ->andReturn(new APIResponse('success', 60));
    
    $this->processor->process($order);
    expect($order->status)->toBe('processed');
    expect($order->apiResponse)->toBe(['value' => 60]);
});

it('sets status to pending when data < 50', function() {
    $order = new Order(2, 'B', 120, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(2)
        ->once()
        ->andReturn(new APIResponse('success', 40));
    
    $this->processor->process($order);
    expect($order->status)->toBe('pending');
    expect($order->apiResponse)->toBe(['value' => 40]);
});

it('sets status to pending when flag is true regardless of data', function() {
    $order = new Order(3, 'B', 80, true);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(3)
        ->once()
        ->andReturn(new APIResponse('success', 60));
    
    $this->processor->process($order);
    expect($order->status)->toBe('pending');
    expect($order->apiResponse)->toBe(['value' => 60]);
});

it('sets status to error when data >= 50 and amount >= 100', function() {
    $order = new Order(4, 'B', 100, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(4)
        ->once()
        ->andReturn(new APIResponse('success', 50));
    
    $this->processor->process($order);
    expect($order->status)->toBe('error');
    expect($order->apiResponse)->toBe(['value' => 50]);
});

it('handles API error response', function() {
    $order = new Order(5, 'B', 100, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(5)
        ->once()
        ->andReturn(new APIResponse('error', 42));
    
    $this->processor->process($order);
    expect($order->status)->toBe('api_error');
});

it('handles API exception', function() {
    $order = new Order(6, 'B', 100, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(6)
        ->once()
        ->andThrow(new APIException('API Error'));
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(APIException::class);
    expect($order->status)->toBe('api_failure');
});

it('validates order ID is positive', function() {
    $order = new Order(0, 'B', 100, false);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(\InvalidArgumentException::class, 'Order ID must be positive');
});

it('validates API response data type', function() {
    $order = new Order(9, 'B', 100, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->with(9)
        ->once()
        ->andReturn(new APIResponse('success', 'invalid'));
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(\InvalidArgumentException::class, 'Invalid API response data type');
    expect($order->status)->toBe('api_error');
});

it('handles null response data from API', function() {
    $order = new Order(1, 'B', 80, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->once()
        ->with(1)
        ->andReturn(new APIResponse('success', null));
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(\InvalidArgumentException::class, 'Invalid API response data type');
    expect($order->status)->toBe('api_error');
});

it('handles negative response data', function() {
    $order = new Order(1, 'B', 80, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->once()
        ->with(1)
        ->andReturn(new APIResponse('success', -10));
    
    $this->processor->process($order);
    expect($order->status)->toBe('pending');
});

it('handles large integer response data', function() {
    $order = new Order(1, 'B', 80, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->once()
        ->with(1)
        ->andReturn(new APIResponse('success', PHP_INT_MAX));
    
    $this->processor->process($order);
    expect($order->status)->toBe('processed');
});

it('handles API timeout', function() {
    $order = new Order(1, 'B', 80, false);
    
    $this->apiClient->shouldReceive('callAPI')
        ->once()
        ->with(1)
        ->andThrow(new APIException('API request timed out', 408));
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(APIException::class);
    expect($order->status)->toBe('api_failure');
});

it('maintains order notes during API processing', function() {
    $order = new Order(1, 'B', 80, false);
    $order->notes = 'Test notes';
    
    $this->apiClient->shouldReceive('callAPI')
        ->once()
        ->with(1)
        ->andReturn(new APIResponse('success', 60));
    
    $this->processor->process($order);
    expect($order->notes)->toBe('Test notes');
});

it('should throw exception for invalid order data', function() {
    $invalidOrders = [
        new Order(null, 'B', 100, false),
        new Order(1, 'B', null, false)
    ];
    
    foreach ($invalidOrders as $order) {
        expect(function() use ($order) {
            $this->processor->process($order);
        })->toThrow(\InvalidArgumentException::class, 'Invalid order data');
    }
});

it('should set high priority for orders over 200', function() {
    $order = new Order(1, 'B', 201, false);

    $this->apiClient->shouldReceive('callAPI')
        ->once()
        ->with(1)
        ->andReturn(new APIResponse('success', 60));
        
    $this->processor->process($order);
    expect($order->priority)->toBe('high');
});