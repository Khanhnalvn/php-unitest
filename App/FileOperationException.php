<?php
namespace App;

/**
 * Class FileOperationException
 * Exception thrown for filesystem operation failures
 * 
 * Used in:
 * - TypeAOrderProcessor for CSV file operations
 * - RealFileSystem implementation
 * - Includes path information when relevant
 */
class FileOperationException extends \Exception {
    private ?string $path;

    public function __construct(string $message = "", string $path = null, \Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->path = $path;
    }

    public function getPath(): ?string {
        return $this->path;
    }
}