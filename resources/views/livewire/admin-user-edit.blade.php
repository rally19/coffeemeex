<?php
use Livewire\Attributes\{Layout, Title};
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\{Rules, Rule};
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Component;
use App\Models\User;
use Flux\Flux;

new #[Layout('components.layouts.admin')]
    #[Title('Edit User')]
    
class extends Component {
    use WithFileUploads;
    
    public string $name = '';
    public string $email = '';
    public ?string $email_verified_at = '';
    public string $password_old = '';
    public string $password = '';
    public string $role = 'user';
    public string $phone_numbers = '';
    public string $address = '';
    public string $gender = 'unknown';
    public $avatar;
    public $user;

    public function mount(): void
    {
        $this->user = User::find(request()->route('id'));
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->email_verified_at = $this->user->email_verified_at;
        $this->password_old = $this->user->password;
        $this->password = $this->user->password;
        $this->role = $this->user->role ?? 'user';
        $this->phone_numbers = $this->user->phone_numbers;
        $this->address = $this->user->address;
        $this->gender = $this->user->gender ?? 'unknown';
    }

    public function updateUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user->id),
            ],
            'email_verified_at' => ['nullable', 'date'],
            'password' => ['required', 'string', Rules\Password::defaults()],
            'role' => ['required', 'in:user,staff,admin'],
            'phone_numbers' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string'],
            'gender' => ['required', 'in:unknown,male,female,other'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validated['password'] !== $this->password_old) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            $validated['password'] = $this->password_old;
        }

        if ($validated['email_verified_at'] === '') {
            $validated['email_verified_at'] = null;
        }

        $this->user->fill(collect($validated)->except('avatar')->toArray());

        if ($this->avatar) {
            if ($this->user->avatar) {
                Storage::disk('public')->delete($this->user->avatar);
            }
            
            $path = $this->avatar->store('avatars', 'public');
            $this->user->avatar = $path;
        }

        if ($this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        $this->dispatch('profile-updated', name: $this->user->name);

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'User settings updated.',
            duration: 4000,
        );
    }

    public function removeAvatar(): void
    {
        if ($this->user->avatar) {
            Storage::disk('public')->delete($this->user->avatar);
            $this->user->avatar = null;
            $this->user->save();
        }
        
        $this->avatar = null;
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4 sm:hidden">
        <div>
            <flux:button icon="eye" :href="route('admin.view.user', ['id' => $user->id])" wire:navigate></flux:button>
        </div>
        <div class="flex items-center gap-4">
            <div>
                <flux:button :href="route('admin.users')" wire:navigate>Back</flux:button>
            </div>
        </div>
    </div>
    <div class="flex items-center justify-between mb-4">
        <div><flux:heading size="xl">Edit User ({{ $user->id }}) <span class="font-extrabold">{{ $user->email }}</span></flux:heading></div>
        <div class="flex items-center gap-4">
            <div class="hidden sm:block">
                <flux:button icon="eye" :href="route('admin.view.user', ['id' => $user->id])" wire:navigate></flux:button>
            </div>
            <div class="hidden sm:block">
                <flux:button :href="route('admin.users')" wire:navigate>Back</flux:button>
            </div>
        </div>
    </div>
    <div class="space-y-6">
        <form wire:submit="updateUser" class="flex flex-col gap-6">
            <div class="space-y-4">
                <div class="space-y-4">
                    @if($user->avatar)
                        <div class="flex items-start gap-4">
                            <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="h-20 w-20 rounded-full object-cover">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('Current Avatar') }}
                                </label>
                                <flux:button variant="danger" wire:click="removeAvatar" type="button">
                                    {{ __('Remove Avatar') }}
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-4">
                            @if($avatar)
                                <img src="{{ $avatar->temporaryUrl() }}" alt="Preview" class="h-20 w-20 rounded-full object-cover">
                            @endif
                            <flux:input 
                                type="file" 
                                wire:model="avatar" 
                                :label="__('Avatar')" 
                                accept="image/jpeg,image/png"
                            />
                        </div>
                    @endif
                </div>
                
                <flux:input 
                    wire:model="name" 
                    :label="__('Name')" 
                    type="text" 
                    required 
                    autofocus 
                    autocomplete="name" 
                    :placeholder="__('Full name')" 
                />
                
                <flux:input 
                    wire:model="email" 
                    :label="__('Email address')" 
                    type="email" 
                    required 
                    autocomplete="email" 
                    placeholder="email@example.com" 
                />
                
                <flux:input 
                    wire:model="email_verified_at" 
                    :label="__('Email Verification')" 
                    type="datetime-local" 
                    clearable
                    step="1"
                />
                
                <flux:input 
                    wire:model="password" 
                    :label="__('Password')" 
                    type="password" 
                    required 
                    autocomplete="new-password" 
                    :placeholder="__('Password')" 
                    viewable 
                />

                <flux:select 
                    wire:model="role" 
                    :label="__('Role')" 
                    required
                >
                    <option value="user">{{ __('User') }}</option>
                    <option value="staff">{{ __('Staff') }}</option>
                    <option value="admin">{{ __('Admin') }}</option>
                </flux:select>
                
                <flux:input 
                    wire:model="phone_numbers" 
                    :label="__('Phone Number')" 
                    type="tel" 
                    required 
                    :placeholder="__('Your phone number')" 
                />
                
                <flux:textarea 
                    wire:model="address" 
                    :label="__('Address')" 
                    required 
                    :placeholder="__('Your complete address')" 
                    rows="3" 
                />
                
                <flux:select 
                    wire:model="gender" 
                    :label="__('Gender')" 
                    required
                >
                    <option value="unknown">{{ __('Prefer not to say') }}</option>
                    <option value="male">{{ __('Male') }}</option>
                    <option value="female">{{ __('Female') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </flux:select>
            </div>
            
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </div>
</div>