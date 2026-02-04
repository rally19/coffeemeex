<?php
use Livewire\Attributes\{Layout, Title, Url, Computed};
use Livewire\Volt\Component;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.admin')]
    #[Title('Order Invoice')]
class extends Component {
    public Order $order;
    public $items = [];
    
    public function mount($id): void
    {
        $this->order = Order::with(['items', 'user'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $this->items = $this->order->items;
    }

    #[Computed]
    public function total(): float
    {
        return $this->order->total_cost;
    }

    #[Computed]
    public function barcode(): string
    {
        return DNS1D::getBarcodeSVG($this->order->code, 'C128', 1.2, 60);
    }
    
    public function formatDate($date): string
    {
        return $date->format('M j, Y H:i');
    }
}; ?>

<div id="invoice">
    <div class="flex flex-row gap-4 justify-end hide-print"
        x-data="{ 
            originalAppearance: $flux.appearance, 
            printWithLightTheme() {
                if (this.originalAppearance !== 'light') {
                    $flux.appearance = 'light';
                    setTimeout(() => {
                        window.print();
                        setTimeout(() => {
                            $flux.appearance = this.originalAppearance;
                        }, 500);
                    }, 100);
                } else {
                    window.print();
                }
            }
        }"
    >
        <flux:button 
            variant="primary" 
            icon="printer"
            @click="printWithLightTheme()"
        >
            Print Invoice
        </flux:button>
        <flux:button 
            :href="route('admin.orders')" 
            wire:navigate
        >
            Back to Orders
        </flux:button>
    </div>
    <style>
        @media print {
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                visibility: hidden;
            }

