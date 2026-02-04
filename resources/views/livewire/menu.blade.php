<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\Volt\Component;
use App\Models\Item;
use App\Models\TagType;
use App\Models\CartItem;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Menu')]
class extends Component {
    
    public bool $showFilters = false;
    public bool $loadingMore = false;
    public int $perLoad = 12;
    public int $loadedCount = 0;
    
    public int $cartCount = 0;

    #[Url(history: true)]
    public $search = [
        'name' => '',
    ];

    #[Url(history: true)]
    public $filters = [
        'code' => '',
        'tags' => [],
        'tag_types' => [],
        'available_only' => true,
        'min_price' => null,
        'max_price' => null,
    ];
    
    #[Url(history: true)]
    public $sortBy = 'name';

    #[Url(history: true)]
    public $sortDirection = 'asc';
    
    public function mount()
    {
        $this->search = session()->get('menu.search', $this->search);
        
        // Ensure all filter keys exist
        $savedFilters = session()->get('menu.filters', []);
        $this->filters = array_merge($this->filters, $savedFilters);
        
        $this->sortBy = session()->get('menu.sortBy', $this->sortBy);
        $this->sortDirection = session()->get('menu.sortDirection', $this->sortDirection);
        $this->loadedCount = session()->get('menu.loadedCount', $this->perLoad);
        
        // Get cart count for current user
        if (auth()->check()) {
            $this->cartCount = CartItem::where('user_id', auth()->id())->sum('quantity');
        }
        
        $this->dispatch('init-scroll-position', 
            scrollPosition: session()->get('menu.scrollPosition', 0)
        );
    }
    
    public function updatedFilters()
    {
        session()->put('menu.filters', $this->filters);
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('menu.loadedCount', $this->loadedCount);
    }

    public function updatedSearch()
    {
        session()->put('menu.search', $this->search);
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('menu.loadedCount', $this->loadedCount);
    }
    
    public function resetFilters()
    {
        $this->filters = [
            'code' => '',
            'tags' => [],
            'tag_types' => [],
            'available_only' => true,
            'min_price' => null,
            'max_price' => null,
        ];
        session()->forget('menu.filters');
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('menu.loadedCount', $this->loadedCount);
    }
    
    public function loadMore()
    {
        $this->loadingMore = true;
        $this->loadedCount += $this->perLoad;
        session()->put('menu.loadedCount', $this->loadedCount);
        $this->loadingMore = false;
        
        $this->dispatch('save-scroll-position');
    }
    
    public function toggleTag($tagId)
    {
        if (in_array($tagId, $this->filters['tags'])) {
            $this->filters['tags'] = array_diff($this->filters['tags'], [$tagId]);
        } else {
            $this->filters['tags'][] = $tagId;
        }
        
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('menu.filters', $this->filters);
        session()->put('menu.loadedCount', $this->loadedCount);
    }
    
    public function toggleTagType($typeId)
    {
        if (in_array($typeId, $this->filters['tag_types'])) {
            $this->filters['tag_types'] = array_diff($this->filters['tag_types'], [$typeId]);
        } else {
            $this->filters['tag_types'][] = $typeId;
        }
        
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('menu.filters', $this->filters);
        session()->put('menu.loadedCount', $this->loadedCount);
    }
    
    public function addToCart($itemId)
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
        
        $item = Item::find($itemId);
        
        if (!$item || $item->status !== 'available' || $item->stock <= 0) {
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
            ->where('item_id', $itemId)
            ->first();
        
        if ($cartItem) {
            // Update quantity if item exists
            if ($cartItem->quantity < $item->stock) {
                $cartItem->increment('quantity');
                $cartItem->update([
                    'cost' => $cartItem->quantity * $item->price
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
                'item_id' => $itemId,
                'quantity' => 1,
                'cost' => $item->price
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
    
    #[Computed]
    public function getItems()
    {
        return Item::query()
            ->with(['tags'])
            ->when($this->search['name'] ?? '', function ($query) {
                $query->where('name', 'like', '%' . $this->search['name'] . '%');
            })
            ->when($this->filters['code'] ?? '', function ($query) {
                $query->where('code', 'like', '%' . $this->filters['code'] . '%');
            })
            ->when($this->filters['tags'] ?? [], function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->whereIn('tags.id', $this->filters['tags']);
                }, '>=', count($this->filters['tags']));
            })
            ->when($this->filters['tag_types'] ?? [], function ($query) {
                $query->whereHas('tags', function ($q) {
                    $q->whereHas('type', function ($typeQuery) {
                        $typeQuery->whereIn('types.id', $this->filters['tag_types']);
                    });
                });
            })
            ->when($this->filters['available_only'] ?? true, function ($query) {
                $query->where('status', 'available')
                    ->where('stock', '>', 0);
            })
            ->when($this->filters['min_price'] ?? null, function ($query) {
                $query->where('price', '>=', $this->filters['min_price']);
            })
            ->when($this->filters['max_price'] ?? null, function ($query) {
                $query->where('price', '<=', $this->filters['max_price']);
            })
            ->when($this->sortBy, function ($query) {
                if ($this->sortBy === 'price') {
                    $query->orderBy('price', $this->sortDirection);
                } elseif ($this->sortBy === 'name') {
                    $query->orderBy('name', $this->sortDirection);
                } elseif ($this->sortBy === 'stock') {
                    $query->orderBy('stock', $this->sortDirection);
                }
            })
            ->get()
            ->map(function ($item) {
                $item->is_available = $item->status === 'available' && $item->stock > 0;
                return $item;
            });
    }
    
    #[Computed]
    public function getTagTypesWithTags()
    {
        return TagType::with('tags')->get();
    }
    
    public function sort($column, $direction = null) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $direction ?? 'asc';
        }
        
        session()->put('menu.sortBy', $this->sortBy);
        session()->put('menu.sortDirection', $this->sortDirection);
        
        $this->reset('loadedCount');
        $this->loadedCount = $this->perLoad;
        session()->put('menu.loadedCount', $this->loadedCount);
        
        $this->dispatch('save-scroll-position');
    }
}; ?>

