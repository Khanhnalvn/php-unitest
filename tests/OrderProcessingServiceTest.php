<?php

use App\Order;
use App\APIClient;
use App\DatabaseService;
use App\OrderProcessorFactory;
use App\OrderProcessingService;
use App\TypeAOrderProcessor;
use App\TypeBOrderProcessor;
use App\TypeCOrderProcessor;
use App\APIException;
use App\DatabaseException;
use App\FileOperationException;

beforeEach(function() {
    $this->dbService = Mockery::mock(DatabaseService::class);
    $this->processorFactory = Mockery::mock(OrderProcessorFactory::class);
    $this->service = new OrderProcessingService($this->processorFactory, $this->dbService);
    $this->typeAProcessor = Mockery::mock(TypeAOrderProcessor::class);
    $this->typeBProcessor = Mockery::mock(TypeBOrderProcessor::class);
    $this->typeCProcessor = Mockery::mock(TypeCOrderProcessor::class);
});

afterEach(function() {
    Mockery::close();
});

it('processes type A order with successful CSV export', function() {
    $order = new Order(1, 'A', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnNull();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, $order->status, 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0])->toBe($order);
});

it('verifies processing order sequence with order IDs', function() {
    $orderSequence = [];
    $orders = [
        new Order(1, 'A', 100, false),
        new Order(2, 'B', 150, true),
        new Order(3, 'C', 200, false)
    ];

    $this->dbService->shouldReceive('getOrdersByUser')->once()->with(1)
        ->andReturn($orders);

    $this->processorFactory->shouldReceive('createProcessor')
        ->andReturnUsing(function($type) use (&$orderSequence) {
            $processor = Mockery::mock('App\\Type' . $type . 'OrderProcessor');
            $processor->shouldReceive('process')->once()
                ->andReturnUsing(function($order) use (&$orderSequence) {
                    $orderSequence[] = $order->id;
                });
            return $processor;
        });

    $this->dbService->shouldReceive('updateOrderStatus');

    $this->service->processOrders(1);
    expect($orderSequence)->toBe([1, 2, 3]);
});

it('processes type C order and sets status to in_progress when flag is false', function() {
    $order = new Order(3, 'C', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('C')
        ->once()
        ->andReturn($this->typeCProcessor);

    $this->typeCProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'in_progress';
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'in_progress', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0])->toBe($order);
});

it('sets high priority for orders with amount greater than 200', function() {
    $order = new Order(4, 'A', 250, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            $order->priority = 'high';
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, $order->status, 'high')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->priority)->toBe('high');
});

it('handles API exceptions gracefully', function() {
    $order = new Order(5, 'B', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('B')
        ->once()
        ->andReturn($this->typeBProcessor);

    $this->typeBProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andThrow(new APIException('API Error'));

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'api_error', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('api_error');
});

it('handles database exceptions', function() {
    $order = new Order(6, 'A', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, $order->status, 'low')
        ->once()
        ->andThrow(new DatabaseException('Database Error'));

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
});

it('handles file operation exceptions', function() {
    $order = new Order(7, 'A', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'export_failed';
            throw new FileOperationException('File Error');
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'export_failed', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('export_failed');
});

it('handles invalid order types', function() {
    $order = new Order(8, 'A', 100, false);
    $order->type = 'INVALID'; // Set invalid type after construction to avoid constructor validation
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('INVALID')
        ->once()
        ->andThrow(new \InvalidArgumentException('Invalid order type'));

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'unknown_type', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('unknown_type');
});

it('verifies behavior with empty order list', function() {
    $this->dbService->shouldReceive('getOrdersByUser')
        ->once()
        ->with(1)
        ->andReturn([]);

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

it('handles negative amounts', function() {
    $order = new Order(9, 'A', 100, false);
    $reflection = new ReflectionProperty($order, 'amount');
    $reflection->setValue($order, -100); // Set negative amount after construction

    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            throw new \InvalidArgumentException('Invalid amount');
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'unknown_type', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('unknown_type');
});

it('handles database errors in saveOrder', function() {
    $order = new Order(1, 'A', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, $order->status, 'low')
        ->once()
        ->andThrow(new DatabaseException('Failed to update order status'));

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
});

it('successfully saves order status', function() {
    $order = new Order(1, 'A', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'processed';
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'processed', 'low')
        ->once()
        ->andReturn();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('processed');
});

it('handles multiple orders with database updates', function() {
    $order1 = new Order(1, 'A', 100, false);
    $order1->status = 'pending';
    
    $order2 = new Order(2, 'B', 150, true);
    $order2->status = 'new';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order1, $order2]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('B')
        ->once()
        ->andReturn($this->typeBProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order1)
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'processed';
        });

    $this->typeBProcessor->shouldReceive('process')
        ->with($order2)
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'api_success';
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order1->id, 'processed', 'low')
        ->once();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order2->id, 'api_success', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]->status)->toBe('processed');
    expect($result[1]->status)->toBe('api_success');
});

it('handles database update with retried orders', function() {
    $order = new Order(1, 'A', 100, false);
    $order->status = 'retry';

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'success';
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'success', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('success');
});

