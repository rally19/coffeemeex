<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-neutral-900 antialiased">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-r dark:border-neutral-800">
                @php
                    // Fetch a random gallery image with status 'show'
                    $gallery = App\Models\Gallery::where('status', 'show')
                        ->whereNotNull('picture')
                        ->inRandomOrder()
                        ->first();
                    
                    // Fallback image if no gallery image is available
                    $fallbackPhotos = [
                        'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTt86DsU1Q3xw88uFHJBX_QMC8I3y-B6g4TyQ&s',
                    ];
                    
                    $randomPhoto = $gallery 
                        ? Storage::url($gallery->picture)
                        : Arr::random($fallbackPhotos);
                @endphp
                <div class="absolute inset-0 bg-neutral-900">
                    <img src="{{ $randomPhoto }}" class="w-full h-full object-cover" alt="Gallery image">
                    <div class="absolute inset-0 bg-gradient-to-b from-black/50 to-black/80"></div>
                </div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md">
                        <x-app-logo-icon class="mr-2 h-7 fill-current text-white" />
                    </span>
                    {{ config('app.name', 'Coffeeemeex') }}
                </a>

                @php
                    [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
                @endphp

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <flux:heading size="lg">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                        <footer><flux:heading>{{ trim($author) }}</flux:heading></footer>
                    </blockquote>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Coffeemeex') }}</span>
                    </a>
                    {{ $slot }}
                    <flux:button variant="subtle" size="sm" class="w-32 block mx-auto" :href="route('home')" wire:navigate>Return</flux:button>
                </div>
            </div>
        </div>
        @fluxScripts
        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>