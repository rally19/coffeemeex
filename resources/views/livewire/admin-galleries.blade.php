<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Tag;
use App\Models\TagType;
use Carbon\Carbon;

new #[Layout('components.layouts.admin')]
    #[Title('Dashboard')]
class extends Component {
    public $stats = [];
    public array $salesData = [];
    public array $orderStatusData = [];
    public array $revenueData = [];
    public array $popularItemsData = [];

    public function mount()
    {
        // Calculate last 30 days for charts
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        // Basic stats
        $this->stats = [
            [
                'title' => 'Total Items',
                'count' => Item::count(),
                'icon' => 'tag',
            ],
            [
                'title' => 'Total Orders',
                'count' => Order::count(),
                'icon' => 'shopping-cart',
            ],
            [
                'title' => 'Pending Orders',
                'count' => Order::where('order_status', 'pending')->count(),
                'icon' => 'clock',
            ],
            [
                'title' => 'Total Users',
                'count' => User::count(),
                'icon' => 'users',
            ],
            [
                'title' => 'Total Revenue',
                'count' => format_rupiah(Order::where('payment_status', 'paid')->sum('total_cost')),
                'icon' => 'currency-dollar',
            ],
            [
                'title' => 'Paid Orders',
                'count' => Order::where('payment_status', 'paid')->count(),
                'icon' => 'check-circle',
            ],
            [
                'title' => 'Available Items',
                'count' => Item::where('status', 'available')->count(),
                'icon' => 'check-badge',
            ],
            [
                'title' => 'Total Tags',
                'count' => Tag::count(),
                'icon' => 'tag',
            ],
        ];

        // Sales over time data (last 30 days)
        $this->salesData = $this->getSalesData($startDate, $endDate);
        
        // Order status distribution
        $this->orderStatusData = $this->getOrderStatusData();
        
        // Revenue data (last 30 days)
        $this->revenueData = $this->getRevenueData($startDate, $endDate);
        
        // Popular items data
        $this->popularItemsData = $this->getPopularItemsData();
    }

    private function getSalesData($startDate, $endDate)
    {
        // Get orders grouped by day for the last 30 days
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days
        $data = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $orderCount = $orders->firstWhere('date', $dateString);
            
            $data[] = [
                'date' => $dateString,
                'orders' => $orderCount ? $orderCount->count : 0,
            ];
            
            $currentDate->addDay();
        }
        
        return $data;
    }

    private function getOrderStatusData()
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled', 'failed'];
        $data = [];
        
        foreach ($statuses as $status) {
            $count = Order::where('order_status', $status)->count();
            if ($count > 0) {
                $data[] = [
                    'status' => ucfirst($status),
                    'count' => $count,
                ];
            }
        }
        
        return $data;
    }

    private function getRevenueData($startDate, $endDate)
    {
        // Get revenue grouped by day for the last 30 days
        $revenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(total_cost) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days
        $data = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $revenueData = $revenue->firstWhere('date', $dateString);
            
            $data[] = [
                'date' => $dateString,
                'revenue' => $revenueData ? (float)$revenueData->revenue : 0,
            ];
            
            $currentDate->addDay();
        }
        
        return $data;
    }

    private function getPopularItemsData()
    {
        // Get top 5 popular items by quantity sold
        $popularItems = OrderItem::whereHas('order', function ($query) {
                $query->where('order_status', 'completed')
                    ->where('payment_status', 'paid');
            })
            ->select('item_id', 'item_name')
            ->selectRaw('SUM(quantity) as total_quantity, SUM(cost) as total_revenue')
            ->groupBy('item_id', 'item_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        $data = [];
        foreach ($popularItems as $item) {
            $data[] = [
                'name' => $item->item_name,
                'quantity' => (int)$item->total_quantity,
                'revenue' => (float)$item->total_revenue,
            ];
        }
        
        return $data;
    }
}; 
?>

