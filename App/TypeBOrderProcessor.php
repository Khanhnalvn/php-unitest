<?php
namespace App;

/**
 * Class TypeBOrderProcessor
 * Processes Type B orders using an API integration
 * 
 * Business Logic:
 * - Validates order data
 * - Sets priority based on amount
 * - Calls external API to process order
 * - Updates order status based on API response and business rules
 */
class TypeBOrderProcessor implements OrderProcessor {
    private APIClient $apiClient;

    /**
     * Constructor
     * @param APIClient|null $apiClient Optional API client instance
     */
    public function __construct(?APIClient $apiClient = null) {
        $this->apiClient = $apiClient ?: new APIClient();
    }

    /**
     * Process a Type B order
     * 
     * Status Rules:
     * - pending: if flag is true or API data < 50
     * - processed: if API data >= 50 and amount < 100
     * - error: if API data >= 50 and amount >= 100
     * - api_error: if API returns error status or invalid data
     * - api_failure: if API throws exception
     * 
     * @param Order $order The order to process
     * @throws APIException If API call fails
     * @throws \InvalidArgumentException If order data is invalid
     */
    public function process(Order $order): void {
        $this->validateOrder($order);
        $this->setPriority($order);

        try {
            $response = $this->apiClient->callAPI($order->id);
            if (!isset($response->data) || !is_numeric($response->data)) {
                $order->status = 'api_error';
                throw new \InvalidArgumentException("Invalid API response data type");
            }

            // Store response data
            $order->apiResponse = ['value' => $response->data];

            if ($response->status === 'success') {
                if ($order->flag === true) {
                    $order->status = 'pending';
                } elseif ($response->data >= 50 && $order->amount < 100) {
                    $order->status = 'processed';
                    $order->priority = $order->amount > 200 ? 'high' : 'low';
                    $order->processedAt = (new \DateTime())->format('Y-m-d H:i:s');
                } elseif ($response->data < 50) {
                    $order->status = 'pending';
                } else {
                    $order->status = 'error';
                }
            } else {
                $order->status = 'api_error';
            }
        } catch (APIException $e) {
            $order->status = 'api_failure';
            throw $e;
        }
    }

    /**
     * Validate order data integrity
     * @param Order $order Order to validate
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateOrder(Order $order): void {
        if (!isset($order->id) || !isset($order->amount)) {
            throw new \InvalidArgumentException("Invalid order data");
        }
        if ($order->id <= 0) {
            throw new \InvalidArgumentException("Order ID must be positive");
        }
    }

    /**
     * Set order priority based on amount
     * - High priority if amount > 200
     * - Low priority otherwise
     * @param Order $order Order to update priority
     */
    private function setPriority(Order $order): void {
        if ($order->amount > 200) {
            $order->priority = 'high';
        } else {
            $order->priority = 'low';
        }
    }
}