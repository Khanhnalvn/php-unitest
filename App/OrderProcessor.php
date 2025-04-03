<?php
namespace App;

/**
 * Interface OrderProcessor
 * Base interface for all order processors in the system
 * 
 * Each processor type (A/B/C) implements this interface to provide:
 * - Consistent process() method signature
 * - Common exception handling patterns
 * - Standard order status management
 */
interface OrderProcessor {
    /**
     * Process a single order
     * 
     * Implementation Requirements:
     * - Must validate order data before processing
     * - Must update order status after processing
     * - Must handle type-specific business rules
     * - Must throw appropriate exceptions for failures
     * 
     * @param Order $order The order to process
     * @throws \InvalidArgumentException If order data is invalid
     * @throws APIException|FileOperationException|DatabaseException For specific failures
     */
    public function process(Order $order): void;
}