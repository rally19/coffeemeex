<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 sticky top-0">
            <flux:sidebar.toggle class="lg:hidden top-1 lg:top-0" icon="bars-2" inset="left" />

            <a href="{{ route('home') }}" class="ml-2 mr-5 flex items-center space-x-2 lg:ml-0 mt-2 lg:mt-0" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                    {{ __('Home') }}
                </flux:navbar.item>
                <flux:navbar.item icon="shopping-bag" :href="route('menu')" :current="request()->routeIs('menu')" wire:navigate>
                    {{ __('Menu') }}
                </flux:navbar.item>
                <flux:navbar.item icon="question-mark-circle" :href="route('about')" :current="request()->routeIs('about')" wire:navigate>
                    {{ __('About') }}
                </flux:navbar.item>
                <flux:navbar.item icon="phone" :href="route('contact')" :current="request()->routeIs('contact')" wire:navigate>
                    {{ __('Contact') }}
                </flux:navbar.item>
                <flux:navbar.item icon="photo" :href="route('gallery')" :current="request()->routeIs('gallery')" wire:navigate>
                    {{ __('Gallery') }}
                </flux:navbar.item>
                @auth
                <flux:navbar.item icon="chart-bar-square" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                @endauth
            </flux:navbar>

            <flux:spacer />

            <!-- Cart Icon with Badge -->
            

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end" class="mt-2 lg:mt-0">
                <div class="flex items-center gap-4">
                    @auth
                        <div class="relative">
                            <flux:button variant="ghost" href="{{ route('cart') }}" wire:navigate class="flex items-center">
                                <flux:icon.shopping-cart class="w-6 h-6" :variant="request()->routeIs('cart') ? 'solid' : null" />
                                @livewire('cart-count')
                            </flux:button>
                        </div>
                    @endauth

                    <flux:profile
                        class="cursor-pointer"
                        avatar="{{ auth()->check() ? (auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('images/avatar.webp')) : asset('images/avatar.webp') }}"
                    />
                </div>
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <flux:avatar 
                                src="{{ auth()->check() ? (auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('images/avatar.webp')) : asset('images/avatar.webp') }}"
                                />

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">
                                        {{ auth()->check() ? auth()->user()->name : 'Guest' }}
                                    </span>
                                    <span class="truncate text-xs">
                                        {{ auth()->check() ? auth()->user()->email : 'Please sign in' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        {{-- @auth
                        <flux:menu.item :href="route('cart')" icon="shopping-cart" wire:navigate>
                            {{ __('Cart') }}
                            @livewire('cart-count')
                        </flux:menu.item>
                        @endauth --}}
                        
                        @if ((auth()->user()->role ?? '') === 'admin' || (auth()->user()->role ?? '') === 'staff' && auth()->user()->hasVerifiedEmail())
                        <flux:menu.item :href="route('admin')" icon="arrow-right-end-on-rectangle" wire:navigate> {{ __('Admin') }} </flux:menu.item>
                        @endif
                        @auth
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        @else
                        <flux:menu.item :href="route('login')" icon="arrow-left-end-on-rectangle" wire:navigate>{{ __('Login') }}</flux:menu.item>
                        @endauth
                    </flux:menu.radio.group>
                    @auth
                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                        
                    </form>
                    @endauth
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ml-1 flex items-center space-x-2" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')">
                    <flux:navlist.item icon="home" :href="route('home')" :current="request()->routeIs('home')" wire:navigate>
                    {{ __('Home') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="shopping-bag" :href="route('menu')" :current="request()->routeIs('menu')" wire:navigate>
                    {{ __('Menu') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="question-mark-circle" :href="route('about')" :current="request()->routeIs('about')" wire:navigate>
                    {{ __('About') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="phone" :href="route('contact')" :current="request()->routeIs('contact')" wire:navigate>
                    {{ __('Contact') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="photo" :href="route('gallery')" :current="request()->routeIs('gallery')" wire:navigate>
                    {{ __('Gallery') }}
                    </flux:navlist.item>
                    @auth
                    <flux:navlist.item icon="chart-bar-square" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    @endauth
                </flux:navlist.group>
            </flux:navlist>
            
            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/rally19/coffeemeex" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>