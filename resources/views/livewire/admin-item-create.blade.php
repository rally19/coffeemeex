<?php
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Flux\Flux;

new class extends Component {
    use WithFileUploads;
    
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public $thumbnail_pic;
    public int $stock = 0;
    public string $price = '';
    
    public function createItem(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50', 'unique:items,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail_pic' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        if ($this->thumbnail_pic) {
            $validated['thumbnail_pic'] = $this->thumbnail_pic->store('item_thumbnails', 'public');
        }

        Flux::toast(
            variant: 'success',
            heading: 'Item Created.',
            text: 'Item successfully created.',
        );

        $item = Item::create($validated);

        $this->redirect(route('admin.edit.item', $item->id), navigate: true);
        // $this->reset();
    }
}; ?>

<div>
    <flux:modal.trigger name="create-item">
        <flux:button variant="primary">Add New Item</flux:button>
    </flux:modal.trigger>

    <flux:modal name="create-item" variant="flyout" class="max-w-lg">
        <div class="space-y-6">
            <form wire:submit.prevent="createItem" class="flex flex-col gap-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input 
                            wire:model="code" 
                            :label="__('Item Code')" 
                            type="text" 
                            required 
                            :placeholder="__('Unique item code')" 
                        />
                        
                        <flux:input 
                            wire:model="name" 
                            :label="__('Item Name')" 
                            type="text" 
                            required 
                            :placeholder="__('Item name')" 
                        />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input 
                            wire:model="stock" 
                            :label="__('Stock')" 
                            type="number" 
                            required 
                            min="0"
                            :placeholder="__('Available stock')" 
                        />
                        
                        <flux:input 
                            wire:model="price" 
                            :label="__('Price')" 
                            type="number" 
                            required 
                            min="0"
                            step="0.01"
                            :placeholder="__('Item price')" 
                        />
                    </div>
                    
                    <flux:textarea 
                        wire:model="description" 
                        :label="__('Description')" 
                        :placeholder="__('Item description')" 
                        rows="3" 
                    />
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            @if($thumbnail_pic)
                                <img src="{{ $thumbnail_pic->temporaryUrl() }}" alt="Thumbnail Preview" class="h-20 w-20 rounded-md object-cover">
                            @endif
                            <flux:input 
                                type="file" 
                                wire:model="thumbnail_pic" 
                                :label="__('Thumbnail Image')" 
                                accept="image/jpeg,image/png"
                                class="truncate"
                            />
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end">
                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ __('Add Item') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>