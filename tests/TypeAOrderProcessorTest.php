<?php

use App\Order;
use App\TypeAOrderProcessor;
use App\FileSystem;
use App\FileOperationException;

beforeEach(function() {
    $this->fileSystem = Mockery::mock(FileSystem::class);
    $this->processor = new TypeAOrderProcessor($this->fileSystem, '/tmp/exports');
});

afterEach(function() {
    Mockery::close();
});

it('should successfully export order to CSV', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->once()
        ->andReturn(true);

    $this->fileSystem->shouldReceive('isWritable')
        ->once()
        ->andReturn(true);

    $this->fileSystem->shouldReceive('fopen')
        ->once()
        ->andReturn(fopen('php://memory', 'w+'));

    $this->fileSystem->shouldReceive('fputcsv')
        ->twice()
        ->andReturn(10); // Return some positive number indicating success

    $this->fileSystem->shouldReceive('fflush')
        ->once()
        ->andReturn(true);

    $this->fileSystem->shouldReceive('fclose')
        ->once()
        ->andReturn(true);

    $this->processor->process($order);
    expect($order->status)->toBe('exported');
    expect($order->exportedAt)->not()->toBeNull();
});

it('should set high priority for orders over 200', function() {
    $order = new Order(1, 'A', 201, false);
    mockFileSystemSuccess($this->fileSystem);
    
    $this->processor->process($order);
    expect($order->priority)->toBe('high');
});

it('should set low priority for orders under or equal to 200', function() {
    $order = new Order(1, 'A', 200, false);
    mockFileSystemSuccess($this->fileSystem);
    
    $this->processor->process($order);
    expect($order->priority)->toBe('low');
});

it('should handle directory creation when it does not exist', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->once()
        ->andReturn(false);
    
    $this->fileSystem->shouldReceive('mkdir')
        ->once()
        ->andReturn(true);
        
    $this->fileSystem->shouldReceive('isWritable')
        ->once()
        ->andReturn(true);
        
    mockFileOperations($this->fileSystem);
    
    $this->processor->process($order);
    expect($order->status)->toBe('exported');
});

it('should throw exception when directory creation fails', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->once()
        ->andReturn(false);
    
    $this->fileSystem->shouldReceive('mkdir')
        ->once()
        ->andReturn(false);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(FileOperationException::class, 'Cannot create output directory');
    expect($order->status)->toBe('export_failed');
});

it('should throw exception when directory is not writable', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->once()
        ->andReturn(true);
    
    $this->fileSystem->shouldReceive('isWritable')
        ->once()
        ->andReturn(false);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(FileOperationException::class, 'Cannot create output directory');
    expect($order->status)->toBe('export_failed');
});

it('should handle file open failure', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->once()
        ->andReturn(true);
        
    $this->fileSystem->shouldReceive('isWritable')
        ->once()
        ->andReturn(true);
        
    $this->fileSystem->shouldReceive('fopen')
        ->once()
        ->andReturn(false);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(FileOperationException::class, 'Cannot open file for writing');
    expect($order->status)->toBe('export_failed');
});

it('should handle CSV headers write failure', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->andReturn(true);
    $this->fileSystem->shouldReceive('isWritable')
        ->andReturn(true);
    $this->fileSystem->shouldReceive('fopen')
        ->andReturn(fopen('php://memory', 'w+'));
    $this->fileSystem->shouldReceive('fputcsv')
        ->once()
        ->andReturn(false);
    $this->fileSystem->shouldReceive('fclose')
        ->once()
        ->andReturn(true);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(FileOperationException::class, 'Failed to write CSV headers');
    expect($order->status)->toBe('export_failed');
});

it('should handle CSV data write failure', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->andReturn(true);
    $this->fileSystem->shouldReceive('isWritable')
        ->andReturn(true);
    $this->fileSystem->shouldReceive('fopen')
        ->andReturn(fopen('php://memory', 'w+'));
    $this->fileSystem->shouldReceive('fputcsv')
        ->once()
        ->andReturn(10)
        ->ordered();
    $this->fileSystem->shouldReceive('fputcsv')
        ->once()
        ->andReturn(false)
        ->ordered();
    $this->fileSystem->shouldReceive('fclose')
        ->once()
        ->andReturn(true);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(FileOperationException::class, 'Failed to write CSV data');
    expect($order->status)->toBe('export_failed');
});

it('should handle flush failure', function() {
    $order = new Order(1, 'A', 100, false);
    
    $this->fileSystem->shouldReceive('isDir')
        ->andReturn(true);
    $this->fileSystem->shouldReceive('isWritable')
        ->andReturn(true);
    $this->fileSystem->shouldReceive('fopen')
        ->andReturn(fopen('php://memory', 'w+'));
    $this->fileSystem->shouldReceive('fputcsv')
        ->twice()
        ->andReturn(10);
    $this->fileSystem->shouldReceive('fflush')
        ->once()
        ->andReturn(false);
    $this->fileSystem->shouldReceive('fclose')
        ->once()
        ->andReturn(true);
    
    expect(function() use ($order) {
        $this->processor->process($order);
    })->toThrow(FileOperationException::class, 'Failed to flush CSV data to disk');
    expect($order->status)->toBe('export_failed');
});

it('should include order notes in CSV export', function() {
    $order = new Order(1, 'A', 100, false);
    $order->notes = 'Test note';
    
    mockFileSystemSuccess($this->fileSystem);
    
    $this->processor->process($order);
    expect($order->status)->toBe('exported');
});

it('should validate order data', function() {
    $invalidOrders = [
        new Order(null, 'A', 100, false),
        new Order(1, 'A', null, false)
    ];
    
    foreach ($invalidOrders as $order) {
        expect(function() use ($order) {
            $this->processor->process($order);
        })->toThrow(\InvalidArgumentException::class, 'Invalid order data');
    }
});

// Helper functions
function mockFileSystemSuccess($fileSystem) {
    $fileSystem->shouldReceive('isDir')->andReturn(true);
    $fileSystem->shouldReceive('isWritable')->andReturn(true);
    $fileSystem->shouldReceive('fopen')->andReturn(fopen('php://memory', 'w+'));
    $fileSystem->shouldReceive('fputcsv')->andReturn(10);
    $fileSystem->shouldReceive('fflush')->andReturn(true);
    $fileSystem->shouldReceive('fclose')->andReturn(true);
}

function mockFileOperations($fileSystem) {
    $fileSystem->shouldReceive('fopen')->andReturn(fopen('php://memory', 'w+'));
    $fileSystem->shouldReceive('fputcsv')->andReturn(10);
    $fileSystem->shouldReceive('fflush')->andReturn(true);
    $fileSystem->shouldReceive('fclose')->andReturn(true);
}
