<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $pagetitle = 'Dashboard';

        // Total Revenue (sum of orders with successful transactions)
        $totalRevenue = Order::whereHas('transactions', function($query) {
            $query->where('status', 'success');
        })->sum('total_amount');

        // Total Orders
        $totalOrders = Order::count();

        // Total Products
        $totalProducts = Product::count();

        // Total Customers (unique users with orders)
        $totalCustomers = User::whereHas('orders')->count();

        // Average Rating
        $avgRating = ProductReview::avg('rating') ?? 0;

        // Recent Sales (last 10 orders with successful payment)
        $recentSales = Order::whereHas('transactions', function($query) {
            $query->where('status', 'success');
        })
        ->with('user')
        ->orderBy('order_date', 'desc')
        ->take(10)
        ->get()
        ->map(function ($order) {
            return [
                'user_name' => $order->user->name ?? 'Unknown',
                'date' => $order->order_date->format('d M, Y'),
                'amount' => number_format($order->total_amount, 2),
            ];
        });

        // Latest Orders (last 20 orders with details)
        $latestOrders = Order::with(['items.product', 'user'])
            ->orderBy('order_date', 'desc')
            ->take(20)
            ->get()
            ->map(function ($order) {
                $product = $order->items->first();
                $brandName = $product && $product->product ? ($product->product->brand->name ?? 'N/A') : 'N/A';
                $avgProductRating = $product && $product->product ? ($product->product->reviews()->avg('rating') ?? '-') : '-';
                return [
                    'order_date' => $order->order_date->format('d M, Y'),
                    'order_id' => $order->id,
                    'shop' => $brandName,
                    'customer' => $order->user->name ?? 'Unknown',
                    'products' => $order->items->pluck('title')->implode(', '),
                    'amount' => number_format($order->total_amount, 2),
                    'status' => $order->status,
                    'rating' => $avgProductRating,
                ];
            });

        // Popular Products (top 6 by sold_quantity)
        $popularProducts = Product::with('brand', 'reviews')
            ->orderBy('sold_quantity', 'desc')
            ->take(6)
            ->get()
            ->map(function ($product) {
                return [
                    'image' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/images/products/32/img-1.png'),
                    'title' => $product->title,
                    'rating' => number_format($product->reviews->avg('rating') ?? 0, 1),
                    'sales' => $product->sold_quantity,
                    'price' => number_format($product->price, 2),
                ];
            });

        // Orders Status Counts
        $orderStatuses = Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Revenue Chart Data (monthly revenue for last 12 months, based on successful transactions)
        $revenueData = $this->getMonthlyRevenueData();

        // Sales by Countries (mock data, replace with real if address has country)
        $salesByCountries = [
            ['country' => 'United States', 'sales' => 15364],
            ['country' => 'Greenland', 'sales' => 12387],
            ['country' => 'Serbia', 'sales' => 9123],
            // Add more as needed
        ];

        // Traffic Source Chart Data (mock, replace with real analytics)
        $trafficSources = [
            ['source' => 'Direct', 'value' => 40],
            ['source' => 'Referral', 'value' => 30],
            ['source' => 'Social', 'value' => 20],
            ['source' => 'Search', 'value' => 10],
        ];

        // Recent Activity (mock, integrate with logs or events)
        $recentActivity = [
            ['icon' => 'ph-shopping-cart', 'title' => 'Purchased by James Price', 'description' => 'Product noise evolve smartwatch', 'time' => '05:57 AM Today'],
            // Add more
        ];

        // Insights (static or from config)
        $insights = [
            'The recognition that one has a mental illness',
            'Review market characteristics and trends',
            // Add more
        ];

        return view('dashboard', compact(
            'pagetitle',
            'totalRevenue', 'totalOrders', 'totalProducts', 'totalCustomers', 'avgRating',
            'recentSales', 'latestOrders', 'popularProducts', 'orderStatuses',
            'revenueData', 'salesByCountries', 'trafficSources', 'recentActivity', 'insights'
        ));
    }

    private function getMonthlyRevenueData()
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $monthlyRevenue = Order::whereHas('transactions', function($query) {
            $query->where('status', 'success');
        })
        ->select(
            DB::raw('DATE_FORMAT(orders.order_date, "%Y-%m") as month'),
            DB::raw('SUM(orders.total_amount) as revenue')
        )
        ->whereBetween('orders.order_date', [$startDate, $endDate])
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->map(function ($item) {
            return [
                'month' => $item->month,
                'revenue' => (float) $item->revenue,
            ];
        })
        ->toArray();

        // Fill missing months with 0
        $fullData = [];
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i)->format('Y-m');
            $revenue = collect($monthlyRevenue)->firstWhere('month', $date)['revenue'] ?? 0;
            $fullData[] = ['month' => $date, 'revenue' => $revenue];
        }

        return $fullData;
    }
}