<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 sticky top-0">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('home') }}" class="ml-2 mr-5 flex items-center space-x-2 lg:ml-0" wire:navigate>
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
            <flux:dropdown position="top" align="end">
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
        </flux:sidebar>

        <main>
            {{ $slot }}
        </main>

        <!-- Footer Section - Minimalist Design -->
        <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 mt-auto">
            <div class="container mx-auto px-4 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Brand Column -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2">
                            <x-app-logo class="w-8 h-8" />
                        </div>
                        <flux:text class="text-zinc-600 dark:text-zinc-400 text-sm">
                            Yogyakarta's beloved coffee destination where every cup tells a story.
                        </flux:text>
                    </div>

                    <!-- Quick Links Column -->
                    <div>
                        <flux:heading size="md" class="mb-4 text-zinc-800 dark:text-zinc-100">Quick Links</flux:heading>
                        <div class="space-y-2">
                            <a href="{{ route('home') }}" wire:navigate 
                               class="block text-zinc-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 text-sm transition-colors">
                                Home
                            </a>
                            <a href="{{ route('menu') }}" wire:navigate 
                               class="block text-zinc-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 text-sm transition-colors">
                                Menu
                            </a>
                            <a href="{{ route('about') }}" wire:navigate 
                               class="block text-zinc-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 text-sm transition-colors">
                                About Us
                            </a>
                            <a href="{{ route('contact') }}" wire:navigate 
                               class="block text-zinc-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 text-sm transition-colors">
                                Contact
                            </a>
                        </div>
                    </div>

                    <!-- Legal & Contact Column -->
                    <div>
                        <flux:heading size="md" class="mb-4 text-zinc-800 dark:text-zinc-100">Legal & Contact</flux:heading>
                        <div class="space-y-2">
                            <a href="{{ route('legal') }}" wire:navigate 
                               class="block text-zinc-600 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 text-sm transition-colors">
                                Terms & Privacy
                            </a>
                            <flux:text class="text-zinc-600 dark:text-zinc-400 text-sm">
                                Jl. Coffee Street No. 123
                            </flux:text>
                            <flux:text class="text-zinc-600 dark:text-zinc-400 text-sm">
                                Yogyakarta 55281
                            </flux:text>
                            <flux:text class="text-zinc-600 dark:text-zinc-400 text-sm">
                                (0274) 567-8901
                            </flux:text>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="my-6 border-t border-zinc-200 dark:border-zinc-700"></div>

                <!-- Bottom Bar -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-500 text-center md:text-left">
                        © {{ date('Y') }} Coffeemeex. All rights reserved.
                    </flux:text>
                    
                    <!-- Social Media -->
                    <div class="flex items-center gap-4">
                        <a href="https://instagram.com/coffeemeex" target="_blank" rel="noopener noreferrer" 
                           class="text-zinc-500 hover:text-amber-600 dark:text-zinc-400 dark:hover:text-amber-400 transition-colors">
                            <flux:icon.user-circle class="w-5 h-5" />
                            <span class="sr-only">Instagram</span>
                        </a>
                        <a href="https://facebook.com/coffeemeex" target="_blank" rel="noopener noreferrer" 
                           class="text-zinc-500 hover:text-amber-600 dark:text-zinc-400 dark:hover:text-amber-400 transition-colors">
                            <flux:icon.user-circle class="w-5 h-5" />
                            <span class="sr-only">Facebook</span>
                        </a>
                        <a href="https://twitter.com/coffeemeex" target="_blank" rel="noopener noreferrer" 
                           class="text-zinc-500 hover:text-amber-600 dark:text-zinc-400 dark:hover:text-amber-400 transition-colors">
                            <flux:icon.user-circle class="w-5 h-5" />
                            <span class="sr-only">Twitter</span>
                        </a>
                    </div>
                </div>
            </div>
        </footer>

        @fluxScripts
        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>