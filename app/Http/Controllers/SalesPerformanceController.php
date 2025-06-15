<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class SalesPerformanceController extends Controller
{
    /**
     * Get monthly target & transaction for this years
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyPerformance(Request $request)
    {
        $salesId = $request->input('sales_id');

        $currentYear = Carbon::now()->year;

        $salesName = null;

        if ($salesId) {
            $sale = Sale::select('sales.*', 'users.name as user_name')
                ->join('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.id', $salesId)
                ->first();

            $salesName = $sale ? $sale->user_name : null;
        }

        $transactionQuery = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.order_id')
            ->select(
                DB::raw('MONTH(sales_orders.created_at) as month'),
                DB::raw('SUM(sales_order_items.selling_price * sales_order_items.quantity) as revenue'),
                DB::raw('SUM((sales_order_items.selling_price - sales_order_items.production_price) * sales_order_items.quantity) as income')
            )
            ->whereYear('sales_orders.created_at', $currentYear)
            ->groupBy('month');

        if ($salesId) {
            $transactionQuery->where('sales_orders.sales_id', $salesId);
        }

        $transactions = $transactionQuery->get()->keyBy('month');

        $targetQuery = DB::table('sales_targets')
            ->select(
                DB::raw('MONTH(active_date) as month'),
                DB::raw('SUM(amount) as target_amount')
            )
            ->whereYear('active_date', $currentYear)
            ->groupBy(DB::raw('MONTH(active_date)'));

        if ($salesId) {
            $targetQuery->where('sales_id', $salesId);
        }

        $targets = $targetQuery->get()->keyBy('month');

        $targetData = [];
        $revenueData = [];
        $incomeData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::createFromDate($currentYear, $month, 1)->format('M');

            $targetData[] = [
                'x' => $monthName,
                'y' => isset($targets[$month]) ? $targets[$month]->target_amount : '0.00'
            ];

            $revenueData[] = [
                'x' => $monthName,
                'y' => isset($transactions[$month]) ? $transactions[$month]->revenue : '0.00'
            ];

            $incomeData[] = [
                'x' => $monthName,
                'y' => isset($transactions[$month]) ? $transactions[$month]->income : '0.00'
            ];
        }

        $items = [
            [
                'name' => 'Target',
                'data' => $targetData
            ],
            [
                'name' => 'Revenue',
                'data' => $revenueData
            ],
            [
                'name' => 'Income',
                'data' => $incomeData
            ]
        ];

        return response()->json([
            'sales' => $salesName,
            'year' => (string) $currentYear,
            'items' => $items
        ]);
    }

}
