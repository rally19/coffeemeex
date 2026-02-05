<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\WithPagination;
use Livewire\Volt\Component;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\{Order, Item, User};
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('Orders Management')]
class extends Component {
    use WithPagination;
    
    public bool $showFilters = false;
    public ?Order $orderToChangeStatus = null;
    public ?Order $orderToChangePaymentStatus = null; // NEW: For payment status changes
    public ?Order $orderToEditComment = null;
    public string $newStatus = '';
    public string $newPaymentStatus = ''; // NEW: For payment status
    public string $newPublicComment = '';
    public string $newPrivateComment = '';
    public $search = [
        'user' => '',
        'code' => '',
        'item_name' => '',
    ];
    public $filters = [
        'order_status' => '',
        'payment_status' => '',
        'payment_method' => '',
        'after_date' => '',
        'before_date' => '',
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
    
    public function updatedPerPage($value)
    {
        session()->put('orders.perPage', $value);
        $this->resetPage();
        $this->validatePage();
    }
    
    public function updatedSearch($value, $key)
    {
        session()->put('orders.search', $this->search);
        $this->resetPage();
    }
    
    public function updatedFilters($value, $key)
    {
        session()->put('orders.filters', $this->filters);
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset('search');
        $this->reset('filters');
        session()->forget('orders.search');
        session()->forget('orders.filters');
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
                    $q->where('item_name', 'like', '%'.$this->search['item_name'].'%')
                      ->orWhereHas('item', function($q2) {
                          $q2->where('name', 'like', '%'.$this->search['item_name'].'%');
                      });
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
                if ($this->sortBy === 'total_quantity') {
                    $query->withCount('items')->orderBy('items_count', $this->sortDirection);
                } else {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
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
        // Since payment_method is not strictly enum, we'll get unique values from existing orders
        $methods = Order::select('payment_method')
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->distinct()
            ->pluck('payment_method')
            ->mapWithKeys(function ($method) {
                return [$method => ucwords(str_replace('_', ' ', $method))];
            })
            ->toArray();
        
        return array_merge(['' => 'All Methods'], $methods);
    }
    
    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        session()->put('orders.sortBy', $this->sortBy);
        session()->put('orders.sortDirection', $this->sortDirection);
        
        $this->validatePage();
    }
    
    public function confirmStatusChange($orderId)
    {
        $this->orderToChangeStatus = Order::with('user')->find($orderId);
        $this->newStatus = $this->orderToChangeStatus->order_status;
        Flux::modal('change-status-modal')->show();
    }
    
    // NEW: Function to confirm payment status change
    public function confirmPaymentStatusChange($orderId)
    {
        $this->orderToChangePaymentStatus = Order::with('user')->find($orderId);
        $this->newPaymentStatus = $this->orderToChangePaymentStatus->payment_status;
        Flux::modal('change-payment-status-modal')->show();
    }
    
    public function updateOrderStatus()
    {
        $this->validate([
            'newStatus' => 'required|in:pending,processing,completed,cancelled,failed'
        ]);
        
        if (!$this->orderToChangeStatus) {
            $this->dispatch('toast', message: 'Order not found', type: 'error');
            return;
        }
        
        try {
            $this->orderToChangeStatus->update(['order_status' => $this->newStatus]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Order Status Updated',
                text: 'Order status has been successfully updated.',
            );
            
            $this->orderToChangeStatus = null;
            Flux::modal('change-status-modal')->close();
            
            $this->resetPage();
            unset($this->getOrders);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to update order status: ' . $e->getMessage(), type: 'error');
        }
    }
    
    // NEW: Function to update payment status
    public function updatePaymentStatus()
    {
        $this->validate([
            'newPaymentStatus' => 'required|in:pending,paid,refunded,failed'
        ]);
        
        if (!$this->orderToChangePaymentStatus) {
            $this->dispatch('toast', message: 'Order not found', type: 'error');
            return;
        }
        
        try {
            $this->orderToChangePaymentStatus->update(['payment_status' => $this->newPaymentStatus]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Payment Status Updated',
                text: 'Payment status has been successfully updated.',
            );
            
            $this->orderToChangePaymentStatus = null;
            Flux::modal('change-payment-status-modal')->close();
            
            $this->resetPage();
            unset($this->getOrders);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to update payment status: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function editPublicComment($orderId)
    {
        $this->orderToEditComment = Order::find($orderId);
        $this->newPublicComment = $this->orderToEditComment->comments_public ?? '';
        Flux::modal('edit-public-comment-modal')->show();
    }
    
    public function editPrivateComment($orderId)
    {
        $this->orderToEditComment = Order::find($orderId);
        $this->newPrivateComment = $this->orderToEditComment->comments_private ?? '';
        Flux::modal('edit-private-comment-modal')->show();
    }
    
    public function updatePublicComment()
    {
        $this->validate([
            'newPublicComment' => 'nullable|string|max:500'
        ]);
        
        if (!$this->orderToEditComment) {
            $this->dispatch('toast', message: 'Order not found', type: 'error');
            return;
        }
        
        try {
            $this->orderToEditComment->update(['comments_public' => $this->newPublicComment]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Public Comment Updated',
                text: 'Order public comment has been successfully updated.',
            );
            
            $this->orderToEditComment = null;
            Flux::modal('edit-public-comment-modal')->close();
            
            $this->resetPage();
            unset($this->getOrders);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to update comment: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function updatePrivateComment()
    {
        $this->validate([
            'newPrivateComment' => 'nullable|string|max:500'
        ]);
        
        if (!$this->orderToEditComment) {
            $this->dispatch('toast', message: 'Order not found', type: 'error');
            return;
        }
        
        try {
            $this->orderToEditComment->update(['comments_private' => $this->newPrivateComment]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Private Comment Updated',
                text: 'Order private comment has been successfully updated.',
            );
            
            $this->orderToEditComment = null;
            Flux::modal('edit-private-comment-modal')->close();
            
            $this->resetPage();
            unset($this->getOrders);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to update comment: ' . $e->getMessage(), type: 'error');
        }
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(
            new OrdersExport($this->search, $this->filters, $this->sortBy, $this->sortDirection),
            'orders-export-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
    
    #[Computed]
    public function getTotalItems(Order $order): int
    {
        return $order->items()->sum('quantity');
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">Orders Management</flux:heading></div>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block">
                <flux:button type="button" wire:click="$toggle('showFilters')">
                    <span x-show="!$wire.showFilters"><flux:icon.funnel/></span>
                    <span x-show="$wire.showFilters"><flux:icon.funnel variant="solid"/></span>
                </flux:button>
            </div>
            <div class="hidden sm:block">
                <flux:button 
                    variant="primary" 
                    wire:click="export"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        <flux:icon.arrow-down-tray class="w-4 h-4" />
                    </span>
                    <span wire:loading>
                        <flux:icon.arrow-path class="w-4 h-4 animate-spin" />
                    </span>
                </flux:button>
            </div>
            <div class="hidden sm:block">
                <flux:select wire:model.live="perPage">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
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
                <flux:button 
                    variant="primary" 
                    wire:click="export"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        <flux:icon.arrow-down-tray class="w-4 h-4" />
                    </span>
                    <span wire:loading>
                        <flux:icon.arrow-path class="w-4 h-4 animate-spin" />
                    </span>
                </flux:button>
            </div>
            <div>
                <flux:select wire:model.live="perPage">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="20">20 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </flux:select>
            </div>
        </div>
    </div>
    
    <div x-data="{ show: $wire.entangle('showFilters') }"
         x-show="show"
         x-collapse
         class="overflow-hidden">
        <div class="p-4 outline outline-offset-[-1px] rounded-lg shadow">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:label>Customer</flux:label>
                    <flux:input wire:model.live="search.user" placeholder="Search by name or email..." />
                </div>
                
                <div>
                    <flux:label>Order Code</flux:label>
                    <flux:input wire:model.live="search.code" placeholder="Search by order code..." />
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
                            @if($value !== '')
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endif
                        @endforeach
                    </flux:select>
                </div>
                
                <div>
                    <flux:label>After Date</flux:label>
                    <flux:input type="date" wire:model.live="filters.after_date" />
                </div>
                
                <div>
                    <flux:label>Before Date</flux:label>
                    <flux:input type="date" wire:model.live="filters.before_date" />
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <flux:button type="button" wire:click="resetFilters">
                    Reset Filters
                </flux:button>
            </div>
        </div>
    </div>
    
    <br>
    @if($this->getOrders()->count())
    <flux:table :paginate="$this->getOrders()">
        <flux:table.columns>
            <flux:table.column class="text-center">No.</flux:table.column>
            <flux:table.column class="text-center">Actions</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Order Code</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'order_status'" :direction="$sortDirection" wire:click="sort('order_status')">Order Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'payment_status'" :direction="$sortDirection" wire:click="sort('payment_status')">Payment Status</flux:table.column>
            <flux:table.column>Items</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'total_cost'" :direction="$sortDirection" wire:click="sort('total_cost')">Total Cost</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'payment_method'" :direction="$sortDirection" wire:click="sort('payment_method')">Payment Method</flux:table.column>
            <flux:table.column>Public Comment</flux:table.column>
            <flux:table.column>Private Comment</flux:table.column>
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
                            :href="route('admin.order', ['id' => $order->id])" 
                            wire:navigate
                        ></flux:button>
                        <flux:button 
                            icon="chat-bubble-left" 
                            variant="primary" 
                            wire:click="editPublicComment({{ $order->id }})"
                            title="Edit Public Comment"
                        ></flux:button>
                        <flux:button 
                            icon="chat-bubble-left-ellipsis" 
                            variant="filled" 
                            wire:click="editPrivateComment({{ $order->id }})"
                            title="Edit Private Comment"
                        ></flux:button>
                        <flux:button 
                            icon="pencil" 
                            variant="primary" 
                            wire:click="confirmStatusChange({{ $order->id }})"
                            title="Change Order Status"
                        ></flux:button>
                        <flux:button 
                            icon="banknotes" 
                            variant="filled" 
                            wire:click="confirmPaymentStatusChange({{ $order->id }})"
                            title="Change Payment Status"
                        ></flux:button>
                        @if ($order->payment_proof)
                        <flux:button 
                            icon="credit-card" 
                            variant="ghost"
                            href="{{ route('admin-payment-proof', [
                                    'orderId' => $order->id,
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
                    {{ $order->email ?? 'N/A' }}<br>
                    <small class="text-neutral-600 dark:text-neutral-400">{{ $order->name ?? '' }}</small>
                </flux:table.cell>
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
                        'refunded' => 'blue',
                        'failed' => 'red',
                        default => 'zinc'
                    }">
                        {{ $this->getPaymentStatusOptions[$order->payment_status] ?? $order->payment_status }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell class="text-center">
                    <div class="flex flex-col items-center">
                        <span>{{ $this->getTotalItems($order) }} items</span>
                        <small class="text-xs text-neutral-500">
                            {{ $order->items->count() }} unique
                        </small>
                    </div>
                </flux:table.cell>
                <flux:table.cell>{{ format_rupiah($order->total_cost) }}</flux:table.cell>
                <flux:table.cell>
                    @if($order->payment_method)
                        {{ ucwords(str_replace('_', ' ', $order->payment_method)) }}
                    @else
                        <span class="text-gray-400">Not specified</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    @if($order->comments_public)
                        <div class="line-clamp-2" title="{{ $order->comments_public }}">
                            {{ $order->comments_public }}
                        </div>
                    @else
                        <span class="text-gray-400">No comment</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    @if($order->comments_private)
                        <div class="line-clamp-2" title="{{ $order->comments_private }}">
                            {{ $order->comments_private }}
                        </div>
                    @else
                        <span class="text-gray-400">No comment</span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>{{ $order->created_at->format('Y-m-d H:i') }}</flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="change-status-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Change Order Status</flux:heading>
                <flux:text class="mt-2">
                    @if($this->orderToChangeStatus)
                        <p>You're about to change status for order <strong>{{ $this->orderToChangeStatus->code }}</strong>.</p>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                            Customer: {{ $this->orderToChangeStatus->user->name ?? 'N/A' }} ({{ $this->orderToChangeStatus->user->email ?? 'N/A' }})
                        </p>
                    @endif
                </flux:text>
                
                <div class="mt-4">
                    <flux:radio.group wire:model="newStatus" label="Select new status">
                        @foreach($this->getOrderStatusOptions as $value => $label)
                            <flux:radio 
                                value="{{ $value }}" 
                                label="{{ $label }}" 
                                :checked="$value === ($this->orderToChangeStatus->order_status ?? '')" 
                            />
                        @endforeach
                    </flux:radio.group>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="primary" 
                    wire:click="updateOrderStatus"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Update Status</span>
                    <span wire:loading>Updating...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- NEW: Modal for changing payment status -->
    <flux:modal name="change-payment-status-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Change Payment Status</flux:heading>
                <flux:text class="mt-2">
                    @if($this->orderToChangePaymentStatus)
                        <p>You're about to change payment status for order <strong>{{ $this->orderToChangePaymentStatus->code }}</strong>.</p>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                            Customer: {{ $this->orderToChangePaymentStatus->user->name ?? 'N/A' }} ({{ $this->orderToChangePaymentStatus->user->email ?? 'N/A' }})
                        </p>
                    @endif
                </flux:text>
                
                <div class="mt-4">
                    <flux:radio.group wire:model="newPaymentStatus" label="Select new payment status">
                        @foreach($this->getPaymentStatusOptions as $value => $label)
                            <flux:radio 
                                value="{{ $value }}" 
                                label="{{ $label }}" 
                                :checked="$value === ($this->orderToChangePaymentStatus->payment_status ?? '')" 
                            />
                        @endforeach
                    </flux:radio.group>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="primary" 
                    wire:click="updatePaymentStatus"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Update Status</span>
                    <span wire:loading>Updating...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="edit-public-comment-modal" class="min-w-[30rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Public Comment</flux:heading>
                <flux:text class="mt-2">
                    @if($this->orderToEditComment)
                        <p>Editing public comment for order <strong>{{ $this->orderToEditComment->code }}</strong>.</p>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                            This comment will be visible to the customer.
                        </p>
                    @endif
                </flux:text>
                
                <div class="mt-4">
                    <flux:label>Public Comment</flux:label>
                    <flux:textarea 
                        wire:model="newPublicComment" 
                        placeholder="Enter public comment (visible to customer)..."
                        rows="4"
                    ></flux:textarea>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">Max 500 characters</flux:text>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="primary" 
                    wire:click="updatePublicComment"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Update Comment</span>
                    <span wire:loading>Updating...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="edit-private-comment-modal" class="min-w-[30rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Private Comment</flux:heading>
                <flux:text class="mt-2">
                    @if($this->orderToEditComment)
                        <p>Editing private comment for order <strong>{{ $this->orderToEditComment->code }}</strong>.</p>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                            This comment is only visible to administrators.
                        </p>
                    @endif
                </flux:text>
                
                <div class="mt-4">
                    <flux:label>Private Comment</flux:label>
                    <flux:textarea 
                        wire:model="newPrivateComment" 
                        placeholder="Enter private comment (admin only)..."
                        rows="4"
                    ></flux:textarea>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">Max 500 characters</flux:text>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="filled" 
                    wire:click="updatePrivateComment"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Update Comment</span>
                    <span wire:loading>Updating...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="text-center py-8">
        <p class="text-neutral-600 dark:text-neutral-400">No orders found. You've been redirected to the last available page.</p>
    </div>
    @endif
</div>