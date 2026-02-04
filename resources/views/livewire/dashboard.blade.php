<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Order, OrderItem, CartItem};
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Dashboard')]
class extends Component {
    use WithPagination;
    
    public bool $showFilters = false;
    public ?string $orderCodeToCancel = null;
    public $search = [
        'order_code' => '',
        'item_name' => '',
    ];
    public $filters = [
        'order_status' => '',
        'payment_status' => '',
        'payment_method' => '',
    ];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    
    public function mount()
    {
        $this->search = session()->get('orders.search', $this->search);
        $this->filters = session()->get('orders.filters', $this->filters);
        $this->sortBy = session()->get('orders.sortBy', $this->sortBy);
        $this->sortDirection = session()->get('orders.sortDirection', $this->sortDirection);
        $this->perPage = session()->get('orders.perPage', $this->perPage);
        
        $savedPage = session()->get('orders.page', 1);
        $this->setPage($savedPage);
        
        $this->validatePage();
    }

    public function updatedPage($value)
    {
        session()->put('orders.page', $value);
    }

    public function gotoPage($page)
    {
        $this->setPage($page);
        session()->put('orders.page', $page);
        $this->validatePage();
    }
    
    public function confirmCancel($orderCode)
    {
        $order = Order::where('code', $orderCode)->first();
        if ($order && $order->order_status === 'pending') {
            $this->orderCodeToCancel = $orderCode;
            Flux::modal('cancel-order-modal')->show();
        }
    }
    
    public function cancelOrder()
    {
        if (!$this->orderCodeToCancel) {
            $this->dispatch('toast', message: 'Order not found', type: 'error');
            return;
        }
        
        try {
            $order = Order::where('code', $this->orderCodeToCancel)
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$order) {
                $this->dispatch('toast', message: 'Order not found or unauthorized', type: 'error');
                return;
            }
            
            $order->update(['order_status' => 'cancelled']);
            
            Flux::toast(
                variant: 'success',
                heading: 'Order Cancelled.',
                text: 'Your order has been successfully cancelled.',
            );
            
            $this->orderCodeToCancel = null;
            Flux::modal('cancel-order-modal')->close();
            
            $this->resetPage();
            unset($this->getOrders);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to cancel order: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function updatedPerPage($value)
    {
        $this->resetPage();
        $this->validatePage();
        session()->put('orders.perPage', $value);
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
        session()->put('orders.search', $this->search);
    }
    
    public function updatedFilters()
    {
        $this->resetPage();
        session()->put('orders.filters', $this->filters);
    }
    
    public function resetFilters()
    {
        $this->reset('search');
        $this->reset('filters');
        session()->forget(['orders.search', 'orders.filters']);
        $this->resetPage();
        session()->put('orders.page', 1);
    }
    
    public function validatePage()
    {
        $orders = $this->getOrders();
        
        if ($orders->currentPage() > $orders->lastPage()) {
            $this->setPage($orders->lastPage());
        }
    }
    
    #[Computed]
    public function getOrders()
    {
        return Order::query()
            ->where('user_id', Auth::id())
            ->when($this->search['order_code'], function ($query) {
                $query->where('code', 'like', '%' . $this->search['order_code'] . '%');
            })
            ->when($this->search['item_name'], function ($query) {
                $query->whereHas('items', function ($q) {
                    $q->where('item_name', 'like', '%' . $this->search['item_name'] . '%');
                });
            })
            ->when($this->filters['order_status'], function ($query) {
                $query->where('order_status', $this->filters['order_status']);
            })
            ->when($this->filters['payment_status'], function ($query) {
                $query->where('payment_status', $this->filters['payment_status']);
            })
            ->when($this->filters['payment_method'], function ($query) {
                $query->where('payment_method', $this->filters['payment_method']);
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->with(['items'])
            ->paginate($this->perPage);
    }

    #[Computed]
    public function getOrderStatusOptions()
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed'
        ];
    }
    
    #[Computed]
    public function getPaymentStatusOptions()
    {
        return [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            'failed' => 'Failed'
        ];
    }
    
    #[Computed]
    public function getPaymentMethodOptions()
    {
        return [
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'e_wallet' => 'E-Wallet',
            'cash' => 'Cash'
        ];
    }
    
    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        $this->validatePage();
        
        session()->put('orders.sortBy', $this->sortBy);
        session()->put('orders.sortDirection', $this->sortDirection);
    }

