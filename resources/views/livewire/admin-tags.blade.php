<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\WithPagination;
use Livewire\Volt\Component;
use App\Models\Tag;
use App\Models\TagType;
use App\Models\TagCon;
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('Tags Management')]
class extends Component {
    use WithPagination;
    
    public bool $showFilters = false;
    public bool $showTagTypeModal = false;
    
    public $tagName = '';
    public $selectedTagType = '';
    public $editingTagId = null;
    public ?Tag $tagToDelete = null;
    
    public $tagTypeName = '';
    public $editingTagTypeId = null;
    public ?TagType $tagTypeToDelete = null; 
    
    public $filters = [
        'name' => '',
        'type' => ''
    ];
    
    public $sortBy = 'id';
    public $sortDirection = 'asc';
    public $perPage = 10;
    
    public function mount()
    {
        $this->filters = session()->get('tags.filters', $this->filters);
        $this->sortBy = session()->get('tags.sortBy', $this->sortBy);
        $this->sortDirection = session()->get('tags.sortDirection', $this->sortDirection);
        $this->perPage = session()->get('tags.perPage', $this->perPage);
        
        $savedPage = session()->get('tags.page', 1);
        $this->setPage($savedPage);
        
        $this->validatePage();
    }

    public function updatedPage($value)
    {
        session()->put('tags.page', $value);
    }

    public function gotoPage($page)
    {
        $this->setPage($page);
        session()->put('tags.page', $page);
        $this->validatePage();
    }
    
    public function updatedPerPage($value)
    {
        session()->put('tags.perPage', $value);
        $this->resetPage();
        $this->validatePage();
    }
    
    public function updatedFilters($value, $key)
    {
        session()->put('tags.filters', $this->filters);
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset('filters');
        session()->forget('tags.filters');
        $this->resetPage();
        session()->put('tags.page', 1);
    }
    
    public function validatePage()
    {
        $tags = $this->getTags();
        
        if ($tags->currentPage() > $tags->lastPage()) {
            $this->setPage($tags->lastPage());
        }
    }
    
