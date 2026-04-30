<?php

use Livewire\Livewire;

test('count page can be rendered', function () {
    $this->get('/count')
        ->assertStatus(200)
        ->assertSeeLivewire('counter');
});

test('counter can increment and decrement', function () {
    Livewire::test('counter')
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->call('decrement')
        ->assertSet('count', 0);
});
