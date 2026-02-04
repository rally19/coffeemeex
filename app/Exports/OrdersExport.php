<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $filters;
    protected $sortBy;
    protected $sortDirection;

    public function __construct($search, $filters, $sortBy, $sortDirection)
    {
        $this->search = $search;
        $this->filters = $filters;
        $this->sortBy = $sortBy;
        $this->sortDirection = $sortDirection;
    }

    public function query()
    {
        return Order::query()
            ->with(['user', 'items'])
            ->when($this->search['user'], function ($query) {
                $query->whereHas('user', function($q) {
                    $q->where('email', 'like', '%'.$this->search['user'].'%')
                      ->orWhere('name', 'like', '%'.$this->search['user'].'%');
                });
            })
            ->when($this->search['code'], function ($query) {
                $query->where('code', 'like', '%'.$this->search['code'].'%');
            })
            ->when($this->search['item_name'], function ($query) {
                $query->whereHas('items', function($q) {
                    $q->where('item_name', 'like', '%'.$this->search['item_name'].'%');
                });
            })
            ->when($this->filters['order_status'], function ($query) {
                $query->where('order_status', $this->filters['order_status']);
            })
            ->when($this->filters['payment_status'], function ($query) {
                $query->where('payment_status', $this->filters['payment_status']);
            })
            ->when($this->filters['payment_method'], function ($query) {
                $query->where('payment_method', 'like', '%'.$this->filters['payment_method'].'%');
            })
            ->when($this->filters['after_date'], function ($query) {
                $query->whereDate('created_at', '>=', $this->filters['after_date']);
            })
            ->when($this->filters['before_date'], function ($query) {
                $query->whereDate('created_at', '<=', $this->filters['before_date']);
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            });
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Customer Email',
            'Customer Name',
            'Order Status',
            'Payment Status',
            'Payment Method',
            'Items Count',
            'Total Items',
            'Total Cost',
            'Shipping Address',
            'Customer Notes',
            'Public Comments',
            'Private Comments',
            'Payment Proof',
            'Order Date',
            'Item Details',
        ];
    }

    public function map($order): array
    {
        // Get item details
        $itemsCount = $order->items->count();
        $totalItems = $order->items->sum('quantity');
        
        // Get item names (first 3 items)
        $itemNames = $order->items->take(3)->map(function($item) {
            return "{$item->quantity}x {$item->item_name}";
        })->implode(', ');
        
        if ($order->items->count() > 3) {
            $itemNames .= " +" . ($order->items->count() - 3) . " more items";
        }

        return [
            $order->code,
            $order->user->email ?? 'N/A',
            $order->user->name ?? 'N/A',
            $this->getOrderStatusLabel($order->order_status),
            $this->getPaymentStatusLabel($order->payment_status),
            $this->getPaymentMethodLabel($order->payment_method),
            $itemsCount,
            $totalItems,
            number_format($order->total_cost, 2),
            $order->address ?? 'N/A',
            $order->notes ?? '',
            $order->comments_public ?? '',
            $order->comments_private ?? '',
            $order->payment_proof ? 'Yes' : 'No',
            $order->created_at->format('Y-m-d H:i'),
            // Additional column for item details
            $itemNames,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function getOrderStatusLabel($status): string
    {
        $statuses = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getPaymentStatusLabel($status): string
    {
        $statuses = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'failed' => 'Failed'
        ];
        
        return $statuses[$status] ?? $status;
    }

    private function getPaymentMethodLabel($method): string
    {
        $methods = [
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'e_wallet' => 'E-Wallet',
            'cash' => 'Cash'
        ];
        
        return $methods[$method] ?? $method;
    }
}