<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return User::customers()
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Name', 'Email', 'Phone', 'Orders', 'Total Spent', 'Joined Date'
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->full_name,
            $customer->email,
            $customer->phone_number ?? '—',
            $customer->orders_count,
            '$' . number_format($customer->orders_sum_total_amount ?? 0, 2),
            $customer->created_at->format('d M Y'),
        ];
    }
}