it('handles initial database query failure', function() {
    // Mock DatabaseService to throw exception on getOrdersByUser
    $this->dbService->shouldReceive('getOrdersByUser')
        ->once()
        ->andThrow(new DatabaseException('Database connection failed'));

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

it('handles processor creation failure', function() {
    $order = new Order(1, 'X', 100, false);
    
    // Mock successful database query
    $this->dbService->shouldReceive('getOrdersByUser')
        ->once()
        ->andReturn([$order]);

    // Mock factory to throw exception
    $this->processorFactory->shouldReceive('createProcessor')
        ->with('X')
        ->once()
        ->andThrow(new \InvalidArgumentException('Invalid processor type'));

    // Should still try to save order with unknown_type status
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'unknown_type', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('unknown_type');
});

it('handles processor execution failure', function() {
    $order = new Order(1, 'A', 100, false);
    $order->status = 'pending';
    
    $this->dbService->shouldReceive('getOrdersByUser')
        ->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    // Mock processor to throw exception and set status
    $this->typeAProcessor->shouldReceive('process')
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'export_failed';
            throw new FileOperationException('File write failed');
        });

    // Should try to update order with the failed status
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'export_failed', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('export_failed');
});

it('handles multiple exceptions in batch processing', function() {
    $order1 = new Order(1, 'A', 100, false);
    $order1->status = 'pending';
    
    $order2 = new Order(2, 'B', 150, true);
    $order2->status = 'pending';
    
    $this->dbService->shouldReceive('getOrdersByUser')
        ->once()
        ->andReturn([$order1, $order2]);

    // First processor throws exception
    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'export_failed';
            throw new FileOperationException('File write failed');
        });

    // Second processor works fine
    $this->processorFactory->shouldReceive('createProcessor')
        ->with('B')
        ->once()
        ->andReturn($this->typeBProcessor);

    $this->typeBProcessor->shouldReceive('process')
        ->once()
        ->andReturnUsing(function($order) {
            $order->status = 'processed';
        });

    // Both orders should be saved with their respective statuses
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order1->id, 'export_failed', 'low')
        ->once();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order2->id, 'processed', 'low')
        ->once();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]->status)->toBe('export_failed');
    expect($result[1]->status)->toBe('processed');
});

it('handles order with null amount', function() {
    $order = new Order(1, 'A', null, false);
    $order->status = 'pending';
    
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order]);
    
    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->andReturn($this->typeAProcessor);
    
    $this->typeAProcessor->shouldReceive('process')
        ->with($order)
        ->once()
        ->andReturnUsing(function($order) {
            throw new \InvalidArgumentException('Invalid amount');
        });
    
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order->id, 'unknown_type', 'low')
        ->once();
    
    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]->status)->toBe('unknown_type');
});

it('processes orders in correct sequence', function() {
    $order1 = new Order(1, 'A', 100, false);
    $order2 = new Order(2, 'B', 150, true);
    $order3 = new Order(3, 'C', 201, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->once()
        ->andReturn([$order1, $order2, $order3]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')
        ->once()
        ->ordered()
        ->andReturn($this->typeAProcessor);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('B')
        ->once()
        ->ordered()
        ->andReturn($this->typeBProcessor);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('C')
        ->once()
        ->ordered()
        ->andReturn($this->typeCProcessor);

    $this->typeAProcessor->shouldReceive('process')
        ->with($order1)
        ->once()
        ->ordered();

    $this->typeBProcessor->shouldReceive('process')
        ->with($order2)
        ->once()
        ->ordered();

    $this->typeCProcessor->shouldReceive('process')
        ->with($order3)
        ->once()
        ->ordered()
        ->andReturnUsing(function($order) {
            $order->priority = 'high';
        });

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order1->id, $order1->status, 'low')
        ->once()
        ->ordered();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order2->id, $order2->status, 'low')
        ->once()
        ->ordered();

    $this->dbService->shouldReceive('updateOrderStatus')
        ->with($order3->id, $order3->status, 'high')
        ->once()
        ->ordered();

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(3);
});

it('handles multiple orders with different types', function() {
    $orderA = new Order(1, 'A', 100, false);
    $orderB = new Order(2, 'B', 150, true);
    $orderC = new Order(3, 'C', 200, false);

    $this->dbService->shouldReceive('getOrdersByUser')->once()->with(1)
        ->andReturn([$orderA, $orderB, $orderC]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')->once()->andReturn($this->typeAProcessor);
    
    $this->processorFactory->shouldReceive('createProcessor')
        ->with('B')->once()->andReturn($this->typeBProcessor);
    
    $this->processorFactory->shouldReceive('createProcessor')
        ->with('C')->once()->andReturn($this->typeCProcessor);

    $this->typeAProcessor->shouldReceive('process')->once()->with($orderA);
    $this->typeBProcessor->shouldReceive('process')->once()->with($orderB);
    $this->typeCProcessor->shouldReceive('process')->once()->with($orderC);

    $this->dbService->shouldReceive('updateOrderStatus')->times(3);

    $result = $this->service->processOrders(1);
    expect($result)->toHaveCount(3);
});

it('handles database exception during status update', function() {
    $order = new Order(1, 'A', 100, false);
    $order->status = 'pending';

    $this->dbService->shouldReceive('getOrdersByUser')->once()
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('A')->once()->andReturn($this->typeAProcessor);

    $this->typeAProcessor->shouldReceive('process')->once()->with($order);

    $this->dbService->shouldReceive('updateOrderStatus')->once()
        ->andThrow(new DatabaseException('Update failed'));

    $result = $this->service->processOrders(1);
    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
});

it('handles order with null flag', function() {
    $order = new Order(1, 'C', 100, null);
    
    $this->dbService->shouldReceive('getOrdersByUser')
        ->once()
        ->with(1)
        ->andReturn([$order]);

    $this->processorFactory->shouldReceive('createProcessor')
        ->with('C')
        ->once()
        ->andReturn($this->typeCProcessor);

    $this->typeCProcessor->shouldReceive('process')
        ->once()
        ->with($order);

    $this->dbService->shouldReceive('updateOrderStatus');

    $result = $this->service->processOrders(1);
    expect($result)->toHaveCount(1);
});