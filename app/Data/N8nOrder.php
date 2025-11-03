<?php

namespace App\Data;

class N8nOrder
{
    public function __construct(
        public int    $orderID,
        public int    $customerID,
        public string $employeeName,
        public float  $orderPrice,
        public string $orderStatus,
    )
    {
    }
}
