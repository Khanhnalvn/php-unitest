<?php
namespace App;

/**
 * Class OrderProcessingService
 * Main service for processing user orders with error handling and status management
 * 
 * Responsibilities:
 * - Fetches orders from database by user ID
 * - Creates appropriate processor for each order type
 * - Handles processing exceptions and updates order status
 * - Manages database transactions and error states
 */
class OrderProcessingService {
    private DatabaseService $dbService;
    private OrderProcessorFactoryInterface $processorFactory;

    /**
     * OrderProcessingService constructor.
     *
     * @param OrderProcessorFactoryInterface $processorFactory Factory for creating order processors
     * @param DatabaseService $dbService Service for database operations
     */
    public function __construct(
        OrderProcessorFactoryInterface $processorFactory,
        DatabaseService $dbService
    ) {
        $this->processorFactory = $processorFactory;
        $this->dbService = $dbService;
    }

    /**
     * Process all orders for a user with comprehensive error handling
     * 
     * Error States:
     * - api_error: API integration issues
     * - export_failed: File operation failures
     * - unknown_type: Invalid order type
     * - db_error: Database operation failures
     * 
     * @param int $userId User whose orders to process
     * @return array The processed orders with final statuses
     */
    public function processOrders(int $userId): array {
        try {
            $orders = $this->dbService->getOrdersByUser($userId);
            
            foreach ($orders as $order) {
                try {
                    $processor = $this->processorFactory->createProcessor($order->type);
                    
                    try {
                        $processor->process($order);
                    } catch (\Exception $e) {
                        if ($e instanceof APIException) {
                            $order->status = 'api_error';
                        } elseif ($e instanceof FileOperationException) {
                            $order->status = 'export_failed';
                        } elseif ($e instanceof \InvalidArgumentException) {
                            $order->status = 'unknown_type';
                        }
                    }
                    
                    $this->dbService->updateOrderStatus($order->id, $order->status, $order->priority);
                } catch (\InvalidArgumentException $e) {
                    $order->status = 'unknown_type';
                    $this->dbService->updateOrderStatus($order->id, $order->status, 'low');
                } catch (DatabaseException $e) {
                    $order->status = 'db_error';
                }
            }
            
            return $orders;
        } catch (\Exception $e) {
            return [];
        }
    }
}