    #[Computed]
    public function getTags()
    {
        return Tag::query()
            ->with('type')
            ->when($this->filters['name'], function ($query) {
                $query->where('name', 'like', '%' . $this->filters['name'] . '%');
            })
            ->when($this->filters['type'], function ($query) {
                $query->where('type_id', $this->filters['type']); // Changed from types_id to type_id
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->paginate($this->perPage);
    }

    #[Computed]
    public function getTagTypes()
    {
        return TagType::orderBy('name')->get();
    }
    
    public function saveTag()
    {
        $this->validate([
            'tagName' => 'required|string|max:255',
            'selectedTagType' => 'required|exists:types,id'
        ]);
        
        if ($this->editingTagId) {
            $tag = Tag::find($this->editingTagId);
            $tag->update([
                'name' => $this->tagName,
                'type_id' => $this->selectedTagType // Changed from types_id to type_id
            ]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Tag Updated',
                text: 'Tag has been successfully updated.',
            );
        } else {
            Tag::create([
                'name' => $this->tagName,
                'type_id' => $this->selectedTagType // Changed from types_id to type_id
            ]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Tag Created',
                text: 'New tag has been successfully created.',
            );
        }
        
        $this->resetTagForm();
    }
    
    public function editTag($tagId)
    {
        $tag = Tag::find($tagId);
        
        if ($tag) {
            $this->editingTagId = $tag->id;
            $this->tagName = $tag->name;
            $this->selectedTagType = $tag->type_id; // Changed from types_id to type_id
        }
    }
    
    public function confirmTagDelete($tagId)
    {
        $this->tagToDelete = Tag::find($tagId);
        Flux::modal('delete-tag-modal')->show();
    }
    
    public function deleteTag()
    {
        if (!$this->tagToDelete) {
            $this->dispatch('toast', message: 'Tag not found', type: 'error');
            return;
        }
        
        try {
            TagCon::where('tag_id', $this->tagToDelete->id)->delete(); // Changed from tags_id to tag_id

            $this->tagToDelete->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'Tag Deleted.',
                text: 'Tag and all bus associations successfully deleted.',
            );
            
            $this->tagToDelete = null;
            Flux::modal('delete-tag-modal')->close();
            
            $this->resetPage();
            unset($this->getTags);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete tag: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function resetTagForm()
    {
        $this->reset(['tagName', 'selectedTagType', 'editingTagId']);
    }
    
    public function saveTagType()
    {
        $this->validate([
            'tagTypeName' => 'required|string|max:255|unique:types,name,'.$this->editingTagTypeId
        ]);
        
        if ($this->editingTagTypeId) {
            $tagType = TagType::find($this->editingTagTypeId);
            $tagType->update(['name' => $this->tagTypeName]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Tag Type Updated',
                text: 'Tag type has been successfully updated.',
            );
        } else {
            TagType::create(['name' => $this->tagTypeName]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Tag Type Created',
                text: 'New tag type has been successfully created.',
            );
        }
        
        $this->resetTagTypeForm();
        $this->showTagTypeModal = false;
    }
    
    public function editTagType($tagTypeId)
    {
        $tagType = TagType::find($tagTypeId);
        
        if ($tagType) {
            $this->editingTagTypeId = $tagType->id;
            $this->tagTypeName = $tagType->name;
            $this->showTagTypeModal = true;
        }
    }
    
    public function confirmTagTypeDelete($tagTypeId)
    {
        $this->tagTypeToDelete = TagType::find($tagTypeId);
        Flux::modal('delete-tag-type-modal')->show();
    }
    
    public function deleteTagType()
    {
        if (!$this->tagTypeToDelete) {
            $this->dispatch('toast', message: 'Tag type not found', type: 'error');
            return;
        }
        
        try {
            $tagIds = $this->tagTypeToDelete->tags()->pluck('id');
            
            TagCon::whereIn('tag_id', $tagIds)->delete(); // Changed from tags_id to tag_id
            
            $this->tagTypeToDelete->tags()->delete();
            
            $this->tagTypeToDelete->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'Tag Type Deleted.',
                text: 'Tag type, all associated tags and bus connections have been successfully deleted.',
            );
            
            $this->tagTypeToDelete = null;
            Flux::modal('delete-tag-type-modal')->close();
            
            $this->resetPage();
            unset($this->getTags);
            unset($this->getTagTypes);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete tag type: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function resetTagTypeForm()
    {
        $this->reset(['tagTypeName', 'editingTagTypeId']);
    }
    
    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        session()->put('tags.sortBy', $this->sortBy);
        session()->put('tags.sortDirection', $this->sortDirection);
        
        $this->validatePage();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">Tags Management</flux:heading></div>
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
            <div>
                <flux:button type="button" wire:click="$toggle('showTagTypeModal')">
                    Manage Tag Types
                </flux:button>
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
                    <flux:label>Tag Name</flux:label>
                    <flux:input wire:model.live="filters.name" placeholder="Search by name..." />
                </div>
                
                <div>
                    <flux:label>Tag Type</flux:label>
                    <flux:select wire:model.live="filters.type">
                        <option value="">All Types</option>
                        @foreach($this->getTagTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
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
        <flux:heading size="lg">{{ $editingTagId ? 'Edit Tag' : 'Create New Tag' }}</flux:heading>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div>
                <flux:label>Tag Name</flux:label>
                <flux:input wire:model="tagName" placeholder="Enter tag name" />
            </div>
            <div>
                <flux:label>Tag Type</flux:label>
                <flux:select wire:model="selectedTagType">
                    <option value="">Select a type</option>
                    @foreach($this->getTagTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="flex items-end gap-2">
                <flux:button type="button" wire:click="saveTag" variant="primary">
                    {{ $editingTagId ? 'Update Tag' : 'Create Tag' }}
                </flux:button>
                @if($editingTagId)
                    <flux:button type="button" wire:click="resetTagForm" variant="ghost">
                        Cancel
                    </flux:button>
                @endif
            </div>
        </div>
    </div>
    
    <br>
    @if($this->getTags()->count())
    <flux:table :paginate="$this->getTags()">
        <flux:table.columns>
            <flux:table.column class="text-center">No.</flux:table.column>
            <flux:table.column class="text-center">Actions</flux:table.column>
            <flux:table.column class="text-center" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column>Type</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->getTags() as $tag)
            <flux:table.row>
                <flux:table.cell class="text-center">{{ ($this->getTags()->currentPage() - 1) * $this->getTags()->perPage() + $loop->iteration }}.</flux:table.cell>
                <flux:table.cell class="text-center">
                <div class="flex items-center justify-center gap-2">
                    <flux:button 
                        icon="trash" 
                        variant="danger"
                        wire:click="confirmTagDelete({{ $tag->id }})"
                    ></flux:button>
                    <flux:button 
                        icon="pencil" 
                        variant="primary" 
                        wire:click="editTag({{ $tag->id }})"
                    ></flux:button>
                </div>
                </flux:table.cell>
                <flux:table.cell>({{$tag->id}})</flux:table.cell>
                <flux:table.cell>{{$tag->name}}</flux:table.cell>
                <flux:table.cell>{{$tag->type->name ?? 'N/A'}}</flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    
    <flux:modal name="delete-tag-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete tag?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->tagToDelete)
                        <p>You're about to delete <strong>{{ $this->tagToDelete->name }}</strong>.</p>
                        <p class="text-red-500 font-medium">This will also remove all bus associations with this tag.</p>
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
                    wire:click="deleteTag"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete Tag</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="text-center py-8">
        <p class="text-neutral-600 dark:text-neutral-400">No tags found. You've been redirected to the last available page.</p>
    </div>
    @endif
    
    <flux:modal wire:model="showTagTypeModal" class="min-w-[30rem] w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Manage Tag Types</flux:heading>
                
                <div class="mt-6">
                    <flux:heading size="md">{{ $editingTagTypeId ? 'Edit Tag Type' : 'Create New Tag Type' }}</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div class="md:col-span-2">
                            <flux:label>Type Name</flux:label>
                            <flux:input wire:model="tagTypeName" placeholder="Enter type name" />
                        </div>
                        <div class="flex items-end gap-2">
                            <flux:button type="button" wire:click="saveTagType" variant="primary">
                                {{ $editingTagTypeId ? 'Update Type' : 'Create Type' }}
                            </flux:button>
                            @if($editingTagTypeId)
                                <flux:button type="button" wire:click="resetTagTypeForm" variant="ghost">
                                    Cancel
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Name</flux:table.column>
                            <flux:table.column class="text-center">Actions</flux:table.column>
                        </flux:table.columns>
                        
                        <flux:table.rows>
                            @foreach ($this->getTagTypes as $type)
                            <flux:table.row>
                                <flux:table.cell>{{ $type->name }}</flux:table.cell>
                                <flux:table.cell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <flux:button 
                                            icon="trash" 
                                            variant="danger"
                                            wire:click="confirmTagTypeDelete({{ $type->id }})"
                                        ></flux:button>
                                        <flux:button 
                                            icon="pencil" 
                                            variant="primary" 
                                            wire:click="editTagType({{ $type->id }})"
                                        ></flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>
            
            <div class="flex gap-2">
                <flux:spacer />
                <flux:button 
                    variant="ghost" 
                    wire:click="resetTagTypeForm"
                    @click="$wire.showTagTypeModal = false"
                >
                    Close
                </flux:button>
            </div>
        </div>
    </flux:modal>
    
    <flux:modal name="delete-tag-type-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete tag type?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->tagTypeToDelete)
                        <p>You're about to delete <strong>{{ $this->tagTypeToDelete->name }}</strong>.</p>
                        <p class="text-red-500 font-medium">Warning: This will also delete all tags associated with this type and their bus connections.</p>
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
                    wire:click="deleteTagType"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete Type and All Associations</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>