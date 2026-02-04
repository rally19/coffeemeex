<?php
use Livewire\Attributes\{Layout, Title, Computed, Url};
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('Users')]
class extends Component {
    use WithFileUploads, WithPagination;
    public string $name = '';
    public string $email = '';
    public string $email_verify_at = '';
    public string $password = '';
    public string $role = 'user';
    public string $phone_number = '';
    public string $address = '';
    public string $gender = 'unknown';
    public bool $showFilters = false;
    public ?User $userToDelete = null;
    public $avatar;
    public $filters = [
        'role' => '',
        'gender' => ''
    ];
    public $search = [
        'name' => '',
        'email' => '',
    ];
    public $sortBy = 'id';
    public $sortDirection = 'asc';
    public $perPage = 10;
    
    public function mount()
    {
        $this->search = session()->get('users.search', $this->search);
        
        $this->filters = session()->get('users.filters', $this->filters);
        
        $this->sortBy = session()->get('users.sortBy', $this->sortBy);
        
        $this->sortDirection = session()->get('users.sortDirection', $this->sortDirection);
        
        $this->perPage = session()->get('users.perPage', $this->perPage);
        
        $savedPage = session()->get('users.page', 1);
        $this->setPage($savedPage);
        
        $this->validatePage();
    }
    
    public function updatedPerPage($value)
    {
        session()->put('users.perPage', $value);
        $this->resetPage();
        $this->validatePage();
    }
    
    public function updatedSearch($value, $key)
    {
        session()->put('users.search', $this->search);
        $this->resetPage();
    }

    public function updatedFilters($value, $key)
    {
        session()->put('users.filters', $this->filters);
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset('search');
        $this->reset('filters');
        session()->forget('users.search');
        session()->forget('users.filters');
        $this->resetPage();
        session()->put('users.page', 1); // Changed from 'terminals.page' to 'users.page'
    }
    
    public function validatePage()
    {
        $users = $this->getUsers();
        
        if ($users->currentPage() > $users->lastPage()) {
            $this->setPage($users->lastPage());
        }
    }
    
    #[Computed]
    public function getUsers()
    {
        return User::query()
            ->when($this->search['name'], function ($query) {
                $query->where('name', 'like', '%'.$this->search['name'].'%');
            })
            ->when($this->search['email'], function ($query) {
                $query->where('email', 'like', '%'.$this->search['email'].'%');
            })
            ->when($this->filters['role'], function ($query) {
                $query->where('role', $this->filters['role']);
            })
            ->when($this->filters['gender'], function ($query) {
                $query->where('gender', $this->filters['gender']);
            })
            ->when($this->sortBy, function ($query) {
                $query->orderBy($this->sortBy, $this->sortDirection);
            })
            ->paginate($this->perPage);
    }

    public function confirmDelete($userId)
    {
        $this->userToDelete = User::find($userId);
        Flux::modal('delete-user-modal')->show();
    }
    
    public function deleteUser()
    {
        if (!$this->userToDelete) {
            $this->dispatch('toast', message: 'User not found', type: 'error');
            return;
        }
        
        try {
            if ($this->userToDelete->avatar) {
                Storage::disk('public')->delete($this->userToDelete->avatar);
            }
            
            $this->userToDelete->delete();
            
            Flux::toast(
                variant: 'success',
                heading: 'User Deleted.',
                text: 'User successfully deleted.',
            );
            
            $this->userToDelete = null;
            Flux::modal('delete-user-modal')->close();
            
            $this->resetPage();
            unset($this->getUsers);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Failed to delete user: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        
        session()->put('users.sortBy', $this->sortBy);
        session()->put('users.sortDirection', $this->sortDirection);
        
        $this->validatePage();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">Users</flux:heading></div>
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
            <div><livewire:admin-user-create/></div>
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
                    <flux:label>Name</flux:label>
                    <flux:input wire:model.live="search.name" placeholder="Search by name..." />
                </div>
                
                <div>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model.live="search.email" placeholder="Search by email..." />
                </div>
                
                <div>
                    <flux:label>Role</flux:label>
                    <flux:select wire:model.live="filters.role">
                        <option value="">All Roles</option>
                        <option value="user">User</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </flux:select>
                </div>
                
                <div>
                    <flux:label>Gender</flux:label>
                    <flux:select wire:model.live="filters.gender">
                        <option value="">All Genders</option>
                        <option value="unknown">Unknown</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
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
    @if($this->getUsers()->count())
    <flux:table :paginate="$this->getUsers()">
        <flux:table.columns>
            <flux:table.column class="text-center">No.</flux:table.column>
            <flux:table.column class="text-center">Actions</flux:table.column>
            <flux:table.column class="text-center" sortable :sorted="$sortBy === 'id'" :direction="$sortDirection" wire:click="sort('id')">ID</flux:table.column>
            <flux:table.column>Avatar</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection" wire:click="sort('email')">Email</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'email_verified_at'" :direction="$sortDirection" wire:click="sort('email_verified_at')">Verified at</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Phone Number</flux:table.column>
            <flux:table.column>Address</flux:table.column>
            <flux:table.column>Gender</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->getUsers() as $user)
            <flux:table.row>
                <flux:table.cell class="text-center">{{ ($this->getUsers()->currentPage() - 1) * $this->getUsers()->perPage() + $loop->iteration }}.</flux:table.cell>
                <flux:table.cell class="text-center">
                    <div class="flex items-center justify-center gap-2">
                        <flux:button 
                            icon="trash" 
                            variant="danger"
                            wire:click="confirmDelete({{ $user->id }})"
                        ></flux:button>
                        <flux:button icon="pencil" variant="primary" :href="route('admin.edit.user', ['id' => $user->id])" wire:navigate></flux:button>
                        <flux:button icon="eye" :href="route('admin.view.user', ['id' => $user->id])" wire:navigate></flux:button>
                    </div>
                </flux:table.cell>
                <flux:table.cell>({{$user->id}})</flux:table.cell>
                <flux:table.cell><flux:avatar src="{{ ($user->avatar ? asset('storage/' . $user->avatar) : asset('images/avatar.webp')) }}" /></flux:table.cell>
                <flux:table.cell>{{$user->name}}</flux:table.cell>
                <flux:table.cell>{{$user->email}}</flux:table.cell>
                <flux:table.cell>{{$user->email_verified_at}}</flux:table.cell>
                <flux:table.cell>{{$user->role}}</flux:table.cell>
                <flux:table.cell>{{$user->phone_numbers}}</flux:table.cell>
                <flux:table.cell>{{$user->address}}</flux:table.cell>
                <flux:table.cell>{{$user->gender}}</flux:table.cell>
                <flux:table.cell></flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
    <flux:modal name="delete-user-modal" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete user?</flux:heading>
                <flux:text class="mt-2">
                    @if($this->userToDelete)
                        <p>You're about to delete <strong>{{ $this->userToDelete->name }}</strong> ({{ $this->userToDelete->email }}).</p>
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
                    wire:click="deleteUser"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Delete User</span>
                    <span wire:loading>Deleting...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @else
    <div class="text-center py-8">
        <p class="text-gray-500">No users found. You've been redirected to the last available page.</p>
    </div>
    @endif
</div>