<?php
namespace App;

class APIResponse
{
    public $status;
    public $data;

    public function __construct($status = null, $data = null)
    {
        $this->status = $status;
        $this->data = $data;
    }
}
