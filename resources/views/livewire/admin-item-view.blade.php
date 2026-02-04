<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use App\Models\{Item, Tag, TagType};
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('View Item')]
    
class extends Component {
    use WithFileUploads;
    
    public string $code = '';
    public string $name = '';
    public string $status = 'unknown';
    public string $description = '';
    public $thumbnail_pic;
    public string $stock = '';
    public string $price = '';
    public $item;
    
    public $tagTypeFilter = '';
    public $availableTags = [];
    public $tagSearchInput = '';

    public function mount(): void
    {
        $this->item = Item::with('tags.type')->find(request()->route('id'));
        
        $this->code = $this->item->code;
        $this->name = $this->item->name;
        $this->status = $this->item->status;
        $this->description = $this->item->description;
        $this->stock = $this->item->stock;
        $this->price = $this->item->price;
        
        $this->updateAvailableTags();
    }

    public function updateItem(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:unknown,available,unavailable,closed'],
            'description' => ['nullable', 'string'],
            'stock' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'thumbnail_pic' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $this->item->fill(collect($validated)->except(['thumbnail_pic'])->toArray());

        if ($this->thumbnail_pic) {
            if ($this->item->thumbnail_pic) {
                Storage::disk('public')->delete($this->item->thumbnail_pic);
            }
            $path = $this->thumbnail_pic->store('item_thumbnails', 'public');
            $this->item->thumbnail_pic = $path;
        }

        $this->item->save();

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Item information updated.',
            duration: 4000,
        );
    }

    public function removeThumbnail(): void
    {
        if ($this->item->thumbnail_pic) {
            Storage::disk('public')->delete($this->item->thumbnail_pic);
            $this->item->thumbnail_pic = null;
            $this->item->save();
        }
        
        $this->thumbnail_pic = null;
    }
    
    public function updatedTagTypeFilter()
    {
        $this->updateAvailableTags();
    }
    
    public function updatedTagSearchInput()
    {
        $this->updateAvailableTags();
    }
    
    public function updateAvailableTags()
    {
        $this->availableTags = Tag::query()
            ->when($this->tagTypeFilter, function ($query) {
                $query->where('type_id', $this->tagTypeFilter);
            })
            ->when($this->tagSearchInput, function ($query) {
                $query->where('name', 'like', '%' . $this->tagSearchInput . '%');
            })
            ->whereNotIn('id', $this->item->tags->pluck('id'))
            ->orderBy('name')
            ->get();
    }
    
    public function addTag($tagId)
    {
        $this->item->tags()->attach($tagId);
        $this->item->load('tags');
        $this->updateAvailableTags();
        
        Flux::toast(
            variant: 'success',
            heading: 'Tag added',
            text: 'The tag has been added to this item',
            duration: 3000,
        );
    }
    
    public function removeTag($tagId)
    {
        $this->item->tags()->detach($tagId);
        $this->item->load('tags');
        $this->updateAvailableTags();
        
        Flux::toast(
            variant: 'success',
            heading: 'Tag removed',
            text: 'The tag has been removed from this item',
            duration: 3000,
        );
    }
    
    #[Computed] 
    public function tagTypes()
    {
        return TagType::orderBy('name')->get();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4 sm:hidden">
        <div>
            <flux:button icon="eye" :href="route('admin.view.item', ['id' => $item->id])" wire:navigate></flux:button>
        </div>
        <div class="flex items-center gap-4">
            <div>
                <flux:button :href="route('admin.items')" wire:navigate>Back</flux:button>
            </div>
        </div>
    </div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">View Item ({{ $item->id }}) <span class="font-extrabold">{{ $item->name }}</span></flux:heading></div>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block">
                <flux:button icon="pencil" :href="route('admin.edit.item', ['id' => $item->id])" wire:navigate></flux:button>
            </div>
            <div class="hidden sm:block">
                <flux:button :href="route('admin.items')" wire:navigate>Back</flux:button>
            </div>
        </div>
    </div>
    
    <flux:tab.group>
        <flux:tabs>
            <flux:tab name="edit">
                <div class="flex items-center gap-2">
                    <flux:icon.pencil-square class="w-4 h-4" />
                    Item Details
                </div>
            </flux:tab>
            <flux:tab name="tags">
                <div class="flex items-center gap-2">
                    <flux:icon.tag class="w-4 h-4" />
                    Manage Tags
                </div>
            </flux:tab>
        </flux:tabs>

        <flux:tab.panel name="edit">
            <div class="space-y-6">
                <form wire:submit="updateItem" class="flex flex-col gap-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input 
                                wire:model="code" 
                                :label="__('Item Code')" 
                                type="text" 
                                required 
                                :placeholder="__('Unique item code')" 
                                disabled
                            />
                            
                            <flux:input 
                                wire:model="name" 
                                :label="__('Item Name')" 
                                type="text" 
                                required 
                                :placeholder="__('Item name')" 
                                disabled
                            />
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:select 
                                wire:model="status" 
                                :label="__('Status')" 
                                required
                                disabled
                            >
                                <option value="unknown">{{ __('Unknown') }}</option>
                                <option value="available">{{ __('Available') }}</option>
                                <option value="unavailable">{{ __('Unavailable') }}</option>
                                <option value="closed">{{ __('Closed') }}</option>
                            </flux:select>
                            
                            <flux:input 
                                wire:model="stock" 
                                :label="__('Stock Quantity')" 
                                type="number" 
                                required 
                                min="0" 
                                :placeholder="__('Available stock')" 
                                disabled
                            />
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input 
                                wire:model="price" 
                                :label="__('Price')" 
                                type="number" 
                                required 
                                min="0" 
                                step="0.01"
                                :placeholder="__('Item price')" 
                                disabled
                            />
                        </div>
                        
                        <flux:textarea 
                            wire:model="description" 
                            :label="__('Description')" 
                            :placeholder="__('Item description')" 
                            rows="3" 
                            disabled
                        />
                        
                        <div class="space-y-4">
                            <div class="space-y-4">
                                @if($item->thumbnail_pic)
                                    <div class="flex items-start gap-4">
                                        <img src="{{ Storage::url($item->thumbnail_pic) }}" alt="Thumbnail" class="h-20 w-20 rounded-md object-cover">
                                        <div class="space-y-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Current Thumbnail') }}
                                            </label>
                                            <flux:button variant="danger" wire:click="removeThumbnail" type="button" disabled>
                                                {{ __('Remove Thumbnail') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-4">
                                        @if($thumbnail_pic)
                                            <img src="{{ $thumbnail_pic->temporaryUrl() }}" alt="Preview" class="h-20 w-20 rounded-md object-cover">
                                        @endif
                                        <flux:input 
                                            type="file" 
                                            wire:model="thumbnail_pic" 
                                            :label="__('Thumbnail Image')" 
                                            accept="image/jpeg,image/png"
                                            disabled
                                        />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full" disabled>{{ __('Save') }}</flux:button>
                    </div>
                </form>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="tags">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4 outline rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">Assigned Tags</flux:heading>
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ $item->tags->count() }} tags</span>
                    </div>
                    
                    @if($item->tags->count())
                        <div class="flex flex-wrap gap-2">
                            @foreach($item->tags as $tag)
                                <flux:badge 
                                    {{-- class="cursor-pointer hover:bg-danger-100 transition-colors"
                                    wire:click="removeTag({{ $tag->id }})" --}}
                                    title="Click to remove"
                                >
                                    {{ $tag->type ? $tag->type->name . ': ' : '' }}{{ $tag->name }}
                                    <span class="ml-1">&times;</span>
                                </flux:badge>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-neutral-600 dark:text-neutral-400">
                            <flux:icon.tag class="w-8 h-8 mx-auto mb-2" />
                            <p>No tags assigned yet</p>
                        </div>
                    @endif
                </div>
                
                <div class="space-y-4 outline rounded-lg p-4">
                    <flux:heading size="lg">Available Tags</flux:heading>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <flux:label>Filter by Type</flux:label>
                            <flux:select wire:model.live="tagTypeFilter" class="w-full" disabled>
                                <option value="">All Tag Types</option>
                                @foreach($this->tagTypes as $tagType)
                                    <option value="{{ $tagType->id }}">{{ $tagType->name }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                        
                        <div>
                            <flux:label>Search Tags</flux:label>
                            <flux:input 
                                type="text" 
                                wire:model.live.debounce.300ms="tagSearchInput" 
                                placeholder="Type to search tags..."
                                class="w-full"
                                disabled
                            />
                        </div>
                    </div>

                    <div class="mt-4">
                        @if($availableTags->count())
                            <div class="flex flex-wrap gap-2 max-h-96 overflow-y-auto p-2">
                                @foreach($availableTags as $tag)
                                    <flux:badge 
                                        {{-- class="cursor-pointer hover:bg-primary-100 transition-colors"
                                        wire:click="addTag({{ $tag->id }})" --}}
                                        title="Click to add"
                                    >
                                        {{ $tag->type ? $tag->type->name . ': ' : '' }}{{ $tag->name }}
                                        <span class="ml-1">+</span>
                                    </flux:badge>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-neutral-600 dark:text-neutral-400">
                                <flux:icon.magnifying-glass class="w-8 h-8 mx-auto mb-2" />
                                <p>No tags found matching your search</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>