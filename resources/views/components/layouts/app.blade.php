<x-layouts.app.header :title="$title ?? null">
    <div>
        <flux:main>
            {{ $slot }}
        </flux:main>
    </div>
    @include('components.layouts.footer')
</x-layouts.app.header>
