<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class CardSuits extends Component
{
    public array $suits = [];
    public array $numbers = [];
    public array $cards = [];

    public function mount(): void
    {
        $this->suits = [
            ['name' => '梅花', 'symbol' => '♣'],
            ['name' => '方塊', 'symbol' => '♦'],
            ['name' => '紅心', 'symbol' => '♥'],
            ['name' => '黑桃', 'symbol' => '♠'],
        ];

        $this->numbers = [
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
            11,
            12,
            13,
        ];

        foreach ($this->suits as $suit) {
            foreach ($this->numbers as $number) {
                $this->cards[] = [
                    'name' => $suit['name'] . $number,
                    'symbol' => $suit['symbol'] . $number,
                ];
            }
        }
    }

    public function render(): View
    {
        return view('livewire.card-suits');
    }
}
