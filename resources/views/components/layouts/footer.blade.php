<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 mt-auto">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Brand Column -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <x-app-logo class="w-8 h-8" />
                </div>
                <flux:text variant="subtle" class="text-sm">
                    Yogyakarta's beloved coffee destination where every cup tells a story.
                </flux:text>
            </div>

            <!-- Quick Links Column -->
            <div>
                <flux:heading size="md" class="mb-4">Quick Links</flux:heading>
                <div class="space-y-2">
                    <flux:link href="{{ route('home') }}" wire:navigate variant="subtle" size="sm">
                        Home
                    </flux:link><br>
                    <flux:link href="{{ route('menu') }}" wire:navigate variant="subtle" size="sm">
                        Menu
                    </flux:link><br>
                    <flux:link href="{{ route('about') }}" wire:navigate variant="subtle" size="sm">
                        About
                    </flux:link><br>
                    <flux:link href="{{ route('contact') }}" wire:navigate variant="subtle" size="sm">
                        Contact
                    </flux:link><br>
                    <flux:link href="{{ route('gallery') }}" wire:navigate variant="subtle" size="sm">
                        Gallery
                    </flux:link><br>
                    @auth
                    <flux:link href="{{ route('dashboard') }}" wire:navigate variant="subtle" size="sm">
                        Dashboard
                    </flux:link><br>
                    @endauth
                    @if ((auth()->user()->role ?? '') === 'admin' || (auth()->user()->role ?? '') === 'staff' && auth()->user()->hasVerifiedEmail())
                    <flux:link href="{{ route('admin') }}" wire:navigate variant="subtle" size="sm">
                        Admin
                    </flux:link><br>
                    @endif
                </div>
            </div>

            <!-- Legal & Contact Column -->
            <div>
                <flux:heading size="md" class="mb-4">Legal & Contact</flux:heading>
                <div class="space-y-2">
                    <flux:text variant="subtle" size="sm">
                        Jl. Coffee Street No. 123
                    </flux:text>
                    <flux:text variant="subtle" size="sm">
                        Yogyakarta 55281
                    </flux:text>
                    <flux:text variant="subtle" size="sm">
                        (0274) 567-8901
                    </flux:text><br>
                    <flux:link href="{{ route('legal') }}" wire:navigate variant="subtle" size="sm">
                        Terms & Privacy
                    </flux:link><br>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="my-6 border-t border-zinc-200 dark:border-zinc-700"></div>

        <!-- Bottom Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <flux:text variant="subtle" size="sm" class="text-center md:text-left">
                © {{ date('Y') }} Coffeemeex. All rights reserved.
            </flux:text>
            
            <!-- Social Media -->
            <div class="flex items-center gap-4">
                <flux:link href="https://instagram.com/coffeemeex" external variant="ghost" size="sm">
                    <flux:icon.user-circle class="w-5 h-5" />
                    <span class="sr-only">Instagram</span>
                </flux:link>
                <flux:link href="https://facebook.com/coffeemeex" external variant="ghost" size="sm">
                    <flux:icon.user-circle class="w-5 h-5" />
                    <span class="sr-only">Facebook</span>
                </flux:link>
                <flux:link href="https://twitter.com/coffeemeex" external variant="ghost" size="sm">
                    <flux:icon.user-circle class="w-5 h-5" />
                    <span class="sr-only">Twitter</span>
                </flux:link>
            </div>
        </div>
    </div>
</footer>