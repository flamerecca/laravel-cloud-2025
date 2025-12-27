<?php

namespace App\Http\Controllers;

use App\Data\N8nOrder;
use Illuminate\Http\Request;

class N8nOrderHeaderController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {// 檢查 Header 是否存在
        if (!$request->hasHeader('unique-id')) {
            return response()->json(['message' => '缺少 Header: unique_id']);
        }
        // 模擬資料（實務上你可能從外部 API 或記憶體取得）
        return collect([
            new N8nOrder(
                orderID: 1,
                customerID: 18,
                employeeName: "Dave",
                orderPrice: 253.32,
                orderStatus: "processing"
            ),
            new N8nOrder(
                orderID: 2,
                customerID: 11,
                employeeName: "Eve",
                orderPrice: 209.19,
                orderStatus: "booked"
            ),
            new N8nOrder(
                orderID: 3,
                customerID: 19,
                employeeName: "Eve",
                orderPrice: 211.19,
                orderStatus: "booked"
            ),
            new N8nOrder(
                orderID: 4,
                customerID: 11,
                employeeName: "Dave",
                orderPrice: 201.71,
                orderStatus: "booked"
            ),
            new N8nOrder(
                orderID: 5,
                customerID: 19,
                employeeName: "Zoe",
                orderPrice: 231.9,
                orderStatus: "booked"
            ),
        ]);
    }

}
