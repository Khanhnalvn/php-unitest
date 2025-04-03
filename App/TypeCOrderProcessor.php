<?php
namespace App;

/**
 * Class TypeCOrderProcessor
 * Processes Type C orders with simple in-memory operations
 * 
 * Business Logic:
 * - Performs basic validation
 * - Applies standard processing rules
 * - No external dependencies or I/O operations
 * - Suitable for internal/system orders
 */
class TypeCOrderProcessor implements OrderProcessor {
    
    /**
     * Process a Type C order
     * 
     * Rules:
     * - Sets status to 'processed' for valid orders
     * - Sets priority based on amount threshold
     * - No external API calls or file operations
     * 
     * @param Order $order Order to process
     * @throws \InvalidArgumentException If order data is invalid
     */
    public function process(Order $order): void {
        $this->validateOrder($order);
        $this->setPriority($order);

        if ($order->flag === true) {
            $order->status = 'completed';
            $order->completedAt = (new \DateTime())->format('Y-m-d H:i:s');
        } else {
            $order->status = 'in_progress';
            $order->processedAt = (new \DateTime())->format('Y-m-d H:i:s');
        }
    }
    
    /**
     * Basic order validation
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateOrder(Order $order): void {
        if (is_null($order->id) || is_null($order->amount)) {
            throw new \InvalidArgumentException('Invalid order data');
        }
        if (!is_null($order->flag) && !is_bool($order->flag)) {
            throw new \InvalidArgumentException('Flag must be boolean');
        }
    }

    /**
     * Set order priority based on amount
     * - High priority if amount > 200
     * - Low priority otherwise
     */
    private function setPriority(Order $order): void {
        if ($order->amount > 200) {
            $order->priority = 'high';
        } else {
            $order->priority = 'low';
        }
    }
}
