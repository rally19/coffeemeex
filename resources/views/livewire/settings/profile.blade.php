<?php

use App\Models\User;
use Illuminate\Support\Facades\{Auth, Session, Storage};
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Title};
use Flux\Flux;

new #[Title('Profile')]
    class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone_numbers = '';
    public string $address = '';
    public string $gender = 'unknown';
    public $avatar;
    public $tempAvatar;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone_numbers = $user->phone_numbers ?? '';
        $this->address = $user->address ?? '';
        $this->gender = $user->gender ?? 'unknown';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'phone_numbers' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string'],
            'gender' => ['required', 'in:unknown,male,female,other'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $user->fill(collect($validated)->except('avatar')->toArray());

        if ($this->avatar) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $path = $this->avatar->store('avatars', 'public');
            $user->avatar = $path;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);

        Flux::toast(
            variant: 'success',
            heading: 'Changes saved.',
            text: 'Profile settings updated.',
            duration: 4000,
        );
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();
        
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }
        
        $this->avatar = null;
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <!-- Avatar Section -->
                <div class="space-y-4">
                    @if(auth()->user()->avatar)
                        <div class="flex items-start gap-4">
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar" class="h-20 w-20 rounded-full object-cover">
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

                <div class="space-y-6">
                    <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />
                    
                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </flux:text>
                            @endif
                        </div>
                    @endif

                    <flux:input wire:model="phone_numbers" :label="__('Phone Number')" type="tel" required />
                </div>

                <div class="space-y-6">
                    <flux:select wire:model="gender" :label="__('Gender')" required>
                        <option value="unknown">{{ __('Prefer not to say') }}</option>
                        <option value="male">{{ __('Male') }}</option>
                        <option value="female">{{ __('Female') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </flux:select>

                    <flux:textarea wire:model="address" :label="__('Address')" required rows="3" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>