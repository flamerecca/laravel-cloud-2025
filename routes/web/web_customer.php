<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Route;

Route::get('customers/{customer}/short-urls', function (Customer $customer) {
    $data = collect();
    foreach ($customer->shortUrls()->get() as $shortUrl) {
        $data->push([
            'name' => $shortUrl->name,
        ]);
    }
    return view('welcome');
});
