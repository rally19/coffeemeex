<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\CartItem;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Menu Item Details')]
class extends Component {
    
    public Item $item;
    public int $cartCount = 0;
    public bool $isAvailable = false; // Add this property
    
    public function mount(string $code): void
    {
        $this->item = Item::where('code', $code)
            ->with(['tags', 'tags.type'])
            ->firstOrFail();
            
        // Calculate availability (same logic as menu.blade.php)
        $this->isAvailable = $this->item->status === 'available' && $this->item->stock > 0;
            
        // Get cart count for current user
        if (auth()->check()) {
            $this->cartCount = CartItem::where('user_id', auth()->id())->sum('quantity');
        }
    }
    
    public function addToCart(): void
    {
        if (!auth()->check()) {
            Flux::toast(
                heading: 'Login Required',
                text: 'You need to be logged in to add items to your cart.',
                variant: 'warning',
                duration: 3000
            );
            return;
        }
        
        // Use the calculated $isAvailable instead of checking $item properties directly
        if (!$this->isAvailable) {
            Flux::toast(
                heading: 'Item unavailable',
                text: 'This item is currently unavailable or out of stock.',
                variant: 'error',
                duration: 3000
            );
            return;
        }
        
        // Check if item already in cart
        $cartItem = CartItem::where('user_id', auth()->id())
            ->where('item_id', $this->item->id)
            ->first();
        
        if ($cartItem) {
            // Update quantity if item exists
            if ($cartItem->quantity < $this->item->stock) {
                $cartItem->increment('quantity');
                $cartItem->update([
                    'cost' => $cartItem->quantity * $this->item->price
                ]);
                
                $this->cartCount = CartItem::where('user_id', auth()->id())->sum('quantity');

                $this->dispatch('cartUpdated');
                
                Flux::toast(
                    heading: 'Cart updated',
                    text: 'Item quantity increased in your cart.',
                    variant: 'success',
                    duration: 2000
                );
            } else {
                Flux::toast(
                    heading: 'Stock limit reached',
                    text: 'Cannot add more than available stock.',
                    variant: 'warning',
                    duration: 3000
                );
            }
        } else {
            // Add new item to cart
            CartItem::create([
                'user_id' => auth()->id(),
                'item_id' => $this->item->id,
                'quantity' => 1,
                'cost' => $this->item->price
            ]);
            
            $this->cartCount = CartItem::where('user_id', auth()->id())->sum('quantity');

            $this->dispatch('cartUpdated');
            
            Flux::toast(
                heading: 'Added to cart',
                text: 'Item has been added to your cart.',
                variant: 'success',
                duration: 2000
            );
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:button 
            :href="route('menu')" 
            icon="arrow-left" 
            variant="ghost"
            wire:navigate
        >
            Back to Menu
        </flux:button>
        
        @auth
        <div>
            <flux:button :href="route('cart')" icon="shopping-cart" wire:navigate>
                Cart
                {{-- @if($cartCount > 0)
                    <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                        {{ $cartCount }}
                    </span>
                @endif --}}
                @livewire('cart-count')
            </flux:button>
        </div>
        @endauth
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Item Image Section -->
        <div>
            @if($item->thumbnail_pic)
                <div class="aspect-square w-full rounded-lg overflow-hidden shadow-lg">
                    <img 
                        src="{{ asset('storage/' . $item->thumbnail_pic) }}" 
                        class="w-full h-full object-cover" 
                        alt="{{ $item->name }}"
                    >
                </div>
            @else
                <div class="aspect-square w-full outline outline-offset-[-1px] rounded-lg flex items-center justify-center bg-gray-50">
                    <flux:icon.photo class="w-32 h-32 text-gray-400" />
                </div>
            @endif
        </div>
        
        <!-- Item Details Section -->
        <div class="space-y-6">
            <div>
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading size="lg">{{ $item->name }}</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                            Code: {{ $item->code }}
                        </flux:text>
                    </div>
                    
                    <div class="text-3xl font-bold text-primary-600">
                        {{ format_rupiah($item->price) }}
                    </div>
                </div>
                
                <!-- Stock Status -->
                <div class="mt-4">
                    @if($isAvailable)
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-success-50 text-success-700 rounded-full">
                            <flux:icon.check-circle class="w-5 h-5" />
                            <span class="font-medium">
                                {{ $item->stock }} in stock
                            </span>
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-danger-50 text-danger-700 rounded-full">
                            <flux:icon.x-circle class="w-5 h-5" />
                            <span class="font-medium">
                                @if($item->status !== 'available')
                                    {{ ucfirst($item->status) }}
                                @elseif($item->stock <= 0)
                                    Out of stock
                                @else
                                    Unavailable
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Tags -->
            @if($item->tags->count() > 0)
                <div>
                    <flux:heading size="lg" class="mb-3">Tags</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach($item->tags as $tag)
                            <flux:badge 
                                color="zinc" 
                                variant="solid" 
                                rounded
                            >
                                {{ $tag->name }}
                                @if($tag->type)
                                    <span class="text-xs opacity-75 ml-1">({{ $tag->type->name }})</span>
                                @endif
                            </flux:badge>
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Description -->
            <div>
                <flux:heading size="lg" class="mb-3">Description</flux:heading>
                @if($item->description)
                    <flux:text class="whitespace-pre-line">{{ $item->description }}</flux:text>
                @else
                    <flux:text class="text-neutral-600 dark:text-neutral-400 italic">
                        No description available for this item.
                    </flux:text>
                @endif
            </div>
            
            <!-- Action Buttons -->
            <div class="pt-4 border-t">
                <div class="flex flex-col sm:flex-row gap-4">
                    <flux:button 
                        icon="shopping-cart"
                        variant="primary"
                        wire:click="addToCart"
                        :disabled="!$isAvailable"
                        class="flex-1 justify-center"
                    >
                        <span wire:loading.remove wire:target="addToCart">
                            Add to Cart
                        </span>
                        <span wire:loading wire:target="addToCart">
                            <flux:icon.loading class="w-5 h-5" />
                        </span>
                    </flux:button>
                    
                    <flux:button 
                        :href="route('menu')" 
                        variant="ghost"
                        wire:navigate
                        class="flex-1 justify-center"
                    >
                        Continue Shopping
                    </flux:button>
                </div>
                
                @if(!auth()->check())
                    <div class="mt-4 p-3 bg-warning-50 rounded-lg">
                        <div class="flex items-center gap-2 text-warning-700">
                            <flux:icon.information-circle class="w-5 h-5" />
                            <span class="text-sm">
                                You need to <flux:link :href="route('login')" wire:navigate>log in</flux:link> to add items to your cart.
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Stock Warning -->
    @if($isAvailable && $item->stock > 0 && $item->stock <= 5)
        <div class="mt-8 p-4 bg-orange-50 border border-orange-200 rounded-lg">
            <div class="flex items-center gap-3">
                <flux:icon.exclamation-triangle class="w-6 h-6 text-orange-600" />
                <div>
                    <div class="font-medium text-orange-800">Low Stock Alert</div>
                    <div class="text-sm text-orange-700 mt-1">
                        Only {{ $item->stock }} item(s) left in stock. Order soon to avoid disappointment.
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>