            #invoice {
                visibility: visible;
                position: absolute;
                left: 0;
                top: 0;
                width: 100vw;
                max-width: 100%;
                padding: 0;
                margin: 0;
            }

            .hide-print {
                display: none;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
    </style>
    
    <div class="flex mb-2">
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground mr-2">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </div>
        <flux:heading size="xl"> Coffeemeex</flux:heading>
    </div>

    <div class="outline rounded-lg overflow-hidden hover:shadow-md transition-shadow mb-8">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading size="xl">Order Invoice</flux:heading>
                    <div class="text-neutral-600 dark:text-neutral-400">Invoice #{{ $order->code }}</div>
                    <div class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                        Order Date: {{ $this->formatDate($order->created_at) }}
                    </div>
                </div>
                <div class="text-right">
                    <flux:badge variant="solid" :color="match($order->order_status) {
                        'completed' => 'lime',
                        'processing' => 'blue',
                        'pending' => 'yellow',
                        'cancelled' => 'zinc',
                        'failed' => 'red',
                        default => 'zinc'
                    }">
                        {{ ucfirst($order->order_status) }}
                    </flux:badge>
                    <flux:badge variant="solid" class="ml-2" :color="match($order->payment_status) {
                        'paid' => 'lime',
                        'pending' => 'yellow',
                        'refunded' => 'blue',
                        'failed' => 'red',
                        default => 'zinc'
                    }">
                        {{ ucfirst($order->payment_status) }}
                    </flux:badge>
                </div>
            </div>
            
            <flux:separator class="mb-6"/>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <flux:heading size="lg" class="mb-4">Billing Information</flux:heading>
                    <div class="space-y-2">
                        <div class="font-medium">{{ $order->user->name }}</div>
                        <div class="text-neutral-600 dark:text-neutral-400">{{ $order->user->email }}</div>
                        @if($order->user->phone_numbers)
                            <div class="text-neutral-600 dark:text-neutral-400">{{ $order->user->phone_numbers }}</div>
                        @endif
                        @if($order->address)
                            <div class="mt-2 text-neutral-600 dark:text-neutral-400">
                                {{ $order->address }}
                            </div>
                        @endif
                    </div>
                </div>
                
                <div>
                    <flux:heading size="lg" class="mb-4">Payment Details</flux:heading>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-neutral-600 dark:text-neutral-400">Payment Method:</span>
                            <span class="font-medium">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</span>
                        </div>
                        @if($order->payment_proof)
                            <div class="flex justify-between">
                                <span class="text-neutral-600 dark:text-neutral-400">Payment Proof:</span>
                                <span class="font-medium">Uploaded</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-neutral-600 dark:text-neutral-400">Invoice Date:</span>
                            <span class="font-medium">{{ $this->formatDate($order->created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <flux:heading size="lg" class="mb-4">Order Items</flux:heading>
            <div class="overflow-x-auto mb-6">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 px-4 font-medium">Item</th>
                            <th class="text-center py-3 px-4 font-medium">Code</th>
                            <th class="text-center py-3 px-4 font-medium">Price</th>
                            <th class="text-center py-3 px-4 font-medium">Quantity</th>
                            <th class="text-right py-3 px-4 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr class="border-b">
                                <td class="py-3 px-4">
                                    <div class="font-medium">{{ $item->item_name }}</div>
                                    @if($item->item_code)
                                        <div class="text-sm text-neutral-600 dark:text-neutral-400">SKU: {{ $item->item_code }}</div>
                                    @endif
                                </td>
                                <td class="text-center py-3 px-4">
                                    {{ $item->code }}
                                </td>
                                <td class="text-center py-3 px-4">
                                    {{ format_rupiah($item->item_price) }}
                                </td>
                                <td class="text-center py-3 px-4">
                                    {{ $item->quantity }}
                                </td>
                                <td class="text-right py-3 px-4 font-medium">
                                    {{ format_rupiah($item->cost) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    @if($order->notes)
                        <flux:heading size="md" class="mb-2">Customer Notes</flux:heading>
                        <div class="p-3 rounded">
                            {{ $order->notes }}
                        </div>
                    @endif
                    
                    @if($order->comments_public)
                        <flux:heading size="md" class="mb-2 mt-4">Admin Message</flux:heading>
                        <div class="p-3 rounded">
                            {{ $order->comments_public }}
                        </div>
                    @endif
                </div>
                
                <div>
                    <div class="bg-neutral-50 dark:bg-neutral-900 p-4 rounded">
                        <flux:heading size="md" class="mb-4">Order Summary</flux:heading>
                        <div class="space-y-2">
                            <div class="flex justify-between pt-2 mt-2">
                                <span class="font-medium text-lg">Total Cost:</span>
                                <span class="font-medium text-lg text-primary-600">{{ format_rupiah($this->total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border-t p-4 flex justify-center bg-gray-50">
            <div class="text-center">
                <div class="mb-2 font-medium">Order Code: {{ $order->code }}</div>
                {!! $this->barcode !!}
            </div>
        </div>
    </div>
    
    <flux:heading size="lg" class="mb-4 hide-print">Order Information</flux:heading>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 hide-print">
        <flux:card>
            <flux:heading size="sm" class="mb-2">Order Status</flux:heading>
            <div class="space-y-1">
                <flux:badge variant="solid" :color="match($order->order_status) {
                    'completed' => 'lime',
                    'processing' => 'blue',
                    'pending' => 'yellow',
                    'cancelled' => 'zinc',
                    'failed' => 'red',
                    default => 'zinc'
                }">
                    {{ ucfirst($order->order_status) }}
                </flux:badge>
                <div class="text-sm text-neutral-600 dark:text-neutral-400">
                    @if($order->order_status === 'pending')
                        Your order is pending confirmation.
                    @elseif($order->order_status === 'processing')
                        Your order is being processed.
                    @elseif($order->order_status === 'completed')
                        Your order has been completed.
                    @elseif($order->order_status === 'cancelled')
                        This order has been cancelled.
                    @elseif($order->order_status === 'failed')
                        There was an issue with your order.
                    @endif
                </div>
            </div>
        </flux:card>
        
        <flux:card>
            <flux:heading size="sm" class="mb-2">Payment Status</flux:heading>
            <div class="space-y-1">
                <flux:badge variant="solid" :color="match($order->payment_status) {
                    'paid' => 'lime',
                    'pending' => 'yellow',
                    'refunded' => 'blue',
                    'failed' => 'red',
                    default => 'zinc'
                }">
                    {{ ucfirst($order->payment_status) }}
                </flux:badge>
                <div class="text-sm text-neutral-600 dark:text-neutral-400">
                    @if($order->payment_status === 'pending')
                        Awaiting payment confirmation.
                    @elseif($order->payment_status === 'paid')
                        Payment has been received.
                    @elseif($order->payment_status === 'refunded')
                        Payment has been refunded.
                    @elseif($order->payment_status === 'failed')
                        Payment failed.
                    @endif
                </div>
            </div>
        </flux:card>
    </div>
</div>