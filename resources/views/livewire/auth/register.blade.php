<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Flux\Flux;

new #[Layout('components.layouts.auth')] class extends Component {
    public int $currentStep = 1;
    public array $step1 = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
    ];
    
    public array $step2 = [
        'phone_numbers' => '',
        'address' => '',
    ];
    
    public array $step3 = [
        'gender' => 'unknown',
    ];

    /**
     * Validate current step and proceed to next
     */
    public function nextStep(): void
    {
        if ($this->currentStep === 1) {
            $this->validateStep1();
        } elseif ($this->currentStep === 2) {
            $this->validateStep2();
        }
        
        $this->currentStep++;
    }

    /**
     * Go back to previous step
     */
    public function previousStep(): void
    {
        $this->currentStep--;
    }

    /**
     * Validate step 1 data
     */
    protected function validateStep1(): void
    {
        $this->validate([
            'step1.name' => ['required', 'string', 'max:255'],
            'step1.email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class . ',email'],
            'step1.password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);
    }

    /**
     * Validate step 2 data
     */
    protected function validateStep2(): void
    {
        $this->validate([
            'step2.phone_numbers' => ['required', 'string', 'max:20'],
            'step2.address' => ['required', 'string', 'max:500'],
        ]);
    }

    /**
     * Handle registration
     */
    public function register(): void
    {
        $this->validateStep1();
        $this->validateStep2();
        $this->validate([
            'step3.gender' => ['required', 'string', 'in:unknown,male,female,other'],
        ]);

        $userData = array_merge(
            [
                'name' => $this->step1['name'],
                'email' => $this->step1['email'],
                'password' => Hash::make($this->step1['password']),
                'role' => 'user',
                'avatar' => null,
                'email_verified_at' => null,
            ],
            $this->step2,
            $this->step3
        );

        event(new Registered(($user = User::create($userData))));

        Auth::login($user);

        Flux::toast(
            variant: 'success',
            heading: 'Account successfully created.',
            text: 'Welcome ' . Auth::user()->name . '!',
            duration: 8000,
        );

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header 
        :title="__('Create an account')" 
        :description="__('Step '.$currentStep.' of 3')" 
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit.prevent="register" class="flex flex-col gap-6">
        <!-- Step 1: Basic Info -->
        <div x-show="$wire.currentStep === 1" class="space-y-6">
            <flux:input
                wire:model="step1.name"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <flux:input
                wire:model="step1.email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                wire:model="step1.password"
                :label="__('Password')"
                type="password"
                required
                viewable
                autocomplete="new-password"
                :placeholder="__('Password')"
            />

            <flux:input
                wire:model="step1.password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                viewable
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
            />
        </div>

        <!-- Step 2: Contact Info -->
        <div x-show="$wire.currentStep === 2" class="space-y-6">
            <flux:input
                wire:model="step2.phone_numbers"
                :label="__('Phone Number')"
                type="tel"
                required
                autofocus
                :placeholder="__('Your phone number')"
            />

            <flux:textarea
                wire:model="step2.address"
                :label="__('Address')"
                required
                :placeholder="__('Your complete address')"
                rows="3"
            />
        </div>

        <!-- Step 3: Personal Info -->
        <div x-show="$wire.currentStep === 3" class="space-y-6">
            <flux:select
                wire:model="step3.gender"
                :label="__('Gender')"
                required
                autofocus
            >
                <option value="unknown">{{ __('Prefer not to say') }}</option>
                <option value="male">{{ __('Male') }}</option>
                <option value="female">{{ __('Female') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </flux:select>

            <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('By creating an account, you agree to our ') }}
                <flux:link :href="route('legal')" target="_blank">{{ __('Terms of Service and Privacy Policy') }}</flux:link>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <template x-if="$wire.currentStep > 1">
                <flux:button 
                    type="button" 
                    wire:click="previousStep"
                >
                    {{ __('Back') }}
                </flux:button>
            </template>

            <div class="flex-1"></div>

            <template x-if="$wire.currentStep < 3">
                <flux:button 
                    type="button" 
                    variant="primary" 
                    wire:click="nextStep"
                >
                    {{ __('Continue') }}
                </flux:button>
            </template>

            <template x-if="$wire.currentStep === 3">
                <flux:button 
                    type="submit" 
                    variant="primary"
                >
                    {{ __('Create account') }}
                </flux:button>
            </template>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Already have an account?') }}
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>