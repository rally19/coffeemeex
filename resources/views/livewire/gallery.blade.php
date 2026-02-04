<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\Volt\Component;
use App\Models\Gallery;

new #[Layout('components.layouts.app')]
    #[Title('Gallery')]
class extends Component {
    
    #[Computed]
    public function galleries()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->orderBy('index', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-5">Welcome to our Gallery!</flux:heading>
    @if($this->galleries->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->galleries as $gallery)
        <div class="group relative overflow-hidden rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
            @if($gallery->picture)
                <div class="aspect-[4/3] w-full">
                    <img 
                        src="{{ Storage::url($gallery->picture) }}" 
                        alt="{{ $gallery->name }}"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                </div>
            @else
                <div class="aspect-[4/3] w-full outline flex items-center justify-center">
                    <flux:icon.photo class="w-16 h-16 text-gray-400" />
                </div>
            @endif
            
            <!-- Hover overlay with title and description -->
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
            
            <!-- Always visible mini overlay - hidden on hover -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4
                        opacity-100 group-hover:opacity-0 transition-opacity duration-300">
                <flux:heading size="lg" class="text-white">
                    {{ $gallery->name }}
                </flux:heading>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <div class="text-gray-400 mb-4">
            <flux:icon.photo class="w-12 h-12 mx-auto" />
        </div>
        <flux:heading size="xl" class="mb-4">No Galleries Available</flux:heading>
        <p class="text-neutral-600 dark:text-neutral-400">
            There are no galleries to display at the moment.
        </p>
    </div>
    @endif
</div>