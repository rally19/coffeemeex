<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use App\Models\{CartItem, Item};
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('My Cart')]
class extends Component {
    
    public array $quantityUpdates = [];

    public function mount()
    {
        if (!Auth::check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }
    }

    public function cartItems()
    {
        return CartItem::query()
            ->with(['item' => function($query) {
                $query->with('tags');
            }])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cartItem) {
                $cartItem->is_available = $cartItem->item?->status === 'available' && $cartItem->item?->stock > 0;
                $cartItem->available_stock = $cartItem->item?->stock ?? 0;
                $cartItem->max_quantity = min($cartItem->available_stock, 99);
                return $cartItem;
            });
    }

    public function subtotal()
    {
        return $this->cartItems()->sum('cost');
    }

    public function totalQuantity()
    {
        return $this->cartItems()->sum('quantity');
    }

    public function updateQuantity($cartItemId, $quantity)
    {
        $cartItem = CartItem::with('item')->find($cartItemId);
        
        if (!$cartItem) {
            Flux::toast(
                heading: 'Item not found',
                text: 'The cart item was not found.',
                variant: 'error',
                duration: 3000
            );
            return;
        }

        $quantity = (int) $quantity;
        
        // Validate quantity
        if ($quantity < 1) {
            $this->removeItem($cartItemId);
            return;
        }

        if ($quantity > $cartItem->item->stock) {
            Flux::toast(
                heading: 'Insufficient stock',
                text: "Only {$cartItem->item->stock} items available in stock.",
                variant: 'warning',
                duration: 3000
            );
            $quantity = $cartItem->item->stock;
        }

        if ($quantity > 99) {
            $quantity = 99;
            Flux::toast(
                heading: 'Maximum quantity reached',
                text: 'Maximum quantity per item is 99.',
                variant: 'warning',
                duration: 3000
            );
        }

        $cartItem->update([
            'quantity' => $quantity,
            'cost' => $quantity * $cartItem->item->price
        ]);

        unset($this->quantityUpdates[$cartItemId]);

        $this->dispatch('cartUpdated');
        
        Flux::toast(
            heading: 'Quantity updated',
            text: 'Cart item quantity has been updated.',
            variant: 'success',
            duration: 2000
        );
    }

    public function incrementQuantity($cartItemId)
    {
        $cartItem = CartItem::with('item')->find($cartItemId);
        
        if (!$cartItem) {
            return;
        }

        $newQuantity = $cartItem->quantity + 1;
        
        if ($newQuantity > $cartItem->item->stock) {
            Flux::toast(
                heading: 'Insufficient stock',
                text: "Only {$cartItem->item->stock} items available in stock.",
                variant: 'warning',
                duration: 3000
            );
            return;
        }

        if ($newQuantity > 99) {
            Flux::toast(
                heading: 'Maximum quantity reached',
                text: 'Maximum quantity per item is 99.',
                variant: 'warning',
                duration: 3000
            );
            return;
        }

        $cartItem->update([
            'quantity' => $newQuantity,
            'cost' => $newQuantity * $cartItem->item->price
        ]);

        $this->dispatch('cartUpdated');

        Flux::toast(
            heading: 'Quantity updated',
            text: 'Cart item quantity has been increased.',
            variant: 'success',
            duration: 2000
        );
    }

    public function decrementQuantity($cartItemId)
    {
        $cartItem = CartItem::find($cartItemId);
        
        if (!$cartItem) {
            return;
        }

        $newQuantity = $cartItem->quantity - 1;
        
        if ($newQuantity < 1) {
            $this->removeItem($cartItemId);
            return;
        }

        $cartItem->update([
            'quantity' => $newQuantity,
            'cost' => $newQuantity * ($cartItem->item->price ?? 0)
        ]);

        $this->dispatch('cartUpdated');

        Flux::toast(
            heading: 'Quantity updated',
            text: 'Cart item quantity has been decreased.',
            variant: 'success',
            duration: 2000
        );
    }

    public function removeItem($cartItemId)
    {
        $cartItem = CartItem::with('item')->find($cartItemId);
        
        if (!$cartItem) {
            return;
        }

        $itemName = $cartItem->item?->name ?? 'Item';
        $cartItem->delete();

        $this->dispatch('cartUpdated');

        Flux::toast(
            heading: 'Item removed',
            text: "{$itemName} has been removed from your cart.",
            variant: 'success',
            duration: 2000
        );
    }

    public function clearCart()
    {
        $count = CartItem::where('user_id', Auth::id())->count();
        
        if ($count === 0) {
            Flux::toast(
                heading: 'Cart is already empty',
                text: 'There are no items in your cart.',
                variant: 'info',
                duration: 3000
            );
            return;
        }

        CartItem::where('user_id', Auth::id())->delete();

        $this->dispatch('cartUpdated');

        Flux::toast(
            heading: 'Cart cleared',
            text: "All {$count} item(s) have been removed from your cart.",
            variant: 'success',
            duration: 3000
        );
    }

    public function checkout()
    {
        $cartItems = $this->cartItems();
        
        if ($cartItems->isEmpty()) {
            Flux::toast(
                heading: 'Cart is empty',
                text: 'Please add items to your cart before checking out.',
                variant: 'warning',
                duration: 3000
            );
            return;
        }

        // Check if all items are available
        $unavailableItems = $cartItems->filter(function ($cartItem) {
            return !($cartItem->item?->status === 'available' && $cartItem->item?->stock >= $cartItem->quantity);
        });

        if ($unavailableItems->isNotEmpty()) {
            $itemNames = $unavailableItems->pluck('item.name')->implode(', ');
            Flux::toast(
                heading: 'Unavailable items',
                text: "The following items are no longer available: {$itemNames}",
                variant: 'error',
                duration: 5000
            );
            return;
        }

        // Redirect to checkout page (to be created later)
        $this->redirect(route('checkout'), navigate: true);
    }

    public function continueShopping()
    {
        $this->redirect(route('menu'), navigate: true);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div><flux:heading size="xl">My Cart</flux:heading></div>
        <div class="flex items-center gap-4">
            @if($this->cartItems()->count() > 0)
            <div>
                <flux:button type="button" wire:click="clearCart" variant="danger">
                    Clear Cart
                </flux:button>
            </div>
            @endif
            <div>
                <flux:button :href="route('menu')" wire:navigate variant="ghost">
                    Continue Shopping
                </flux:button>
            </div>
        </div>
    </div>
    
    @if($this->cartItems()->count() > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column: Cart items -->
        <div class="lg:col-span-2">
            <div class="space-y-4">
                @foreach ($this->cartItems() as $cartItem)
                <div class="p-4 outline rounded-lg {{ !$cartItem->is_available ? 'bg-gray-50' : '' }}">
                    <div class="flex items-start gap-4">
                        <!-- Item thumbnail -->
                        <div class="flex-shrink-0">
                            @if($cartItem->item?->thumbnail_pic)
                                <img src="{{ asset('storage/' . $cartItem->item->thumbnail_pic) }}" 
                                    class="w-20 h-20 object-cover rounded-md"
                                    alt="{{ $cartItem->item->name }}">
                            @else
                                <div class="w-20 h-20 outline flex items-center justify-center rounded-md">
                                    <flux:icon.photo class="w-8 h-8 text-gray-400" />
                                </div>
                            @endif
                        </div>
                        
                        <!-- Item details -->
                        <div class="flex-grow">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <flux:heading size="md">
                                            {{ $cartItem->item?->name ?? 'Unknown Item' }}
                                        </flux:heading>
                                        @if(!$cartItem->is_available)
                                            <flux:badge color="red" size="sm">Unavailable</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-sm text-neutral-500 mb-2">
                                        {{ $cartItem->item?->code ?? 'N/A' }}
                                    </div>
                                    <div class="text-lg font-bold text-primary-600">
                                        {{ format_rupiah($cartItem->item?->price ?? 0) }}
                                    </div>
                                </div>
                                
                                <!-- Quantity controls -->
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center">
                                        @if($cartItem->is_available)
                                            <flux:button 
                                                icon="minus" 
                                                size="sm" 
                                                variant="ghost"
                                                wire:click="decrementQuantity({{ $cartItem->id }})"
                                            />
                                        @else
                                            <flux:button 
                                                icon="minus" 
                                                size="sm" 
                                                variant="ghost"
                                                disabled
                                            />
                                        @endif
                                        
                                        <input 
                                            type="number" 
                                            min="1" 
                                            max="{{ $cartItem->max_quantity }}"
                                            value="{{ $cartItem->quantity }}"
                                            wire:change="updateQuantity({{ $cartItem->id }}, $event.target.value)"
                                            class="w-16 text-center border rounded-md py-1 px-2 {{ !$cartItem->is_available ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                            @if(!$cartItem->is_available) disabled @endif
                                            wire:key="cart-item-{{ $cartItem->id }}-{{ $cartItem->quantity }}"
                                        />
                                        
                                        @if($cartItem->is_available && $cartItem->quantity < $cartItem->max_quantity)
                                            <flux:button 
                                                icon="plus" 
                                                size="sm" 
                                                variant="ghost"
                                                wire:click="incrementQuantity({{ $cartItem->id }})"
                                            />
                                        @else
                                            <flux:button 
                                                icon="plus" 
                                                size="sm" 
                                                variant="ghost"
                                                disabled
                                            />
                                        @endif
                                    </div>
                                    
                                    <!-- Remove button -->
                                    <flux:button 
                                        icon="trash" 
                                        size="sm" 
                                        variant="danger"
                                        wire:click="removeItem({{ $cartItem->id }})"
                                    />
                                </div>
                            </div>
                            
                            <!-- Stock info -->
                            <div class="mt-2">
                                @if($cartItem->is_available)
                                    <div class="flex items-center gap-2 text-sm text-success-600">
                                        <flux:icon.check-circle class="w-4 h-4" />
                                        <span>In stock: {{ $cartItem->available_stock }} available</span>
                                    </div>
                                @else
                                    <div class="flex items-center gap-2 text-sm text-danger-600">
                                        <flux:icon.x-circle class="w-4 h-4" />
                                        <span>
                                            @if($cartItem->item?->status !== 'available')
                                                {{ ucfirst($cartItem->item?->status ?? 'unavailable') }}
                                            @else
                                                Out of stock
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                
                            </div>
                            
                            <!-- Item  -->
                            <div class="mt-2 flex items-center justify-between">
                                <flux:button :href="route('menu.item', ['code' => $cartItem->item->code])" wire:navigate>Details</flux:button>
                                <div class="text-right">
                                    <div class="text-lg font-bold">
                                        Total: {{ format_rupiah($cartItem->cost) }}
                                    </div>
                                    <div class="text-sm text-neutral-500">
                                        {{ $cartItem->quantity }} × {{ format_rupiah($cartItem->item?->price ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <!-- Right column: Order summary -->
        <div class="lg:col-span-1">
            <div class="sticky top-6">
                <div class="p-6 outline rounded-lg shadow">
                    <flux:heading size="lg" class="mb-6">Order Summary</flux:heading>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-neutral-600 dark:text-neutral-400">Items in Cart</span>
                            <span class="font-medium">{{ $this->cartItems()->count() }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-neutral-600 dark:text-neutral-400">Total Quantity</span>
                            <span class="font-medium">{{ $this->totalQuantity() }}</span>
                        </div>
                        
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Subtotal</span>
                                <span class="text-2xl font-bold text-primary-600">
                                    {{ format_rupiah($this->subtotal()) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 space-y-4">
                        <flux:button 
                            type="button" 
                            variant="primary" 
                            wire:click="checkout"
                            class="w-full"
                        >
                            Proceed to Checkout
                        </flux:button>

                        <flux:button 
                            :href="route('menu')" 
                            wire:navigate
                            variant="ghost" 
                            class="w-full"
                        >
                            Continue Shopping
                        </flux:button>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t">
                        <div class="text-sm text-neutral-600 dark:text-neutral-400">
                            <p class="mb-2">By proceeding to checkout, you agree to our Terms of Service and Privacy Policy.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Empty cart state -->
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <flux:icon.shopping-cart class="w-24 h-24 mx-auto" />
        </div>
        <flux:heading size="xl" class="mb-4">Your cart is empty</flux:heading>
        <p class="text-neutral-600 dark:text-neutral-400 text-lg mb-8">
            Looks like you haven't added any items to your cart yet.
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
                    :href="route('dashboard')" 
                    wire:navigate
                    variant="ghost"
                >
                    Go to Dashboard
                </flux:button>
            </div>
        </div>
    </div>
    @endif
</div>