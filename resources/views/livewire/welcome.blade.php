<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\Volt\Component;
use App\Models\{Gallery, Item, OrderItem};
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')]
    #[Title('Coffeemeex')]
class extends Component {
    
    #[Computed]
    public function featuredImage()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->whereNotNull('picture')
            ->inRandomOrder()
            ->first();
    }
    
    #[Computed]
    public function galleries()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->orderBy('index', 'desc')
            ->orderBy('id', 'desc')
            ->take(3)
            ->get();
    }
    
    #[Computed]
    public function featuredItems()
    {
        return Item::query()
            ->where('status', 'available')
            ->where('stock', '>', 0)
            ->whereHas('orderItems', function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('order_status', 'completed')
                      ->where('payment_status', 'paid');
                });
            })
            ->withCount(['orderItems as total_sold' => function ($query) {
                $query->select(DB::raw('SUM(quantity)'))
                    ->whereHas('order', function ($q) {
                        $q->where('order_status', 'completed')
                          ->where('payment_status', 'paid');
                    });
            }])
            ->orderByDesc('total_sold')
            ->take(4)
            ->get();
    }
}; ?>

<div>
    <!-- Hero Section -->
    <section class="relative overflow-hidden rounded-2xl mb-8 md:mb-12">
        @if($this->featuredImage)
            <div class="h-64 md:h-96 lg:h-[500px] w-full">
                <img 
                    src="{{ Storage::url($this->featuredImage->picture) }}" 
                    alt="{{ $this->featuredImage->name }}"
                    class="w-full h-full object-cover"
                />
            </div>
        @else
            <div class="h-64 md:h-96 lg:h-[500px] w-full bg-gradient-to-r from-amber-900 to-amber-700 flex items-center justify-center">
                <div class="text-center px-4">
                    <flux:icon.beaker class="w-16 h-16 md:w-24 md:h-24 text-amber-200 mx-auto mb-4 md:mb-6" />
                    <flux:heading size="xl" class="text-white">Coffeemeex</flux:heading>
                    <flux:text class="text-amber-100 mt-2">Discover Yogyakarta's Coffee Gem</flux:text>
                </div>
            </div>
        @endif
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent flex items-end">
            <div class="p-4 md:p-8 lg:p-12 w-full">
                <div class="max-w-4xl mx-auto">
                    <flux:heading size="xl" class="text-white mb-3 md:mb-4">Where Coffee Meets Connection</flux:heading>
                    <flux:text class="text-white/90 mb-4 md:mb-8 max-w-2xl text-sm md:text-base">
                        A sanctuary for coffee lovers in the cultural heart of Yogyakarta. 
                        Experience handcrafted brews in a space designed for connection and creativity.
                    </flux:text>
                    <div class="flex flex-wrap gap-2 md:gap-3">
                        <flux:button 
                            :href="route('menu')" 
                            wire:navigate
                            variant="primary"
                            size="sm"
                            class="bg-amber-600 hover:bg-amber-700 text-xs md:text-sm"
                        >
                            <flux:icon.shopping-cart class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Order Online</span>
                            <span class="sm:hidden">Order</span>
                        </flux:button>
                        <flux:button 
                            :href="route('about')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            class="text-white border-white hover:bg-white/10 text-xs md:text-sm"
                        >
                            <flux:icon.information-circle class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Our Story</span>
                            <span class="sm:hidden">Story</span>
                        </flux:button>
                        <flux:button 
                            :href="route('contact')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            class="text-white border-white hover:bg-white/10 text-xs md:text-sm"
                        >
                            <flux:icon.envelope class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Contact Us</span>
                            <span class="sm:hidden">Contact</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="mb-16">
        <div class="text-center mb-12">
            <flux:heading size="xl">Why Coffee Lovers Choose Us</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 max-w-2xl mx-auto">
                A unique blend of craftsmanship, quality, and community spirit
            </flux:text>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.beaker class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Artisanal Brews</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Each cup is crafted with precision using beans from Indonesia's finest regions.
                </flux:text>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.sparkles class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Expert Craftsmanship</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Our baristas are trained in both traditional and modern brewing techniques.
                </flux:text>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.user-group class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Community Hub</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    A welcoming space for work, conversation, and discovering new connections.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Gallery Preview -->
    @if($this->galleries->count())
        <section class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <flux:heading size="xl">Glimpse of Coffeemeex</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        Moments captured in our space
                    </flux:text>
                </div>
                <flux:button 
                    :href="route('gallery')" 
                    wire:navigate
                    variant="ghost"
                >
                    View Gallery
                    <flux:icon.arrow-right class="w-4 h-4 ml-2" />
                </flux:button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($this->galleries as $gallery)
                    <div class="group relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                        @if($gallery->picture)
                            <div class="aspect-[4/3] w-full">
                                <img 
                                    src="{{ Storage::url($gallery->picture) }}" 
                                    alt="{{ $gallery->name }}"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                            </div>
                        @else
                            <div class="aspect-[4/3] w-full outline flex items-center justify-center">
                                <flux:icon.photo class="w-16 h-16 text-gray-400" />
                            </div>
                        @endif
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent 
                                    opacity-0 group-hover:opacity-100 transition-opacity duration-300 
                                    flex flex-col justify-end p-6">
                            <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                <flux:heading size="xl" class="text-white mb-2">
                                    {{ $gallery->name }}
                                </flux:heading>
                                
                                @if($gallery->description)
                                    <flux:text class="text-white/90 line-clamp-2">
                                        {{ $gallery->description }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- CTA Section -->
    <section class="bg-gradient-to-r from-amber-900 to-amber-800 rounded-2xl p-8 md:p-12 text-center mb-16">
        <flux:heading size="xl" class="text-white mb-4">Start Your Coffee Journey</flux:heading>
        <flux:text class="text-amber-100 mb-8 max-w-2xl mx-auto">
            Experience the perfect blend of tradition and innovation in every cup
        </flux:text>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                <flux:icon.map-pin class="w-8 h-8 text-amber-200 mx-auto mb-4" />
                <flux:heading size="lg" class="text-white mb-2">Our Location</flux:heading>
                <flux:text class="text-amber-100 text-base">
                    Jl. Coffee Street No. 123<br>
                    Yogyakarta 55281
                </flux:text>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                <flux:icon.clock class="w-8 h-8 text-amber-200 mx-auto mb-4" />
                <flux:heading size="lg" class="text-white mb-2">Visit Us</flux:heading>
                <flux:text class="text-amber-100 text-base">
                    Open daily<br>
                    7:00 AM - 10:00 PM
                </flux:text>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6">
                <flux:icon.phone class="w-8 h-8 text-amber-200 mx-auto mb-4" />
                <flux:heading size="lg" class="text-white mb-2">Quick Contact</flux:heading>
                <flux:text class="text-amber-100 text-base">
                    (0274) 567-8901<br>
                    For reservations
                </flux:text>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-3 justify-center">
            <flux:button 
                :href="route('menu')" 
                wire:navigate
                variant="primary"
                class="bg-white text-amber-800 hover:bg-amber-50"
            >
                <flux:icon.shopping-cart class="w-5 h-5 mr-2" />
                Browse Menu
            </flux:button>
            @if(auth()->check())
                <flux:button 
                    :href="route('dashboard')" 
                    wire:navigate
                    variant="ghost"
                    class="text-white border-white hover:bg-white/10"
                >
                    <flux:icon.user class="w-5 h-5 mr-2" />
                    My Orders
                </flux:button>
            @else
                <flux:button 
                    :href="route('login')" 
                    wire:navigate
                    variant="ghost"
                    class="text-white border-white hover:bg-white/10"
                >
                    <flux:icon.user-plus class="w-5 h-5 mr-2" />
                    Sign In
                </flux:button>
            @endif
            <flux:button 
                :href="route('contact')" 
                wire:navigate
                variant="ghost"
                class="text-white border-white hover:bg-white/10"
            >
                <flux:icon.envelope class="w-5 h-5 mr-2" />
                Get in Touch
            </flux:button>
            <!-- ADDED: Legal Page Button -->
            <flux:button 
                :href="route('legal')" 
                wire:navigate
                variant="ghost"
                class="text-white border-white hover:bg-white/10"
            >
                <flux:icon.shield-check class="w-5 h-5 mr-2" />
                Legal Info
            </flux:button>
        </div>
    </section>

    <!-- Featured Menus Section -->
    <section class="mb-16">
        <div class="text-center mb-8">
            <flux:heading size="xl" class="mb-4">Customer Favorites</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400">
                Most-loved selections from our regulars
            </flux:text>
        </div>
        
        @if($this->featuredItems->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @foreach($this->featuredItems as $item)
            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div class="relative">
                    @if($item->thumbnail_pic)
                        <div class="aspect-square w-full">
                            <img src="{{ asset('storage/' . $item->thumbnail_pic) }}" 
                                class="w-full h-full object-cover" 
                                alt="{{ $item->name }}">
                        </div>
                    @else
                        <div class="aspect-square w-full outline flex items-center justify-center bg-gray-50">
                            <flux:icon.photo class="w-16 h-16 text-gray-400" />
                        </div>
                    @endif
                    
                    @if($item->stock > 0 && $item->stock <= 5)
                        <div class="absolute top-2 right-2">
                            <span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                Low Stock: {{ $item->stock }}
                            </span>
                        </div>
                    @endif
                </div>
                
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <flux:heading size="lg">{{ $item->name }}</flux:heading>
                        </div>
                        <div class="text-lg font-bold text-primary-600">
                            {{ format_rupiah($item->price) }}
                        </div>
                    </div>
                    
                    @if($item->total_sold > 0)
                        <div class="mb-3">
                            <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">
                                <flux:icon.fire class="w-3 h-3 inline mr-1 text-orange-500" />
                                {{ $item->total_sold }} sold
                            </flux:text>
                        </div>
                    @endif
                    
                    <div class="flex items-center justify-between">
                        <div>
                            @if($item->status === 'available' && $item->stock > 0)
                                <div class="flex items-center gap-2 text-success-600">
                                    <flux:icon.check-circle class="w-4 h-4" />
                                    <span class="text-sm">
                                        {{ $item->stock }} available
                                    </span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-danger-600">
                                    <flux:icon.x-circle class="w-4 h-4" />
                                    <span class="text-sm">
                                        {{ $item->status === 'available' ? 'Out of stock' : ucfirst($item->status) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex gap-2">
                            <flux:button 
                                icon="eye"
                                variant="ghost"
                                size="sm"
                                :href="route('menu.item', ['code' => $item->code])"
                                wire:navigate
                                :title="'View ' . $item->name . ' details'"
                            >
                                Details
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <flux:text class="text-neutral-600 dark:text-neutral-400">
                Discover our menu for delicious options
            </flux:text>
        </div>
        @endif
        
        <div class="text-center">
            <flux:button 
                :href="route('menu')" 
                wire:navigate
                variant="primary"
            >
                <flux:icon.fire class="w-5 h-5 mr-2" />
                Explore Full Menu
            </flux:button>
        </div>
    </section>
</div>