@php
    use Illuminate\Support\Str;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
                <x-app-logo /><span class="font-bold">[
                                                      @if ((auth()->user()->role ?? '') === 'admin')
                                                      Admin
                                                      @else
                                                      Staff
                                                      @endif
                                                      ]</span>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Admin')" class="grid">
                    <flux:navlist.item icon="chart-bar-square" :href="route('admin')" :current="request()->routeIs('admin')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    @if ((auth()->user()->role ?? '') === 'admin' && auth()->user()->hasVerifiedEmail())
                    <flux:navlist.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
                    @endif
                    <flux:navlist.item icon="currency-dollar" :href="route('admin.orders')" :current="request()->routeIs('admin.orders')" wire:navigate>{{ __('Orders') }}</flux:navlist.item>
                    <flux:navlist.item icon="tag" :href="route('admin.tags')" :current="request()->routeIs('admin.tags')" wire:navigate>{{ __('Tags') }}</flux:navlist.item>
                    <flux:navlist.item icon="cube" :href="route('admin.items')" :current="request()->routeIs('admin.items')" wire:navigate>{{ __('Items') }}</flux:navlist.item>
                    <flux:navlist.item icon="photo" :href="route('admin.galleries')" :current="request()->routeIs('admin.galleries')" wire:navigate>{{ __('Gallery') }}</flux:navlist.item>
                    <flux:navlist.item icon="phone" :href="route('admin.contacts')" :current="request()->routeIs('admin.contacts')" wire:navigate>{{ __('Messages') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    avatar="{{ auth()->check() ? (auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('images/avatar.webp')) : asset('images/avatar.webp') }}"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <flux:avatar 
                                src="{{ auth()->check() ? (auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('images/avatar.webp')) : asset('images/avatar.webp') }}"
                                />

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item as="button" icon="arrow-right-start-on-rectangle" class="w-full" href="{{ route('dashboard') }}" wire:navigate>
                        {{ __('Exit') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                        
                    </form>
                    
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    avatar="{{ auth()->check() ? (auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('images/avatar.webp')) : asset('images/avatar.webp') }}"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <flux:avatar 
                                src="{{ auth()->check() ? (auth()->user()->avatar ? asset('storage/' . auth()->user()->avatar) : asset('images/avatar.webp')) : asset('images/avatar.webp') }}"
                                />

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item as="button" icon="arrow-right-start-on-rectangle" class="w-full" href="{{ route('dashboard') }}" wire:navigate>
                        {{ __('Exit') }}
                    </flux:menu.item>
                    
                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                        
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>
