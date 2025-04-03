<?php
namespace App;

/**
 * Interface OrderProcessorFactoryInterface
 * Factory interface for creating order processors
 * 
 * Provides abstraction for processor creation to allow:
 * - Different factory implementations
 * - Dependency injection in services
 * - Mock factories in tests
 */
interface OrderProcessorFactoryInterface {
    /**
     * Create an order processor for the given type
     *
     * @param string $type Order type (A/B/C)
     * @return OrderProcessor The appropriate processor instance
     * @throws \InvalidArgumentException If type is invalid
     */
    public function createProcessor(string $type): OrderProcessor;
}