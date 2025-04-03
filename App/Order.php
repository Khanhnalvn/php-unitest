<?php
namespace App;

class Order
{
    public int|string|null $id;
    public string $type;
    public ?float $amount;
    public bool|string|null $flag;
    public string $status = 'pending';
    public string $priority = 'low';
    public ?array $apiResponse = null;
    public string $notes = '';
    public ?string $processedAt = null;
    public ?string $completedAt = null;
    public ?string $exportedAt = null;

    public function __construct(int|string|null $id, string $type, ?float $amount, bool|string|null $flag = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->amount = $amount;
        $this->flag = $flag;
    }
}