<div
x-data="{
    scrollPosition: 0,
    cartCount: {{ $cartCount }},
    
    init() {
        this.cartCount = {{ $cartCount }};
        
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.scrollTo(0, this.scrollPosition);
            }, 100);
        });
        
        this.$wire.on('init-scroll-position', ({ scrollPosition }) => {
            this.scrollPosition = scrollPosition;
            window.scrollTo(0, scrollPosition);
        });
        
        window.addEventListener('beforeunload', () => {
            this.saveScrollPosition();
        });
    },
    
    saveScrollPosition() {
        this.scrollPosition = window.scrollY;
    }
}"
@scroll.debounce.250ms="saveScrollPosition"
@save-scroll-position.window="saveScrollPosition">
    <div class="overflow-hidden">
        <div class="p-4 outline outline-offset-[-1px] rounded-lg shadow">
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <flux:label>Item Name</flux:label>
                    <flux:input wire:model.live="search.name" placeholder="Search by name..." />
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex items-center justify-between my-6">
        <div><flux:heading size="xl">Menu Items</flux:heading></div>
        <div class="flex items-center gap-4">
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
            <div>
                <flux:modal.trigger name="sort">
                    <flux:button type="button">
                        <span><flux:icon.arrows-up-down/></span> Sort
                    </flux:button>
                </flux:modal.trigger>
            </div>
            <div>
                <flux:modal.trigger name="filters">
                    <flux:button type="button">
                        <span><flux:icon.funnel/></span> Filters
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>
    
    @if($this->getItems()->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach ($this->getItems()->take($this->loadedCount) as $item)
        <div class="rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow relative bg-gradient-to-r from-amber-900 to-amber-800">
            @if(!$item->is_available)
                <div class="absolute inset-0 bg-gray-400/30 z-10"></div>
            @endif
            
            <div class="relative">
                @if($item->thumbnail_pic)
                    <div class="aspect-square w-full">
                        <img src="{{ asset('storage/' . $item->thumbnail_pic) }}" 
                            class="w-full h-full object-cover" 
                            alt="{{ $item->name }}">
                    </div>
                @else
                    <div class="aspect-square w-full outline flex items-center justify-center">
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
                        {{-- <flux:text class="text-xs" variant="subtle">{{ $item->code }}</flux:text> --}}
                    </div>
                    <div class="text-lg font-bold text-primary-600">
                        {{ format_rupiah($item->price) }}
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-1 mb-4">
                    @foreach($item->tags->take(3) as $tag)
                        <flux:badge color="amber" variant="solid" rounded>
                            {{ $tag->name }}
                        </flux:badge>
                    @endforeach
                    @if($item->tags->count() > 3)
                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            +{{ $item->tags->count() - 3 }}
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        @if($item->is_available)
                            <div class="flex items-center gap-2 text-success-600">
                                <flux:icon.check-circle class="w-4 h-4" />
                                <span class="text-sm">
                                    {{ $item->stock }} in stock
                                </span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-danger-600">
                                <flux:icon.x-circle class="w-4 h-4" />
                                <span class="text-sm">
                                    @if($item->status !== 'available')
                                        {{ ucfirst($item->status) }}
                                    @else
                                        Out of stock
                                    @endif
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
                        
                        <flux:button 
                            icon="plus"
                            variant="primary"
                            size="sm"
                            wire:click="addToCart({{ $item->id }})"
                            :disabled="!$item->is_available"
                            :title="!$item->is_available ? 'This item is not available' : 'Add to cart'"
                        >
                            Add
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        
        @if($this->loadedCount < $this->getItems()->count())
        <div class="col-span-full flex justify-center mt-6" wire:loading.remove>
            <flux:button wire:click="loadMore" :loading="$loadingMore">
                Load More
            </flux:button>
        </div>
        @endif
        
        <div wire:loading>
            <div class="col-span-full flex justify-center py-8">
                <flux:icon.loading />
            </div>
        </div>
    </div>
    @else
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <flux:icon.magnifying-glass class="w-12 h-12 mx-auto" />
        </div>
        <p class="text-neutral-600 dark:text-neutral-400 text-lg">No menu items found matching your criteria.</p>
        <div class="mt-4">
            <flux:button wire:click="resetFilters">Reset Filters</flux:button>
        </div>
    </div>
    @endif
    
    <flux:modal name="sort" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Sort By</flux:heading>
            </div>
            
            <div class="space-y-4">
                <div>
                    <flux:button 
                        type="button" 
                        variant="{{ $sortBy === 'name' ? 'primary' : 'ghost' }}" 
                        wire:click="sort('name')"
                        class="w-full"
                    >
                        <div class="inline-flex items-center">Name
                        @if($sortBy === 'name')
                            <flux:icon.arrow-down class="ml-2 w-4 h-4 {{ $sortDirection === 'desc' ? 'transform rotate-180' : '' }}" />
                        @endif
                        </div>
                    </flux:button>
                </div>
                
                <div>
                    <flux:button 
                        type="button" 
                        variant="{{ $sortBy === 'price' ? 'primary' : 'ghost' }}" 
                        wire:click="sort('price')"
                        class="w-full inline-flex items-center"
                    >
                        <div class="inline-flex items-center">Price
                        @if($sortBy === 'price')
                            <flux:icon.arrow-down class="ml-2 w-4 h-4 {{ $sortDirection === 'desc' ? 'transform rotate-180' : '' }}" />
                        @endif
                        </div>
                    </flux:button>
                </div>
                
                <div>
                    <flux:button 
                        type="button" 
                        variant="{{ $sortBy === 'stock' ? 'primary' : 'ghost' }}" 
                        wire:click="sort('stock')"
                        class="w-full inline-flex items-center"
                    >
                        <div class="inline-flex items-center">Stock
                        @if($sortBy === 'stock')
                            <flux:icon.arrow-down class="ml-2 w-4 h-4 {{ $sortDirection === 'desc' ? 'transform rotate-180' : '' }}" />
                        @endif
                        </div>
                    </flux:button>
                </div>
            </div>
            
            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
    
    <flux:modal name="filters" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Filters</flux:heading>
            </div>
            
            {{-- <div>
                <flux:label>Item Code</flux:label>
                <flux:input wire:model.live="filters.code" placeholder="Search by code..." />
            </div> --}}
            
            <div>
                <flux:label>Price Range</flux:label>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model.live="filters.min_price" type="number" min="0" step="0.01" placeholder="Min price" />
                    <flux:input wire:model.live="filters.max_price" type="number" min="0" step="0.01" placeholder="Max price" />
                </div>
            </div>
            
            <div>
                <flux:label>Show Only Available Items</flux:label>
                <flux:checkbox wire:model.live="filters.available_only" />
            </div>
            
            {{-- Tag Types Filter Section --}}
            <div>
                <flux:label>Filter by Category/Types</flux:label>
                <div class="space-y-2 mb-4 flex flex-wrap gap-x-4 gap-y-2">
                    @foreach($this->getTagTypesWithTags as $type)
                        <div class="flex items-center">
                            <flux:checkbox 
                                wire:model.live="filters.tag_types"
                                value="{{ $type->id }}"
                                id="type_{{ $type->id }}"
                            />
                            <label for="type_{{ $type->id }}" class="ml-2 cursor-pointer whitespace-nowrap">
                                {{ $type->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Individual Tags Filter Section --}}
            <div>
                <flux:label>Filter by Specific Tags</flux:label>
                @foreach($this->getTagTypesWithTags as $type)
                    @if($type->tags->count())
                        <div class="mb-4">
                            <flux:text class="text-sm font-medium mb-2">{{ $type->name }}</flux:text>
                            <div class="flex flex-wrap gap-2">
                                @foreach($type->tags as $tag)
                                    <flux:badge 
                                        :color="in_array($tag->id, $filters['tags'] ?? []) ? 'lime' : 'zinc'"
                                        wire:click="toggleTag({{ $tag->id }})"
                                        class="cursor-pointer"
                                    >
                                        {{ $tag->name }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            
            <div class="flex">
                <flux:button type="button" wire:click="resetFilters">
                    Reset Filters
                </flux:button>
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('save-scroll-position', () => {
                const scrollPosition = window.scrollY;
                Livewire.dispatch('saveScrollToSession', { scrollPosition });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('scroll', () => {
                const scrollPosition = window.scrollY;
                Livewire.dispatch('saveScrollToSession', { scrollPosition });
            }, { passive: true });
        });
    </script>
</div>