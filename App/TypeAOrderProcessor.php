<?php
namespace App;

/**
 * Class TypeAOrderProcessor
 * Processes Type A orders by exporting them to CSV files
 * 
 * Business Logic:
 * - Validates order data (numeric id and amount)
 * - Sets priority based on amount threshold
 * - Exports order data to CSV file with headers and notes
 * - Updates order status and export timestamp
 */
class TypeAOrderProcessor implements OrderProcessor {
    private const CSV_HEADERS = ['ID', 'Type', 'Amount', 'Flag', 'Status', 'Priority', 'Notes'];
    private string $outputDirectory;
    private FileSystem $fileSystem;

    /**
     * Constructor
     * @param FileSystem|null $fileSystem Optional filesystem implementation
     * @param string $outputDirectory Directory to store CSV files
     */
    public function __construct(?FileSystem $fileSystem = null, string $outputDirectory = '/tmp/orders') {
        $this->fileSystem = $fileSystem ?: new RealFileSystem();
        $this->outputDirectory = $outputDirectory;
    }

    /**
     * Process a Type A order by exporting to CSV
     * 
     * Flow:
     * 1. Validate order data
     * 2. Set priority based on amount
     * 3. Create output directory if needed
     * 4. Generate unique filename
     * 5. Write order data to CSV
     * 6. Update order status and timestamp
     * 
     * @param Order $order The order to process
     * @throws FileOperationException If file operations fail
     * @throws \InvalidArgumentException If order data is invalid
     */
    public function process(Order $order): void {
        $this->validateOrder($order);
        $this->setPriority($order);
        
        try {
            $this->ensureDirectoryExists();
            $filename = $this->generateFilename($order);
            $this->writeOrderToCsv($order, $filename);
            $order->status = 'exported';
            $order->priority = $order->amount > 200 ? 'high' : 'low';
            $order->exportedAt = (new \DateTime())->format('Y-m-d H:i:s');
        } catch (FileOperationException $e) {
            $order->status = 'export_failed';
            throw $e;
        }
    }

    /**
     * Validate numeric fields in order
     * @throws \InvalidArgumentException If validation fails
     */
    private function validateOrder(Order $order): void {
        if (!is_numeric($order->id) || !is_numeric($order->amount)) {
            throw new \InvalidArgumentException('Invalid order data');
        }
    }

    /**
     * Set order priority based on amount threshold
     * High: amount > 200
     * Low: amount <= 200
     */
    private function setPriority(Order $order): void {
        if ($order->amount > 200) {
            $order->priority = 'high';
        } else {
            $order->priority = 'low';
        }
    }

    /**
     * Ensure output directory exists and is writable
     * @throws FileOperationException If directory cannot be created/accessed
     */
    private function ensureDirectoryExists(): void {
        if (!$this->fileSystem->isDir($this->outputDirectory)) {
            if (!$this->fileSystem->mkdir($this->outputDirectory)) {
                throw new FileOperationException('Cannot create output directory');
            }
        }
        if (!$this->fileSystem->isWritable($this->outputDirectory)) {
            throw new FileOperationException('Cannot create output directory');
        }
    }

    /**
     * Generate unique CSV filename for order
     * Format: orders_type_A_[safe_id]_[timestamp].csv
     */
    private function generateFilename(Order $order): string {
        $safeId = preg_replace('/[^a-zA-Z0-9-]/', '_', (string)$order->id);
        $filename = sprintf('orders_type_A_%s_%d.csv', $safeId, time());
        return $this->outputDirectory . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Write order data to CSV file with headers
     * @throws FileOperationException If file operations fail
     */
    private function writeOrderToCsv(Order $order, string $filename): void {
        $handle = $this->fileSystem->fopen($filename, 'w');
        if (!$handle) {
            throw new FileOperationException('Cannot open file for writing');
        }

        try {
            if ($this->fileSystem->fputcsv($handle, self::CSV_HEADERS) === false) {
                throw new FileOperationException('Failed to write CSV headers');
            }

            $notes = $order->amount > 150 ? 'High value order' : '';
            if (!empty($order->notes)) {
                $notes = $order->notes;
            }

            $data = [
                $order->id,
                $order->type,
                $order->amount,
                $order->flag ? 'true' : 'false',
                $order->status,
                $order->priority,
                $notes
            ];

            if ($this->fileSystem->fputcsv($handle, $data) === false) {
                throw new FileOperationException('Failed to write CSV data');
            }

            if (!$this->fileSystem->fflush($handle)) {
                throw new FileOperationException('Failed to flush CSV data to disk');
            }
        } finally {
            $this->fileSystem->fclose($handle);
        }
    }
}