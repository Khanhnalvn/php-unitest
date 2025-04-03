<?php

use App\OrderProcessorFactory;
use App\APIClient;
use App\FileSystem;
use App\TypeAOrderProcessor;
use App\TypeBOrderProcessor;
use App\TypeCOrderProcessor;

beforeEach(function() {
    $this->apiClient = Mockery::mock(APIClient::class);
    $this->fileSystem = Mockery::mock(FileSystem::class);
    $this->factory = new OrderProcessorFactory($this->apiClient, $this->fileSystem, '/tmp/test');
});

afterEach(function() {
    Mockery::close();
});

it('creates TypeA processor', function() {
    $processor = $this->factory->createProcessor('A');
    expect($processor)->toBeInstanceOf(TypeAOrderProcessor::class);
});

it('creates TypeB processor', function() {
    $processor = $this->factory->createProcessor('B');
    expect($processor)->toBeInstanceOf(TypeBOrderProcessor::class);
});

it('creates TypeC processor', function() {
    $processor = $this->factory->createProcessor('C');
    expect($processor)->toBeInstanceOf(TypeCOrderProcessor::class);
});

it('throws exception for unknown type', function() {
    expect(function() {
        $this->factory->createProcessor('X');
    })->toThrow(\InvalidArgumentException::class, 'Unknown order type: X');
});