<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\Volt\Component;
use App\Models\Gallery;

new #[Layout('components.layouts.app')]
    #[Title('About Coffeemeex - Yogyakarta\'s Finest Coffee')]
class extends Component {
    
    #[Computed]
    public function featuredImages()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->whereNotNull('picture')
            ->inRandomOrder()
            ->take(2)
            ->get();
    }
    
    #[Computed]
    public function teamGalleries()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->whereNotNull('picture')
            ->orderBy('index', 'desc')
            ->take(4)
            ->get();
    }
}; ?>

<div>
    <!-- Hero Section - Updated for Mobile -->
    <section class="relative overflow-hidden rounded-2xl mb-8 md:mb-12">
        @if($this->featuredImages->count() > 0)
            <div class="h-64 md:h-96 lg:h-[500px] w-full">
                <img 
                    src="{{ Storage::url($this->featuredImages->first()->picture) }}" 
                    alt="{{ $this->featuredImages->first()->name }}"
                    class="w-full h-full object-cover"
                />
            </div>
        @else
            <div class="h-64 md:h-96 lg:h-[500px] w-full bg-gradient-to-r from-amber-900 to-amber-700 flex items-center justify-center">
                <div class="text-center px-4">
                    <flux:icon.beaker class="w-16 h-16 md:w-24 md:h-24 text-amber-200 mx-auto mb-4 md:mb-6" />
                    <flux:heading size="xl" class="text-white">Our Journey</flux:heading>
                    <flux:text class="text-amber-100 mt-2">Crafting Coffee Stories Since 2018</flux:text>
                </div>
            </div>
        @endif
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent flex items-end">
            <div class="p-4 md:p-8 lg:p-12 w-full">
                <div class="max-w-4xl mx-auto">
                    <flux:heading size="xl" class="text-white mb-3 md:mb-4">The Heart Behind Every Cup</flux:heading>
                    <flux:text class="text-white/90 mb-4 md:mb-8 max-w-2xl text-sm md:text-base">
                        From a small passion project to Yogyakarta's beloved coffee destination, 
                        our journey is woven into every aromatic brew we serve.
                    </flux:text>
                    <div class="flex flex-wrap gap-2 md:gap-3">
                        <flux:button 
                            :href="route('home')" 
                            wire:navigate
                            variant="primary"
                            size="sm"
                            class="bg-amber-600 hover:bg-amber-700 text-xs md:text-sm"
                        >
                            <flux:icon.arrow-left class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Back to Home</span>
                            <span class="sm:hidden">Home</span>
                        </flux:button>
                        <flux:button 
                            :href="route('contact')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            class="text-white border-white hover:bg-white/10 text-xs md:text-sm"
                        >
                            <flux:icon.map-pin class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Visit Us</span>
                            <span class="sm:hidden">Visit</span>
                        </flux:button>
                        <!-- ADDED: Legal Page Button -->
                        <flux:button 
                            :href="route('legal')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            class="text-white border-white hover:bg-white/10 text-xs md:text-sm"
                        >
                            <flux:icon.document-text class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Terms & Privacy</span>
                            <span class="sm:hidden">Legal</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story Section -->
    <section class="mb-12 md:mb-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12 items-center">
            <div>
                @if($this->featuredImages->count() > 1)
                    <div class="rounded-lg overflow-hidden shadow-lg">
                        <img 
                            src="{{ Storage::url($this->featuredImages->skip(1)->first()->picture) }}" 
                            alt="{{ $this->featuredImages->skip(1)->first()->name }}"
                            class="w-full h-64 object-cover"
                        />
                    </div>
                @else
                    <div class="aspect-square w-full rounded-lg bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center">
                        <flux:icon.beaker class="w-32 h-32 text-amber-600" />
                    </div>
                @endif
            </div>
            
            <div>
                <flux:heading size="xl" class="mb-6">Our Origin Story</flux:heading>
                <div class="space-y-4">
                    <flux:text class="text-base">
                        Coffeemeex was born from a simple vision: to create a space where coffee becomes more than a drink—it becomes a connector. 
                        Founded by local enthusiasts in 2018, we set out to bring specialty coffee to Yogyakarta's vibrant scene.
                    </flux:text>
                    <flux:text class="text-base">
                        The name "Coffeemeex" reflects our core philosophy: where "coffee" meets "me" and "you." 
                        It's about the moments shared over a perfect cup, the conversations sparked, and the community that grows around it.
                    </flux:text>
                    <flux:text class="text-base">
                        From our humble beginnings in a renovated traditional house, we've grown while staying true to our roots—prioritizing 
                        quality ingredients, skilled craftsmanship, and genuine hospitality.
                    </flux:text>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="mb-16">
        <div class="text-center mb-12">
            <flux:heading size="xl" class="mb-4">What Drives Us Forward</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 max-w-2xl mx-auto">
                The principles that shape our daily operations and long-term vision
            </flux:text>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.heart class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Passion-Driven</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Every team member shares a genuine love for coffee culture and customer experience.
                </flux:text>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.shield-check class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Ethical Sourcing</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    We build direct relationships with farmers who practice sustainable agriculture.
                </flux:text>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.light-bulb class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Continuous Learning</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Regular training ensures our team stays updated with global coffee trends and techniques.
                </flux:text>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.hand-raised class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Local Integration</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    We actively participate in and support Yogyakarta's cultural and creative communities.
                </flux:text>
            </flux:card>
        </div>
    </section>

    <!-- Our Process Section -->
    <section class="mb-16 bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-8 md:p-12">
        <flux:heading size="xl" class="text-center mb-10">Our Artisanal Approach</flux:heading>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white text-amber-600 shadow-md">
                        <flux:icon.magnifying-glass class="w-6 h-6" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Careful Selection</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    We taste and evaluate hundreds of samples to find beans with exceptional character.
                </flux:text>
            </div>
            
            <div class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white text-amber-600 shadow-md">
                        <flux:icon.fire class="w-6 h-6" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Precision Roasting</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Small-batch roasting allows us to highlight each bean's unique flavor profile.
                </flux:text>
            </div>
            
            <div class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white text-amber-600 shadow-md">
                        <flux:icon.scale class="w-6 h-6" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Exact Measurement</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Consistent weighing ensures each brew meets our exacting standards.
                </flux:text>
            </div>
            
            <div class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white text-amber-600 shadow-md">
                        <flux:icon.presentation-chart-line class="w-6 h-6" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Quality Control</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Every batch undergoes tasting and evaluation before being served.
                </flux:text>
            </div>
        </div>
    </section>

    <!-- Team & Space Section -->
    @if($this->teamGalleries->count())
        <section class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <flux:heading size="xl">Behind the Scenes</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        The people and places that make Coffeemeex special
                    </flux:text>
                </div>
                <flux:button 
                    :href="route('gallery')" 
                    wire:navigate
                    variant="ghost"
                >
                    View Gallery
                    <flux:icon.arrow-right class="w-4 h-4 ml-2" />
                </flux:button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($this->teamGalleries as $gallery)
                    <div class="group relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
                        @if($gallery->picture)
                            <div class="aspect-square w-full">
                                <img 
                                    src="{{ Storage::url($gallery->picture) }}" 
                                    alt="{{ $gallery->name }}"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                            </div>
                        @else
                            <div class="aspect-square w-full outline flex items-center justify-center">
                                <flux:icon.photo class="w-16 h-16 text-gray-400" />
                            </div>
                        @endif
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent 
                                    opacity-0 group-hover:opacity-100 transition-opacity duration-300 
                                    flex flex-col justify-end p-6">
                            <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                <flux:heading size="xl" class="text-white mb-2">
                                    {{ $gallery->name }}
                                </flux:heading>
                                
                                @if($gallery->description)
                                    <flux:text class="text-white/90 line-clamp-2">
                                        {{ $gallery->description }}
                                    </flux:text>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- CTA Section with Legal Link -->
    <section class="bg-gradient-to-r from-amber-900 to-amber-800 rounded-2xl p-8 md:p-12 text-center">
        <flux:heading size="xl" class="text-white mb-4">Experience Our Craftsmanship</flux:heading>
        <flux:text class="text-amber-100 mb-8 max-w-2xl mx-auto">
            Join us for a coffee experience that tells a story with every sip
        </flux:text>
        
        <div class="flex flex-wrap gap-3 justify-center">
            <flux:button 
                :href="route('menu')" 
                wire:navigate
                variant="primary"
                class="bg-white text-amber-800 hover:bg-amber-50"
            >
                <flux:icon.shopping-cart class="w-5 h-5 mr-2" />
                View Our Menu
            </flux:button>
            <flux:button 
                :href="route('contact')" 
                wire:navigate
                variant="ghost"
                class="text-white border-white hover:bg-white/10"
            >
                <flux:icon.map-pin class="w-5 h-5 mr-2" />
                Find Our Location
            </flux:button>
            <!-- ADDED: Legal Page Button in CTA Section -->
            <flux:button 
                :href="route('legal')" 
                wire:navigate
                variant="ghost"
                class="text-white border-white hover:bg-white/10"
            >
                <flux:icon.shield-check class="w-5 h-5 mr-2" />
                Terms & Privacy
            </flux:button>
        </div>

        <!-- Legal Notice -->
        <div class="mt-8 pt-6 border-t border-white/20">
            <flux:text class="text-amber-100/80 text-sm">
                By using our services, you agree to our 
                <flux:link :href="route('legal')" wire:navigate class="text-white font-medium hover:underline">
                    Terms of Service and Privacy Policy
                </flux:link>.
            </flux:text>
        </div>
    </section>
</div>