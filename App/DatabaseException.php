<?php
namespace App;

/**
 * Class DatabaseException
 * Exception thrown for database operation failures
 * 
 * Used in:
 * - DatabaseService for query/transaction failures
 * - OrderProcessingService handles these to set db_error status
 * - Contains query context when available
 */
class DatabaseException extends \Exception {
    private ?string $query;

    public function __construct(string $message = "", string $query = null, \Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->query = $query;
    }

    public function getQuery(): ?string {
        return $this->query;
    }
}
