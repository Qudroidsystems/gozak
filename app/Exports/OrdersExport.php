<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class OrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Order::with(['user', 'items'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('payment_status'), fn($q) => $q->where('payment_status', request('payment_status')))
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Invoice', 'Customer', 'Email', 'Date', 'Items', 'Total', 'Payment', 'Status'
        ];
    }

    public function map($order): array
    {
        return [
            $order->invoice_number ?? substr($order->id, 0, 8),
            $order->user->first_name . ' ' . $order->user->last_name,
            $order->user->email,
            $order->created_at->format('d M Y'),
            $order->items_count,
            '$' . number_format($order->total_amount, 2),
            ucfirst($order->payment_status),
            ucfirst($order->status),
        ];
    }
}