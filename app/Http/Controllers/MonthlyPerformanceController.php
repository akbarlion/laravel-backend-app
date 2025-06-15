<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class MonthlyPerformanceController extends Controller
{
    /**
     * Get monthly target & transaction for this years
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function monthlyPerform(Request $request)
    {
        $month = $request->input('month') ? (int) $request->input('month') : Carbon::now()->month;
        $year = $request->input('year') ? (int) $request->input('year') : Carbon::now()->year;
        $isUnderperform = $request->has('isUnderperform') ? $request->boolean('isUnderperform') : null;

        $monthDate = Carbon::createFromDate($year, $month, 1);
        $monthName = $monthDate->format('F Y');

        $salesQuery = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select('sales.id', 'users.name');

        $allSales = $salesQuery->get();

        $targetsQuery = DB::table('sales_targets')
            ->select('sales_id', DB::raw('SUM(amount) as target_amount'))
            ->whereYear('active_date', $year)
            ->whereMonth('active_date', $month)
            ->groupBy('sales_id');

        $targets = $targetsQuery->get()->keyBy('sales_id');

        $transactionsQuery = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.order_id')
            ->select(
                'sales_orders.sales_id',
                DB::raw('SUM(sales_order_items.selling_price * sales_order_items.quantity) as revenue')
            )
            ->whereYear('sales_orders.created_at', $year)
            ->whereMonth('sales_orders.created_at', $month)
            ->groupBy('sales_orders.sales_id');

        $transactions = $transactionsQuery->get()->keyBy('sales_id');

        $salesData = [];

        foreach ($allSales as $sale) {
            $saleId = $sale->id;
            $target = isset($targets[$saleId]) ? (float) $targets[$saleId]->target_amount : 0;
            $revenue = isset($transactions[$saleId]) ? (float) $transactions[$saleId]->revenue : 0;
            $isUnderperforming = $revenue < $target;
            $percentage = $target > 0 ? ($revenue / $target) * 100 : 0;

            if ($isUnderperform !== null && $isUnderperforming !== $isUnderperform) {
                continue;
            }

            $revenueAbbr = $this->formatAbbreviation($revenue);
            $targetAbbr = $this->formatAbbreviation($target);

            $salesData[] = [
                'sales' => $sale->name,
                'revenue' => [
                    'amount' => number_format($revenue, 2, '.', ''),
                    'abbreviation' => $revenueAbbr
                ],
                'target' => [
                    'amount' => number_format($target, 2, '.', ''),
                    'abbreviation' => $targetAbbr
                ],
                'percentage' => number_format($percentage, 2, '.', '')
            ];
        }

        usort($salesData, function ($a, $b) {
            return (float) $b['percentage'] - (float) $a['percentage'];
        });

        return response()->json([
            'is_underperform' => $isUnderperform,
            'month' => $monthName,
            'items' => $salesData
        ]);
    }

    /**
     * Format number to abbreviation (K, M, B)
     * 
     * @param float $number
     * @return string
     */
    private function formatAbbreviation($number)
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 2, '.', '') . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 2, '.', '') . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2, '.', '') . 'K';
        }

        return number_format($number, 2, '.', '');
    }

}
