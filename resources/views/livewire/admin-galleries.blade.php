<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Gallery;
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('Galleries')]
class extends Component {
    use WithPagination, WithFileUploads;
    
    public bool $showFilters = false;
    
    public $code = '';
    public $index = 0;
    public $name = '';
    public $status = 'unknown';
    public $description = '';
    public $picture;
    public $editingGalleryId = null;
    public ?Gallery $galleryToDelete = null;
    
    public $filters = [
        'name' => '',
        'code' => '',
        'status' => ''
    ];
    
    public $sortBy = 'index';
    public $sortDirection = 'desc';
    public $perPage = 10;
    
    public function mount()
    {
        $this->filters = session()->get('galleries.filters', $this->filters);
        $this->sortBy = session()->get('galleries.sortBy', $this->sortBy);
        $this->sortDirection = session()->get('galleries.sortDirection', $this->sortDirection);
        $this->perPage = session()->get('galleries.perPage', $this->perPage);
        
        $savedPage = session()->get('galleries.page', 1);
        $this->setPage($savedPage);
        
        $this->validatePage();
    }

    public function updatedPage($value)
    {
        session()->put('galleries.page', $value);
    }

    public function gotoPage($page)
    {
        $this->setPage($page);
        session()->put('galleries.page', $page);
        $this->validatePage();
    }
    
    public function updatedPerPage($value)
    {
        session()->put('galleries.perPage', $value);
        $this->resetPage();
        $this->validatePage();
    }
    
    public function updatedFilters($value, $key)
    {
        session()->put('galleries.filters', $this->filters);
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset('filters');
        session()->forget('galleries.filters');
        $this->resetPage();
        session()->put('galleries.page', 1);
    }
    
    public function validatePage()
    {
        $galleries = $this->getGalleries();
        
        if ($galleries->currentPage() > $galleries->lastPage()) {
            $this->setPage($galleries->lastPage());
        }
    }
    
