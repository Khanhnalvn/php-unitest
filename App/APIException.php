<?php
namespace App;

/**
 * Class APIException
 * Exception thrown when API operations fail
 * 
 * Used in:
 * - TypeBOrderProcessor when API calls fail
 * - Includes HTTP status code for specific error cases
 * - Helps distinguish API failures from other errors
 */
class APIException extends \Exception {
    private ?int $statusCode;

    public function __construct(string $message = "", int $statusCode = null, \Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int {
        return $this->statusCode;
    }
}