<div>
    <!-- Welcome Section -->
    <div class="text-center">
        <flux:heading size="xl">WELCOME BACK
            @if ((auth()->user()->role ?? '') === 'admin')
                ADMIN
            @else
                STAFF
            @endif
            {{ Auth::user()->name }}!
        </flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400 mt-2">
            {{ now()->format('l, F j, Y') }}
        </flux:text>
    </div>
    
    <br><br>
    <flux:separator text="DASHBOARD OVERVIEW" />
    <br><br>
    
    <!-- Stats Cards with Mini Charts -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($stats as $stat)
            <flux:card class="overflow-hidden min-w-[12rem] relative">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-zinc-500 dark:text-zinc-400">{{ $stat['title'] }}</flux:text>
                        <flux:heading size="xl" class="mt-2 tabular-nums">{{ $stat['count'] }}</flux:heading>
                    </div>
                    <x-icon :name="$stat['icon']" class="w-8 h-8 text-zinc-300 dark:text-zinc-600" />
                </div>
            </flux:card>
        @endforeach
    </div>
    
    <br><br>
    
    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Sales Over Time Chart -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Sales Trend (Last 30 Days)</flux:heading>
            <flux:chart wire:model="salesData" class="aspect-3/1">
                <flux:chart.svg>
                    <flux:chart.line field="orders" class="text-blue-500 dark:text-blue-400" />
                    <flux:chart.area field="orders" class="text-blue-200/50 dark:text-blue-400/20" />
                    <flux:chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.cursor />
                </flux:chart.svg>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" :format="['month' => 'long', 'day' => 'numeric', 'year' => 'numeric']" />
                    <flux:chart.tooltip.value field="orders" label="Orders" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>
        
        <!-- Revenue Chart -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Revenue (Last 30 Days)</flux:heading>
            <flux:chart wire:model="revenueData" class="aspect-3/1">
                <flux:chart.svg>
                    <flux:chart.line field="revenue" class="text-green-500 dark:text-green-400" curve="none" />
                    <flux:chart.area field="revenue" class="text-green-200/50 dark:text-green-400/20" curve="none" />
                    <flux:chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y" tick-prefix="₱" :format="[
                        'notation' => 'compact',
                        'compactDisplay' => 'short',
                        'maximumFractionDigits' => 1,
                    ]">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.cursor />
                </flux:chart.svg>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" :format="['month' => 'long', 'day' => 'numeric', 'year' => 'numeric']" />
                    <flux:chart.tooltip.value field="revenue" label="Revenue" :format="['style' => 'currency', 'currency' => 'PHP']" />
                </flux:chart.tooltip>
            </flux:chart>
        </flux:card>
        
        <!-- Order Status Distribution -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Order Status Distribution</flux:heading>
            @if(!empty($orderStatusData))
                <div class="space-y-4">
                    @foreach($orderStatusData as $status)
                        <div>
                            <div class="flex justify-between mb-1">
                                <flux:text>{{ $status['status'] }}</flux:text>
                                <flux:text class="tabular-nums">{{ $status['count'] }}</flux:text>
                            </div>
                            <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                @php
                                    $total = collect($orderStatusData)->sum('count');
                                    $percentage = $total > 0 ? ($status['count'] / $total) * 100 : 0;
                                    $color = match($status['status']) {
                                        'Pending' => 'bg-yellow-500',
                                        'Processing' => 'bg-blue-500',
                                        'Completed' => 'bg-green-500',
                                        'Cancelled' => 'bg-red-500',
                                        'Failed' => 'bg-zinc-500',
                                        default => 'bg-blue-500',
                                    };
                                @endphp
                                <div class="h-full {{ $color }} rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-zinc-500 dark:text-zinc-400">No order data available</flux:text>
            @endif
        </flux:card>
        
        <!-- Popular Items -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Top Selling Items</flux:heading>
            @if(!empty($popularItemsData))
                <div class="space-y-4">
                    @foreach($popularItemsData as $item)
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium truncate">{{ $item['name'] }}</flux:text>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $item['quantity'] }} sold
                                </flux:text>
                            </div>
                            <flux:text class="font-medium tabular-nums ml-4">
                                {{ format_rupiah($item['revenue']) }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-zinc-500 dark:text-zinc-400">No sales data available</flux:text>
            @endif
        </flux:card>
    </div>
    
    <!-- Recent Orders Table -->
    <br><br>
    <flux:card>
        <flux:heading size="lg" class="mb-4">Recent Orders</flux:heading>
        @php
            $recentOrders = App\Models\Order::with('user')
                ->latest()
                ->limit(10)
                ->get();
        @endphp
        
        @if($recentOrders->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Order Code</flux:table.column>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Payment</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($recentOrders as $order)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:text class="font-mono">{{ $order->code }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text>{{ $order->user->name ?? 'Guest' }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="solid" :color="match($order->order_status) {
                                'pending' => 'yellow',
                                'processing' => 'blue',
                                'completed' => 'green',
                                'cancelled' => 'red',
                                default => 'zinc'
                            }">
                                {{ ucfirst($order->order_status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="solid" :color="match($order->payment_status) {
                                'paid' => 'green',
                                'pending' => 'yellow',
                                'refunded' => 'blue',
                                'failed' => 'red',
                                default => 'zinc'
                            }">
                                {{ ucfirst($order->payment_status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="font-medium tabular-nums">
                                {{ format_rupiah($order->total_cost) }}
                            </flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->created_at->format('M d, Y') }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:text class="text-zinc-500 dark:text-zinc-400">No recent orders</flux:text>
        @endif
    </flux:card>
    
    <!-- Recent Contacts Table -->
    <br><br>
    <flux:card>
        <flux:heading size="lg" class="mb-4">Recent Contacts</flux:heading>
        @php
            $recentContacts = App\Models\Contact::with('user')
                ->latest()
                ->limit(10)
                ->get();
        @endphp
        
        @if($recentContacts->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Phone</flux:table.column>
                    <flux:table.column>Subject</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                    <flux:table.column>Replied</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($recentContacts as $contact)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:text class="font-medium">{{ $contact->name }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text>{{ $contact->email }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text>{{ $contact->phone_numbers ?? 'N/A' }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="truncate max-w-[200px]">{{ $contact->subject }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text>{{ $contact->user->name ?? 'Guest' }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="solid" :color="$contact->replied_at ? 'green' : 'yellow'">
                                {{ $contact->replied_at ? 'Replied' : 'Pending' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $contact->created_at->format('M d, Y') }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:text class="text-zinc-500 dark:text-zinc-400">No recent contacts</flux:text>
        @endif
    </flux:card>
</div>