<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use App\Models\Contact;
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('Contacts')]
class extends Component {
    use WithFileUploads, WithPagination;
    public bool $showFilters = false;
    public ?Contact $contactToDelete = null;
    public ?Contact $viewingContact = null;
    public $filters = [
        'subject' => '',
        'replied' => ''
    ];
    public $search = [
        'name' => '',
        'email' => '',
    ];
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    
    public function mount()
    {
        $this->search = session()->get('contacts.search', $this->search);
        
        $this->filters = session()->get('contacts.filters', $this->filters);
        
        $this->sortBy = session()->get('contacts.sortBy', $this->sortBy);
        
        $this->sortDirection = session()->get('contacts.sortDirection', $this->sortDirection);
        
        $this->perPage = session()->get('contacts.perPage', $this->perPage);
        
        $savedPage = session()->get('contacts.page', 1);
        $this->setPage($savedPage);
        
        $this->validatePage();
    }
    
    public function updatedPerPage($value)
    {
        session()->put('contacts.perPage', $value);
        $this->resetPage();
        $this->validatePage();
    }
    
    public function updatedSearch($value, $key)
    {
        session()->put('contacts.search', $this->search);
        $this->resetPage();
    }

    public function updatedFilters($value, $key)
    {
        session()->put('contacts.filters', $this->filters);
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset('search');
        $this->reset('filters');
        session()->forget('contacts.search');
        session()->forget('contacts.filters');
        $this->resetPage();
        session()->put('contacts.page', 1);
    }
    
    public function validatePage()
    {
        $contacts = $this->getContacts();
        
        if ($contacts->currentPage() > $contacts->lastPage()) {
            $this->setPage($contacts->lastPage());
        }
    }
    