    #[Computed]
    public function getTotalOrders()
    {
        return Order::where('user_id', Auth::id())->count();
    }

    #[Computed]
    public function getTotalItemsOrdered()
    {
        return OrderItem::whereIn('order_id', function($query) {
            $query->select('id')
                  ->from('orders')
                  ->where('user_id', Auth::id());
        })->sum('quantity');
    }

    #[Computed]
    public function getTotalSpent()
    {
        return Order::where('user_id', Auth::id())
            ->where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_cost');
    }

    #[Computed]
    public function getCartTotalQuantity()
    {
        return Auth::user()->cartTotalQuantity();
    }

    public function getFormattedItems($order)
    {
        $items = $order->items;
        $itemCount = $items->count();

        if($itemCount > 0) {
            if($itemCount <= 2) {
                return $items->pluck('item_name')->join(', ');
            } else {
                return $items->first()->item_name . ' and ' . ($itemCount - 1) . ' more item(s)';
            }
        }

        return 'No items';
    }
}; ?>

<div>
    <div><flux:heading size="xl">Welcome back {{ Auth::user()->name }}!</flux:heading></div><br>
    <div class="grid auto-rows-min gap-4 grid-cols-3">
        <flux:card class="items-center overflow-hidden">
            <flux:text>Total Orders</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums inline-flex items-center">{{ $this->getTotalOrders }}</flux:heading>
        </flux:card>
        <flux:card class="items-center overflow-hidden">
            <flux:text>Items Ordered</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums inline-flex items-center">{{ $this->getTotalItemsOrdered }}</flux:heading>
        </flux:card>
        <flux:card class="items-center overflow-hidden">
            <flux:text>Total Spent</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums inline-flex items-center">{{ format_rupiah($this->getTotalSpent) }}</flux:heading>
        </flux:card>
    </div>
    <br>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">My Orders</flux:heading></div>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block">
                <flux:button type="button" wire:click="$toggle('showFilters')">
                    <span x-show="!$wire.showFilters"><flux:icon.funnel/></span>
                    <span x-show="$wire.showFilters"><flux:icon.funnel variant="solid"/></span>
                </flux:button>
            </div>
            <div class="hidden sm:block">
                <flux:select wire:model.live="perPage">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                </flux:select>
            </div>
        </div>
    </div>
    
    <div class="flex items-center justify-between mb-4 sm:hidden">
        <div>
            <flux:button type="button" wire:click="$toggle('showFilters')">
                <span x-show="!$wire.showFilters"><flux:icon.funnel/></span>
                <span x-show="$wire.showFilters"><flux:icon.funnel variant="solid"/></span>
            </flux:button>
        </div>
        <div class="flex items-center gap-4">
            <div>
                <flux:select wire:model.live="perPage">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                </flux:select>
            </div>
        </div>
    </div>
    
    <div x-data="{ show: $wire.entangle('showFilters') }"
         x-show="show"
         x-collapse
         class="overflow-hidden">
        <div class="mb-6 p-4 outline outline-offset-[-1px] rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:label>Order Code</flux:label>
                    <flux:input wire:model.live="search.order_code" placeholder="Search by order code..." />
                </div>
                
                <div>
                    <flux:label>Item Name</flux:label>
                    <flux:input wire:model.live="search.item_name" placeholder="Search by item name..." />
                </div>
                
                <div>
                    <flux:label>Order Status</flux:label>
                    <flux:select wire:model.live="filters.order_status">
                        <option value="">All Statuses</option>
                        @foreach($this->getOrderStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                
                <div>
                    <flux:label>Payment Status</flux:label>
                    <flux:select wire:model.live="filters.payment_status">
                        <option value="">All Statuses</option>
                        @foreach($this->getPaymentStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                
                <div>
                    <flux:label>Payment Method</flux:label>
                    <flux:select wire:model.live="filters.payment_method">
                        <option value="">All Methods</option>
                        @foreach($this->getPaymentMethodOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <flux:button type="button" wire:click="resetFilters">
                    Reset Filters
                </flux:button>
            </div>
        </div>
    </div>
    
    @if($this->getOrders()->count())
    <flux:table :paginate="$this->getOrders()">
        <flux:table.columns>
            <flux:table.column class="text-center">No.</flux:table.column>
            <flux:table.column class="text-center">Actions</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Order Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'order_status'" :direction="$sortDirection" wire:click="sort('order_status')">Order Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'payment_status'" :direction="$sortDirection" wire:click="sort('payment_status')">Payment Status</flux:table.column>
            <flux:table.column>Items</flux:table.column>
            <flux:table.column>Total Quantity</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'total_cost'" :direction="$sortDirection" wire:click="sort('total_cost')">Total Cost</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'payment_method'" :direction="$sortDirection" wire:click="sort('payment_method')">Payment Method</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Order Date</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->getOrders() as $order)
            <flux:table.row>
                <flux:table.cell class="text-center">{{ ($this->getOrders()->currentPage() - 1) * $this->getOrders()->perPage() + $loop->iteration }}.</flux:table.cell>
                <flux:table.cell class="text-center">
                    <div class="flex items-center justify-start gap-2">
                        <flux:button 
                            icon="eye" 
                            :href="route('order', ['code' => $order->code])" 
                            wire:navigate
                        ></flux:button>
                        @if ($order->order_status === 'pending')
                        <flux:button 
                            icon="pencil" 
                            variant="primary" 
                            :href="route('order.edit', ['code' => $order->code])" 
                            wire:navigate
                        ></flux:button>
                        <flux:button 
                            icon="x-circle" 
                            variant="danger" 
                            wire:click="confirmCancel('{{ $order->code }}')"
                        ></flux:button>
                        @endif
                        @if ($order->payment_proof)
                        <flux:button 
                            icon="credit-card" 
                            variant="ghost"
                            href="{{ route('payment-proof', [
                                    'orderCode' => $order->code,
                                    'filename' => basename($order->payment_proof)
                                ]) }}" 
                            target="_blank"
                            title="View Payment Proof"
                        ></flux:button>
                        @endif
                    </div>
                </flux:table.cell>
                <flux:table.cell>{{ $order->code }}</flux:table.cell>
                <flux:table.cell>
                    <flux:badge variant="solid" :color="match($order->order_status) {
                        'completed' => 'lime',
                        'processing' => 'blue',
                        'pending' => 'yellow',
                        'cancelled' => 'zinc',
                        'failed' => 'red',
                        default => 'zinc'
                    }">
                        {{ $this->getOrderStatusOptions[$order->order_status] ?? $order->order_status }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge variant="solid" :color="match($order->payment_status) {
                        'paid' => 'lime',
                        'pending' => 'yellow',
                        'refunded' => 'zinc',
                        'failed' => 'red',
                        default => 'zinc'
                    }">
                        {{ $this->getPaymentStatusOptions[$order->payment_status] ?? $order->payment_status }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    {{ $this->getFormattedItems($order) }}
                </flux:table.cell>
                <flux:table.cell class="text-center">{{ $order->items->sum('quantity') }}</flux:table.cell>
                <flux:table.cell>{{ format_rupiah($order->total_cost) }}</flux:table.cell>
                <flux:table.cell>{{ $this->getPaymentMethodOptions[$order->payment_method] ?? $order->payment_method }}</flux:table.cell>
                <flux:table.cell>{{ $order->created_at->format('Y-m-d H:i') }}</flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="cancel-order-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel this order?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->orderCodeToCancel)
                        <p>You're about to cancel order <strong>{{ $this->orderCodeToCancel }}</strong>.</p>
                        <p>This action cannot be undone.</p>
                    @endif
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">No, Keep It</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="danger" 
                    wire:click="cancelOrder"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Yes, Cancel Order</span>
                    <span wire:loading>Cancelling...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="text-center py-8">
        <div class="text-gray-400 mb-4">
            <flux:icon.magnifying-glass class="w-12 h-12 mx-auto" />
        </div>
        <p class="text-neutral-600 dark:text-neutral-400 text-lg">No orders found matching your criteria.</p>
        <div class="mt-4">
            <flux:button wire:click="resetFilters">Reset Filters</flux:button>
        </div>
    </div>
    @endif
</div>