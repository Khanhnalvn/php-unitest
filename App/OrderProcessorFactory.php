<?php
namespace App;

class OrderProcessorFactory implements OrderProcessorFactoryInterface {
    private APIClient $apiClient;
    private FileSystem $fileSystem;
    private string $outputDir;

    public function __construct(APIClient $apiClient, FileSystem $fileSystem, string $outputDir = '') {
        $this->apiClient = $apiClient;
        $this->fileSystem = $fileSystem;
        $this->outputDir = $outputDir;
    }

    /**
     * Create an OrderProcessor instance based on the given type
     *
     * @param string $type Type of the order processor to create
     *
     * @return OrderProcessor
     *
     * @throws \InvalidArgumentException If the type is unknown
     */
    public function createProcessor(string $type): OrderProcessor {
        return match($type) {
            'A' => new TypeAOrderProcessor($this->fileSystem, $this->outputDir),
            'B' => new TypeBOrderProcessor($this->apiClient),
            'C' => new TypeCOrderProcessor(),
            default => throw new \InvalidArgumentException("Unknown order type: $type")
        };
    }
}