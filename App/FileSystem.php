<?php
namespace App;

/**
 * Interface FileSystem
 * Filesystem operation abstraction layer
 * 
 * Benefits:
 * - Decouples code from direct filesystem operations
 * - Enables mocking in tests
 * - Centralizes file operation error handling
 * - Allows different implementations (local, remote, etc)
 */
interface FileSystem {
    /**
     * Check if path is a directory
     * @param string $path Directory path to check
     * @return bool True if path exists and is a directory
     */
    public function isDir(string $path): bool;
    
    /**
     * Create a directory
     * @param string $path Directory path to create
     * @return bool True if directory was created or already exists
     */
    public function mkdir(string $path): bool;
    
    /**
     * Check if path is writable
     * @param string $path Path to check
     * @return bool True if path is writable
     */
    public function isWritable(string $path): bool;
    
    /**
     * Open a file handle
     * @param string $filename File path
     * @param string $mode File open mode (r/w/a etc)
     * @return resource|false File handle or false on failure
     */
    public function fopen(string $filename, string $mode);
    
    /**
     * Write CSV data to file handle
     * @param resource $handle File handle from fopen
     * @param array $data Data array to write as CSV
     * @return bool True on success
     */
    public function fputcsv($handle, array $data): bool;
    
    /**
     * Flush file handle buffers
     * @param resource $handle File handle
     * @return bool True on success
     */
    public function fflush($handle): bool;
    
    /**
     * Close file handle
     * @param resource $handle File handle to close
     * @return bool True on success
     */
    public function fclose($handle): bool;
}