<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReportController extends Controller
{
    /**
     * Get monthly transactions for the last 3 years
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyTransactions(Request $request)
    {
        $customerId = $request->input('customer_id');
        $salesId = $request->input('sales_id');

        $endDate = Carbon::now();
        $startDate = Carbon::now()->subYears(3);

        $customerName = null;
        $salesName = null;

        if ($customerId) {
            $customer = Customer::find($customerId);
            $customerName = $customer ? $customer->name : null;
        }

        if ($salesId) {
            $sale = Sale::select('sales.*', 'users.name as user_name')
                ->join('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.id', $salesId)
                ->first();

            $salesName = $sale ? $sale->user_name : null;
        }

        $query = SalesOrderItem::select(
            DB::raw('YEAR(sales_orders.created_at) as year'),
            DB::raw('MONTH(sales_orders.created_at) as month'),
            DB::raw('SUM(sales_order_items.selling_price * sales_order_items.quantity) as total_amount')
        )
            ->join('sales_orders', 'sales_order_items.order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');

        if ($customerId) {
            $query->where('sales_orders.customer_id', $customerId);
        }

        if ($salesId) {
            $query->where('sales_orders.sales_id', $salesId);
        }

        $monthlyTransactions = $query->get();

        $years = [];
        $currentYear = (int) $startDate->format('Y');
        $endYear = (int) $endDate->format('Y');

        for ($year = $currentYear; $year <= $endYear; $year++) {
            $years[] = $year;
        }

        $yearlyData = [];
        foreach ($years as $year) {
            $yearlyData[$year] = [
                'name' => $year,
                'data' => []
            ];

            for ($month = 1; $month <= 12; $month++) {
                $monthName = Carbon::createFromDate($year, $month, 1)->format('M');
                $yearlyData[$year]['data'][] = [
                    'x' => $monthName,
                    'y' => '0.00'
                ];
            }
        }

        foreach ($monthlyTransactions as $transaction) {
            $year = $transaction->year;
            $month = $transaction->month - 1;
            if (isset($yearlyData[$year]['data'][$month])) {
                $yearlyData[$year]['data'][$month]['y'] = $transaction->total_amount;
            }
        }

        ksort($yearlyData);
        $items = array_values($yearlyData);

        return response()->json([
            'customer' => $customerName,
            'sales' => $salesName,
            'items' => $items
        ]);
    }
}