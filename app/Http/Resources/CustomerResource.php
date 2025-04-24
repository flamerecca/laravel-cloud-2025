<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => preg_replace(
                pattern: '/[+\d-](?=[\d-]{4})/',
                replacement: '*',
                subject: $this->phone
            ),
            'joined' => $this->created_at->format('Y-m-d'),
        ];
    }
}
