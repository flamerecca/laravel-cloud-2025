<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class N8nOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'orderID' => $this->orderID,
            'customerID' => $this->customerID,
            'employeeName' => $this->employeeName,
            'orderPrice' => $this->orderPrice,
            'orderStatus' => $this->orderStatus,
        ];
    }
}