    #[Computed]
    public function getContacts()
    {
        return Contact::query()
            ->when($this->search['name'], function ($query) {
                $query->where('name', 'like', '%'.$this->search['name'].'%');
            })
            ->when($this->search['email'], function ($query) {
                $query->where('email', 'like', '%'.$this->search['email'].'%');
            })
            ->when($this->filters['subject'], function ($query) {
                $query->where('subject', 'like', '%'.$this->filters['subject'].'%');
            })
            ->when($this->filters['replied'] === 'replied', function ($query) {
                $query->whereNotNull('replied_at');
            })
            ->when($this->filters['replied'] === 'not_replied', function ($query) {
                $query->whereNull('replied_at');
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->with('user')
            ->paginate($this->perPage);
    }

    public function confirmDelete($contactId)
    {
        $this->contactToDelete = Contact::find($contactId);
        Flux::modal('delete-contact-modal')->show();
    }
    
    public function deleteContact()
    {
        if (!$this->contactToDelete) {
            $this->dispatch('toast', message: 'Contact not found', type: 'error');
            return;
        }
        
        try {
            $this->contactToDelete->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'Contact Deleted.',
                text: 'Contact successfully deleted.',
            );
            
            $this->contactToDelete = null;
            Flux::modal('delete-contact-modal')->close();
            
            $this->resetPage();
            unset($this->getContacts);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete contact: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        session()->put('contacts.sortBy', $this->sortBy);
        session()->put('contacts.sortDirection', $this->sortDirection);
        
        $this->validatePage();
    }
    
    public function viewContact($contactId)
    {
        $this->viewingContact = Contact::with('user')->find($contactId);
        Flux::modal('view-contact-modal')->show();
    }
    
    public function markAsReplied()
    {
        if (!$this->viewingContact) {
            return;
        }
        
        try {
            $this->viewingContact->update([
                'replied_at' => now()
            ]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Marked as Replied',
                text: 'Contact has been marked as replied.',
            );
            
            unset($this->getContacts);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to mark as replied: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function markAsUnreplied()
    {
        if (!$this->viewingContact) {
            return;
        }
        
        try {
            $this->viewingContact->update([
                'replied_at' => null
            ]);
            
            Flux::toast(
                variant: 'success',
                heading: 'Marked as Unreplied',
                text: 'Contact has been marked as unreplied.',
            );
            
            unset($this->getContacts);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to mark as unreplied: ' . $e->getMessage(), type: 'error');
        }
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">Contact Messages</flux:heading></div>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block">
                <flux:button type="button" wire:click="$toggle('showFilters')" variant="subtle">
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
            <flux:button type="button" wire:click="$toggle('showFilters')" variant="subtle">
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
                    <flux:label>Name</flux:label>
                    <flux:input wire:model.live="search.name" placeholder="Search by name..." />
                </div>
                
                <div>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model.live="search.email" placeholder="Search by email..." />
                </div>
                
                <div>
                    <flux:label>Subject</flux:label>
                    <flux:input wire:model.live="filters.subject" placeholder="Search by subject..." />
                </div>
                
                <div>
                    <flux:label>Reply Status</flux:label>
                    <flux:select wire:model.live="filters.replied">
                        <option value="">All Messages</option>
                        <option value="replied">Replied</option>
                        <option value="not_replied">Not Replied</option>
                    </flux:select>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <flux:button type="button" wire:click="resetFilters" variant="subtle">
                    Reset Filters
                </flux:button>
            </div>
        </div>
    </div>
    
    <br>
    @if($this->getContacts()->count())
    <flux:table :paginate="$this->getContacts()">
        <flux:table.columns>
            <flux:table.column class="text-center">No.</flux:table.column>
            <flux:table.column class="text-center">Actions</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'subject'" :direction="$sortDirection" wire:click="sort('subject')">Subject</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">Email</flux:table.column>
            <flux:table.column>Phone Number</flux:table.column>
            <flux:table.column>Address</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'replied_at'" :direction="$sortDirection" wire:click="sort('replied_at')">Reply Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Date</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->getContacts() as $contact)
            <flux:table.row>
                <flux:table.cell class="text-center">{{ ($this->getContacts()->currentPage() - 1) * $this->getContacts()->perPage() + $loop->iteration }}.</flux:table.cell>
                <flux:table.cell class="text-center">
                    <div class="flex items-center justify-center gap-2">
                        <flux:button 
                            icon="trash" 
                            variant="danger"
                            wire:click="confirmDelete({{ $contact->id }})"
                        ></flux:button>
                        <flux:button 
                            icon="eye" 
                            variant="primary"
                            wire:click="viewContact({{ $contact->id }})"
                        ></flux:button>
                    </div>
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium">{{ $contact->subject }}</div>
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium">{{ $contact->name }}</div>
                    @if($contact->user)
                        <div class="text-xs text-gray-500">User ID: {{ $contact->user_id }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>{{ $contact->email }}</flux:table.cell>
                <flux:table.cell>{{ $contact->phone_numbers ?? '-' }}</flux:table.cell>
                <flux:table.cell>{{ $contact->address ?? '-' }}</flux:table.cell>
                <flux:table.cell>
                    @if($contact->replied_at)
                        <span class="inline-flex items-center gap-1">
                            <flux:icon.check-circle variant="solid" class="w-4 h-4 text-green-600" />
                            <span class="text-green-700 font-medium">Replied</span>
                            <div class="text-xs text-gray-500">{{ $contact->replied_at->format('Y-m-d H:i') }}</div>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1">
                            <flux:icon.x-circle variant="solid" class="w-4 h-4 text-red-600" />
                            <span class="text-red-700 font-medium">Pending</span>
                        </span>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium">{{ $contact->created_at->format('Y-m-d') }}</div>
                    <div class="text-xs text-gray-500">{{ $contact->created_at->format('H:i') }}</div>
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-contact-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Contact?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->contactToDelete)
                        <p>You're about to delete contact message from <strong>{{ $this->contactToDelete->name }}</strong> ({{ $this->contactToDelete->email }}).</p>
                        <p>Subject: <strong>{{ $this->contactToDelete->subject }}</strong></p>
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
                    wire:click="deleteContact"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete Contact</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- View Contact Modal -->
    <flux:modal name="view-contact-modal" class="min-w-[28rem] max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Contact Message Details</flux:heading>
                @if($this->viewingContact)
                    <div class="mt-4 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <flux:label>Name</flux:label>
                                <flux:text class="font-medium">{{ $viewingContact->name }}</flux:text>
                                @if($viewingContact->user)
                                    <flux:text class="text-sm text-gray-500">User ID: {{ $viewingContact->user_id }}</flux:text>
                                @endif
                            </div>
                            <div>
                                <flux:label>Email</flux:label>
                                <flux:text class="font-medium">{{ $viewingContact->email }}</flux:text>
                            </div>
                            <div>
                                <flux:label>Phone Number</flux:label>
                                <flux:text>{{ $viewingContact->phone_numbers ?? '-' }}</flux:text>
                            </div>
                            <div>
                                <flux:label>Address</flux:label>
                                <flux:text>{{ $viewingContact->address ?? '-' }}</flux:text>
                            </div>
                        </div>
                        
                        <div>
                            <flux:label>Subject</flux:label>
                            <flux:text class="font-medium text-lg">{{ $viewingContact->subject }}</flux:text>
                        </div>
                        
                        <div>
                            <flux:label>Message</flux:label>
                            <div class="mt-2 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <flux:text class="whitespace-pre-wrap">{{ $viewingContact->message }}</flux:text>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <flux:label>Submitted</flux:label>
                                <flux:text>
                                    {{ $viewingContact->created_at->format('Y-m-d H:i:s') }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:label>Reply Status</flux:label>
                                <div class="flex items-center gap-2">
                                    @if($viewingContact->replied_at)
                                        <flux:icon.check-circle variant="solid" class="w-5 h-5 text-green-600" />
                                        <flux:text class="font-medium text-green-700">Replied on {{ $viewingContact->replied_at->format('Y-m-d H:i') }}</flux:text>
                                    @else
                                        <flux:icon.x-circle variant="solid" class="w-5 h-5 text-red-600" />
                                        <flux:text class="font-medium text-red-700">Not Replied</flux:text>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex gap-2">
                @if($this->viewingContact)
                    @if($viewingContact->replied_at)
                        <flux:button 
                            variant="filled"
                            wire:click="markAsUnreplied"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Mark as Unreplied</span>
                            <span wire:loading>Updating...</span>
                        </flux:button>
                    @else
                        <flux:button 
                            variant="filled"
                            wire:click="markAsReplied"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Mark as Replied</span>
                            <span wire:loading>Updating...</span>
                        </flux:button>
                    @endif
                @endif
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="text-center py-8">
        <p class="text-gray-500">No contact messages found. You've been redirected to the last available page.</p>
    </div>
    @endif
</div>