    #[Computed]
    public function getGalleries()
    {
        return Gallery::query()
            ->when($this->filters['name'], function ($query) {
                $query->where('name', 'like', '%' . $this->filters['name'] . '%');
            })
            ->when($this->filters['code'], function ($query) {
                $query->where('code', 'like', '%' . $this->filters['code'] . '%');
            })
            ->when($this->filters['status'] && $this->filters['status'] !== 'all', function ($query) {
                $query->where('status', $this->filters['status']);
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);
    }

    public function saveGallery()
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:galleries,code,' . $this->editingGalleryId,
            'name' => 'required|string|max:255',
            'index' => 'nullable|integer',
            'status' => 'required|in:unknown,show,hide',
            'description' => 'nullable|string',
            'picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'], // 5MB max
        ]);
        
        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'description' => $this->description,
        ];
        
        // Only include index if it's not null or empty
        if ($this->index !== '' && $this->index !== null) {
            $data['index'] = (int) $this->index;
        }
        
        if ($this->editingGalleryId) {
            $gallery = Gallery::find($this->editingGalleryId);
            
            // Handle picture upload for edit
            if ($this->picture) {
                // Delete old picture if exists
                if ($gallery->picture) {
                    Storage::disk('public')->delete($gallery->picture);
                }
                
                // Store new picture
                $path = $this->picture->store('galleries', 'public');
                $data['picture'] = $path;
            }
            
            $gallery->update($data);
            
            Flux::toast(
                variant: 'success',
                heading: 'Gallery Updated',
                text: 'Gallery has been successfully updated.',
            );
        } else {
            // Handle picture upload for create
            if ($this->picture) {
                $path = $this->picture->store('galleries', 'public');
                $data['picture'] = $path;
            }
            
            Gallery::create($data);
            
            Flux::toast(
                variant: 'success',
                heading: 'Gallery Created',
                text: 'New gallery has been successfully created.',
            );
        }
        
        $this->resetGalleryForm();
    }
    
    public function editGallery($galleryId)
    {
        $gallery = Gallery::find($galleryId);
        
        if ($gallery) {
            $this->editingGalleryId = $gallery->id;
            $this->code = $gallery->code;
            $this->index = $gallery->index ?? 0;
            $this->name = $gallery->name;
            $this->status = $gallery->status;
            $this->description = $gallery->description ?? '';
            $this->picture = null; // Reset picture upload field
        }
    }
    
    public function removePicture()
    {
        if ($this->editingGalleryId) {
            $gallery = Gallery::find($this->editingGalleryId);
            
            if ($gallery && $gallery->picture) {
                Storage::disk('public')->delete($gallery->picture);
                $gallery->update(['picture' => null]);
                
                Flux::toast(
                    variant: 'success',
                    heading: 'Picture Removed',
                    text: 'Gallery picture has been removed.',
                );
            }
        }
    }
    
    public function confirmGalleryDelete($galleryId)
    {
        $this->galleryToDelete = Gallery::find($galleryId);
        Flux::modal('delete-gallery-modal')->show();
    }
    
    public function deleteGallery()
    {
        if (!$this->galleryToDelete) {
            $this->dispatch('toast', message: 'Gallery not found', type: 'error');
            return;
        }
        
        try {
            // Delete picture file if exists
            if ($this->galleryToDelete->picture) {
                Storage::disk('public')->delete($this->galleryToDelete->picture);
            }
            
            $this->galleryToDelete->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'Gallery Deleted.',
                text: 'Gallery successfully deleted.',
            );
            
            $this->galleryToDelete = null;
            Flux::modal('delete-gallery-modal')->close();
            
            $this->resetPage();
            unset($this->getGalleries);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete gallery: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function resetGalleryForm()
    {
        $this->reset(['code', 'index', 'name', 'status', 'description', 'picture', 'editingGalleryId']);
        $this->picture = null;
    }
    
    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        session()->put('galleries.sortBy', $this->sortBy);
        session()->put('galleries.sortDirection', $this->sortDirection);
        
        $this->validatePage();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">Galleries Management</flux:heading></div>
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
                    <flux:label>Gallery Name</flux:label>
                    <flux:input wire:model.live="filters.name" placeholder="Search by name..." />
                </div>
                
                <div>
                    <flux:label>Gallery Code</flux:label>
                    <flux:input wire:model.live="filters.code" placeholder="Search by code..." />
                </div>
                
                <div>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="filters.status">
                        <option value="all">All Statuses</option>
                        <option value="unknown">Unknown</option>
                        <option value="show">Show</option>
                        <option value="hide">Hide</option>
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
    <br>

    <div class="p-4 mb-4 outline outline-offset-[-1px] rounded-lg shadow">
        <flux:heading size="lg">{{ $editingGalleryId ? 'Edit Gallery' : 'Create New Gallery' }}</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div class="md:col-span-2">
                <div class="space-y-4">
                    @if($editingGalleryId)
                        @php
                            $gallery = \App\Models\Gallery::find($editingGalleryId);
                        @endphp
                        @if($gallery && $gallery->picture)
                            <div class="flex items-start gap-4">
                                <img src="{{ Storage::url($gallery->picture) }}" alt="Current Picture" class="h-20 w-20 rounded-lg object-cover">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Current Picture
                                    </label>
                                    <flux:button variant="danger" wire:click="removePicture" type="button">
                                        Remove Picture
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-4">
                                @if($picture)
                                    <img src="{{ $picture->temporaryUrl() }}" alt="Preview" class="h-20 w-20 rounded-lg object-cover">
                                @endif
                                <flux:input 
                                    type="file" 
                                    wire:model="picture" 
                                    label="Gallery Picture" 
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="truncate"
                                />
                            </div>
                        @endif
                    @else
                        <div class="flex items-center gap-4">
                            @if($picture)
                                <img src="{{ $picture->temporaryUrl() }}" alt="Preview" class="h-20 w-20 rounded-lg object-cover">
                            @endif
                            <flux:input 
                                type="file" 
                                wire:model="picture" 
                                label="Gallery Picture" 
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                class="truncate"
                            />
                        </div>
                    @endif
                </div>
            </div>
            
            <div>
                <flux:label>Gallery Code</flux:label>
                <flux:input wire:model="code" placeholder="Enter unique gallery code" />
            </div>
            
            <div>
                <flux:label>Gallery Name</flux:label>
                <flux:input wire:model="name" placeholder="Enter gallery name" />
            </div>
            
            <div>
                <flux:label>Index (Priority)</flux:label>
                <flux:input type="number" wire:model="index" placeholder="Higher number = higher priority" />
            </div>
            
            <div>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    <option value="unknown">Unknown</option>
                    <option value="show">Show</option>
                    <option value="hide">Hide</option>
                </flux:select>
            </div>
            
            <div class="md:col-span-2">
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" placeholder="Enter gallery description (optional)" rows="3" />
            </div>
            
            <div class="md:col-span-2 flex items-end gap-2">
                <flux:button type="button" wire:click="saveGallery" variant="primary">
                    {{ $editingGalleryId ? 'Update Gallery' : 'Create Gallery' }}
                </flux:button>
                @if($editingGalleryId)
                    <flux:button type="button" wire:click="resetGalleryForm" variant="ghost">
                        Cancel
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
    
    <br>
    @if($this->getGalleries()->count())
    <flux:table :paginate="$this->getGalleries()">
        <flux:table.columns>
            <flux:table.column class="text-center">No.</flux:table.column>
            <flux:table.column class="text-center">Actions</flux:table.column>
            <flux:table.column class="text-center" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'code'" :direction="$sortDirection" wire:click="sort('code')">Code</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column class="text-center" sortable :sorted="$sortBy === 'index'" :direction="$sortDirection" wire:click="sort('index')">Index</flux:table.column>
            <flux:table.column class="text-center">Status</flux:table.column>
            <flux:table.column>Picture</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->getGalleries() as $gallery)
            <flux:table.row>
                <flux:table.cell class="text-center">{{ ($this->getGalleries()->currentPage() - 1) * $this->getGalleries()->perPage() + $loop->iteration }}.</flux:table.cell>
                <flux:table.cell class="text-center">
                <div class="flex items-center justify-center gap-2">
                    <flux:button 
                        icon="trash" 
                        variant="danger"
                        wire:click="confirmGalleryDelete({{ $gallery->id }})"
                    ></flux:button>
                    <flux:button 
                        icon="pencil" 
                        variant="primary" 
                        wire:click="editGallery({{ $gallery->id }})"
                    ></flux:button>
                </div>
                </flux:table.cell>
                <flux:table.cell class="text-center">({{$gallery->id}})</flux:table.cell>
                <flux:table.cell>{{ $gallery->code }}</flux:table.cell>
                <flux:table.cell>{{$gallery->name}}</flux:table.cell>
                <flux:table.cell class="text-center">{{ $gallery->index ?? 0 }}</flux:table.cell>
                <flux:table.cell class="text-center">
                    <flux:badge variant="solid" :color="match($gallery->status) {
                        'unknown' => 'zinc',
                        'show' => 'lime',
                        'hide' => 'red',
                        default => 'zinc'
                    }">
                        {{ ucfirst($gallery->status) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    @if($gallery->picture)
                        <img src="{{ Storage::url($gallery->picture) }}" alt="{{ $gallery->name }}" class="h-10 w-10 rounded object-cover">
                    @else
                        <span class="text-gray-400 text-sm">No picture</span>
                    @endif
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    
    <flux:modal name="delete-gallery-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete gallery?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->galleryToDelete)
                        <p>You're about to delete <strong>{{ $this->galleryToDelete->name }}</strong> (Code: {{ $this->galleryToDelete->code }}).</p>
                        <p>This will also delete the associated picture file.</p>
                        <p>This action cannot be undone.</p>
                    @endif
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="danger" 
                    wire:click="deleteGallery"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete Gallery</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="text-center py-8">
        <p class="text-neutral-600 dark:text-neutral-400">No galleries found. You've been redirected to the last available page.</p>
    </div>
    @endif
</div>