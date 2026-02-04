<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use App\Models\{CartItem, Order, OrderItem};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Checkout')]
class extends Component {

    use WithFileUploads; 
    
    public array $cartItems = [];
    public float $total = 0;
    public string $paymentMethod = 'bank_transfer';
    public $paymentProof;
    public string $address = '';
    public string $notes = '';
    
    public function mount(): void
    {
        if (!Auth::check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }
        
        $this->loadCartItems();
        
        $this->address = Auth::user()->address ?? '';
    }
    
    public function loadCartItems(): void
    {
        $this->cartItems = CartItem::query()
            ->with(['item' => function($query) {
                $query->with('tags');
            }])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cartItem) {
                $cartItem->is_available = $cartItem->item?->status === 'available' && $cartItem->item?->stock >= $cartItem->quantity;
                $cartItem->available_stock = $cartItem->item?->stock ?? 0;
                return $cartItem;
            })
            ->toArray();
        
        $this->calculateTotal();
    }
    
    public function calculateTotal(): void
    {
        $this->total = 0;
        
        foreach ($this->cartItems as $cartItem) {
            if ($cartItem['is_available']) {
                $this->total += $cartItem['cost'];
            }
        }
    }
    
    public function submitOrder(): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:bank_transfer,credit_card,e_wallet,cash'],
            'paymentProof' => ['required_if:paymentMethod,bank_transfer,credit_card,e_wallet', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        
        DB::transaction(function() {
            // Reload cart items with fresh data
            $freshCartItems = CartItem::with('item')
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->get();
            
            // Check if all items are still available
            $unavailableItems = [];
            foreach ($freshCartItems as $cartItem) {
                if (!$cartItem->item || 
                    $cartItem->item->status !== 'available' || 
                    $cartItem->item->stock < $cartItem->quantity) {
                    $unavailableItems[] = $cartItem->item?->name ?? 'Unknown Item';
                }
            }
            
            if (!empty($unavailableItems)) {
                Flux::toast(
                    heading: 'Unavailable items',
                    text: 'Some items are no longer available. Please update your cart.',
                    variant: 'error',
                    duration: 5000
                );
                $this->redirect(route('cart'), navigate: true);
                return;
            }
            
            // Check if cart is empty
            if ($freshCartItems->isEmpty()) {
                Flux::toast(
                    heading: 'Cart is empty',
                    text: 'Your cart is empty. Please add items to your cart.',
                    variant: 'warning',
                    duration: 3000
                );
                $this->redirect(route('cart'), navigate: true);
                return;
            }
            
            // Handle payment proof upload
            $paymentProofPath = null;
            if ($this->paymentProof && in_array($this->paymentMethod, ['bank_transfer', 'credit_card', 'e_wallet'])) {
                $paymentProofPath = $this->paymentProof->store('payment_proofs', 'local');
            }
            
            // Generate order code
            $orderCount = Order::whereDate('created_at', now())->count();
            $orderCode = 'ORD-' . now()->format('Y-m-d') . '-' . Str::upper(Str::random(6)) . '-' . ($orderCount + 1);
            
            // Get current user data
            $user = Auth::user();
            
            // Create order with user data
            $order = Order::create([
                'code' => $orderCode,
                'user_id' => Auth::id(),
                'name' => $user->name,
                'email' => $user->email,
                'phone_numbers' => $user->phone_numbers,
                'order_status' => 'pending',
                'payment_status' => $this->paymentMethod === 'cash' ? 'pending' : 'pending',
                'payment_method' => $this->paymentMethod,
                'payment_proof' => $paymentProofPath,
                'comments_public' => null,
                'comments_private' => null,
                'address' => $this->address,
                'notes' => $this->notes,
                'total_cost' => $this->total,
            ]);
            
            // Create order items and update stock
            foreach ($freshCartItems as $cartItem) {
                // Generate order item code
                $orderItemCount = OrderItem::where('item_id', $cartItem->item_id)->count();
                $orderItemCode = 'ORD-' . $cartItem->item->code . '-' . Str::upper(Str::random(6)) . '-' . ($orderItemCount + 1);
                
                OrderItem::create([
                    'code' => $orderItemCode,
                    'order_id' => $order->id,
                    'item_id' => $cartItem->item_id,
                    'quantity' => $cartItem->quantity,
                    'cost' => $cartItem->cost,
                    'item_code' => $cartItem->item->code,
                    'item_name' => $cartItem->item->name,
                    'item_price' => $cartItem->item->price,
                ]);
                
                // Update item stock
                $cartItem->item->decrement('stock', $cartItem->quantity);
            }
            
            // Clear cart
            CartItem::where('user_id', Auth::id())->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'Order Placed Successfully',
                text: 'Your order has been submitted successfully',
                duration: 5000,
            );
            
            $this->redirect(route('dashboard', $order->id), navigate: true);
        });
    }
    
    public function backToCart(): void
    {
        $this->redirect(route('cart'), navigate: true);
    }

    public function getUnavailableItemsProperty()
    {
        return array_filter($this->cartItems, function($item) {
            return !$item['is_available'];
        });
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Checkout</flux:heading>
    
    @if(count($cartItems) > 0)
    <form wire:submit.prevent="submitOrder" class="space-y-8">
        <div class="p-6 outline rounded-lg">
            <flux:heading size="lg" class="mb-4">Order Summary</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 mb-4">
                Review your order. To make changes, go back to your cart.
            </flux:text>
            
            <div class="space-y-4">
                @foreach($cartItems as $cartItem)
                <div class="p-4 outline rounded-lg {{ !$cartItem['is_available'] ? 'bg-danger-50' : '' }}">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @if($cartItem['item']['thumbnail_pic'] ?? false)
                                <img src="{{ asset('storage/' . $cartItem['item']['thumbnail_pic']) }}" 
                                    class="w-16 h-16 object-cover rounded-md"
                                    alt="{{ $cartItem['item']['name'] }}">
                            @else
                                <div class="w-16 h-16 outline flex items-center justify-center rounded-md">
                                    <flux:icon.photo class="w-8 h-8 text-gray-400" />
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex-grow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <flux:heading size="md">
                                            {{ $cartItem['item']['name'] ?? 'Unknown Item' }}
                                        </flux:heading>
                                        @if(!$cartItem['is_available'])
                                            <flux:badge color="red" size="sm">Unavailable</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-sm text-neutral-500 mb-2">
                                        {{ $cartItem['item']['code'] ?? 'N/A' }}
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">Price:</span>
                                                <span class="text-primary-600 font-medium">
                                                    {{ format_rupiah($cartItem['item']['price'] ?? 0) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">Quantity:</span>
                                                    {{ $cartItem['quantity'] }}
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-1 text-right">
                                            <div class="font-bold text-lg">
                                                {{ format_rupiah($cartItem['cost']) }}
                                            </div>
                                            <div class="text-sm text-neutral-500">
                                                {{ $cartItem['quantity'] }} × {{ format_rupiah($cartItem['item']['price'] ?? 0) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if(!$cartItem['is_available'])
                            <div class="mt-2 p-2 bg-danger-50 border border-danger-200 rounded">
                                <div class="flex items-center gap-2 text-danger-700 text-sm">
                                    <flux:icon.x-circle class="w-4 h-4" />
                                    <span>
                                        @if($cartItem['item']['status'] !== 'available')
                                            Item is {{ ucfirst($cartItem['item']['status']) }}
                                        @elseif($cartItem['item']['stock'] < $cartItem['quantity'])
                                            Only {{ $cartItem['item']['stock'] }} in stock (requested: {{ $cartItem['quantity'] }})
                                        @else
                                            Item is no longer available
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="mt-6 pt-6 border-t">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-xl font-semibold">Total Amount</span>
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            {{ count($cartItems) }} item(s)
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-primary-600">
                        {{ format_rupiah($total) }}
                    </span>
                </div>
            </div>
            
            @if(count($this->unavailableItems) > 0)
            <div class="mt-4 p-4 bg-danger-50 border border-danger-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <div class="text-danger-600">
                        <flux:icon.exclamation-triangle class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="font-medium text-danger-800">Cannot Checkout</div>
                        <div class="text-sm text-danger-700 mt-1">
                            Some items in your cart are no longer available. Please go back to your cart to remove them or update quantities.
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <div class="p-6 outline rounded-lg">
            <flux:heading size="lg" class="mb-4">Shipping Information</flux:heading>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:label>Name</flux:label>
                        <flux:input 
                            value="{{ auth()->user()->name }}"
                            readonly
                            disabled
                        />
                    </div>
                    <div>
                        <flux:label>Email</flux:label>
                        <flux:input 
                            value="{{ auth()->user()->email }}"
                            readonly
                            disabled
                        />
                    </div>
                </div>
                
                <div>
                    <flux:label>Phone Number</flux:label>
                    <flux:input 
                        value="{{ auth()->user()->phone_numbers ?? 'Not provided' }}"
                        readonly
                        disabled
                    />
                    <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        @if(!auth()->user()->phone_numbers)
                            <a href="{{ route('settings.profile') }}" class="text-primary-600 hover:underline">
                                Add phone number in your profile
                            </a>
                        @endif
                    </div>
                </div>
                
                <div>
                    <flux:label>Shipping Address</flux:label>
                    <flux:textarea 
                        wire:model="address" 
                        required
                        rows="3"
                        placeholder="Enter your complete shipping address"
                    />
                    @error('address') <div class="mt-1 text-sm text-danger-600">{{ $message }}</div> @enderror
                </div>
                
                <div>
                    <flux:label>Order Notes (Optional)</flux:label>
                    <flux:textarea 
                        wire:model="notes" 
                        rows="2"
                        placeholder="Any special instructions or notes for your order"
                    />
                </div>
            </div>
        </div>
        
        <div class="p-6 outline rounded-lg">
            <flux:heading size="lg" class="mb-4">Payment Information</flux:heading>
            
            <div class="space-y-6">
                <div>
                    <flux:label>Payment Method</flux:label>
                    <flux:select wire:model.live="paymentMethod" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="e_wallet">E-Wallet</option>
                        <option value="cash">Cash on Delivery</option>
                    </flux:select>
                </div>
                
                @if(in_array($paymentMethod, ['bank_transfer', 'credit_card', 'e_wallet']))
                <div>
                    <flux:label>Payment Proof</flux:label>
                    <div class="flex items-center gap-4">
                        @if($paymentProof)
                            <div class="flex items-center gap-2">
                                <flux:icon.document-text class="w-6 h-6 text-primary-600" />
                                <span class="text-sm">{{ $paymentProof->getClientOriginalName() }}</span>
                            </div>
                        @endif
                        <flux:input
                            type="file"
                            wire:model="paymentProof"
                            accept="image/*,.pdf"
                            class="truncate"
                        />
                    </div>
                    @error('paymentProof') <div class="mt-1 text-sm text-danger-600">{{ $message }}</div> @enderror
                    <div class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                        Upload screenshot or PDF of your payment receipt.
                    </div>
                </div>
                
                <div class="p-4 bg-gray-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="text-gray-200">
                            <flux:icon.information-circle class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="font-medium text-gray-200">Payment Instructions</div>
                            <div class="mt-2 text-sm text-gray-100">
                                @if($paymentMethod === 'bank_transfer')
                                    Please transfer the exact amount to:<br>
                                    Bank: Bank Indonesia<br>
                                    Account Number: 1234567890<br>
                                    Account Name: Your Store Name<br>
                                    Amount: {{ format_rupiah($total) }}<br>
                                    <br>
                                    After payment, please upload your payment proof above.
                                @elseif($paymentMethod === 'credit_card')
                                    Credit card payments are processed through our secure payment gateway.<br>
                                    You will be redirected to the payment page after order confirmation.<br>
                                    <br>
                                    For manual payment, upload your payment confirmation above.
                                @elseif($paymentMethod === 'e_wallet')
                                    Scan the QR code or transfer to:<br>
                                    E-Wallet Provider: Dana/OVO/GoPay<br>
                                    Phone Number: +62 876 5432 1098<br>
                                    Amount: {{ format_rupiah($total) }}<br>
                                    <br>
                                    Upload your payment confirmation screenshot above.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($paymentMethod === 'cash')
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="text-green-600">
                            <flux:icon.check-circle class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="font-medium text-green-800">Cash</div>
                            <div class="mt-1 text-sm text-green-700">
                                No online payment required.
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <div class="flex justify-between pt-4 border-t">
            <flux:button 
                type="button" 
                wire:click="backToCart" 
                variant="ghost"
            >
                Back to Cart
            </flux:button>
            
            <flux:button 
                type="submit" 
                variant="primary"
                :disabled="count($this->unavailableItems) > 0"
            >
                <span wire:loading.remove wire:target="submitOrder">
                    Place Order
                </span>
                <span wire:loading wire:target="submitOrder">
                    <flux:icon.loading class="w-5 h-5" />
                </span>
            </flux:button>
        </div>
    </form>
    @else
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <flux:icon.shopping-cart class="w-24 h-24 mx-auto" />
        </div>
        <flux:heading size="xl" class="mb-4">Your cart is empty</flux:heading>
        <p class="text-neutral-600 dark:text-neutral-400 text-lg mb-8">
            Add some items to your cart before checking out.
        </p>
        <div class="space-y-4">
            <flux:button 
                :href="route('menu')" 
                wire:navigate
                variant="primary" 
            >
                Start Shopping
            </flux:button>
            <div>
                <flux:button 
                    :href="route('cart')" 
                    wire:navigate
                    variant="ghost"
                >
                    Go to Cart
                </flux:button>
            </div>
        </div>
    </div>
    @endif
</div>