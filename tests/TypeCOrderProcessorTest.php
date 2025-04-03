<?php

use App\Order;
use App\TypeCOrderProcessor;

beforeEach(function() {
    $this->processor = new TypeCOrderProcessor();
});

it('should set status to in_progress when flag is false', function() {
    $order = new Order(1, 'C', 100, false);
    $this->processor->process($order);
    expect($order->status)->toBe('in_progress');
});

it('should set status to completed when flag is true', function() {
    $order = new Order(1, 'C', 100, true);
    $this->processor->process($order);
    expect($order->status)->toBe('completed');
});

it('should throw exception for invalid order data', function() {
    $invalidOrders = [
        new Order(null, 'C', 100, false),
        new Order(1, 'C', null, false)
    ];
    
    foreach ($invalidOrders as $order) {
        expect(function() use ($order) {
            $this->processor->process($order);
        })->toThrow(\InvalidArgumentException::class, 'Invalid order data');
    }
});

it('should handle null flag', function() {
    $order = new Order(1, 'C', 100, null);
    $this->processor->process($order);
    expect($order->status)->toBe('in_progress');
});

it('should set high priority for orders over 200', function() {
    $order = new Order(1, 'C', 201, false);
    $this->processor->process($order);
    expect($order->priority)->toBe('high');
});

it('should set low priority for orders under or equal to 200', function() {
    $testCases = [
        ['amount' => 50, 'expectedPriority' => 'low'],
        ['amount' => 200, 'expectedPriority' => 'low']
    ];

    foreach ($testCases as $case) {
        $order = new Order(1, 'C', $case['amount'], false);
        $this->processor->process($order);
        expect($order->priority)->toBe($case['expectedPriority']);
    }
});

it('should maintain order notes during processing', function() {
    $order = new Order(1, 'C', 100, false);
    $order->notes = 'Test notes';
    $this->processor->process($order);
    expect($order->notes)->toBe('Test notes');
});

it('should handle transitions between statuses', function() {
    $order = new Order(1, 'C', 100, false);
    
    // Test pending to in_progress
    $order->status = 'pending';
    $this->processor->process($order);
    expect($order->status)->toBe('in_progress');
    
    // Test in_progress to completed
    $order->flag = true;
    $this->processor->process($order);
    expect($order->status)->toBe('completed');
    
    // Test maintaining completed status
    $this->processor->process($order);
    expect($order->status)->toBe('completed');
});

it('should validate flag is boolean', function() {
    $order = new Order(1, 'C', 100, 'non-boolean');
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(\InvalidArgumentException::class, 'Flag must be boolean');
});