<?php

namespace App\Http\Controllers;

use App\Data\N8nOrder;
use Illuminate\Http\Request;

class N8nOrderController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // 模擬資料（實務上你可能從外部 API 或記憶體取得）
        return collect([
            new N8nOrder(
                orderID: 1,
                customerID: 8,
                employeeName: "Alice",
                orderPrice: 150.32,
                orderStatus: "processing"
            ),
            new N8nOrder(
                orderID: 2,
                customerID: 1,
                employeeName: "Bob",
                orderPrice: 111.19,
                orderStatus: "booked"
            ),
            new N8nOrder(
                orderID: 3,
                customerID: 9,
                employeeName: "Charlie",
                orderPrice: 111.19,
                orderStatus: "booked"
            ),
            new N8nOrder(
                orderID: 4,
                customerID: 1,
                employeeName: "Bob",
                orderPrice: 101.71,
                orderStatus: "booked"
            ),
            new N8nOrder(
                orderID: 5,
                customerID: 9,
                employeeName: "Bob",
                orderPrice: 55.9,
                orderStatus: "booked"
            ),
        ]);
    }
}
