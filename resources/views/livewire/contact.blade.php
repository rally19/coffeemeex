<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\{Contact, Gallery};
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Contact')]
class extends Component {
    use WithFileUploads;
    
    public string $name = '';
    public string $email = '';
    public string $phone_numbers = '';
    public string $address = '';
    public string $subject = '';
    public string $message = '';
    
    #[Computed]
    public function featuredImage()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->whereNotNull('picture')
            ->inRandomOrder()
            ->first();
    }
    
    public function mount(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone_numbers = $user->phone_numbers ?? '';
            $this->address = $user->address ?? '';
        }
    }
    
    public function submitContact(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_numbers' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        
        $contactData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone_numbers' => $this->phone_numbers,
            'address' => $this->address,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
        
        if (Auth::check()) {
            $contactData['user_id'] = Auth::id();
        }
        
        Contact::create($contactData);
        
        Flux::toast(
            variant: 'success',
            heading: 'Message Sent!',
            text: 'Thank you for contacting us. We will respond as soon as possible.',
            duration: 5000
        );
        
        $this->resetExcept(['name', 'email', 'phone_numbers', 'address']);
    }
    
    #[Computed]
    public function isLoggedIn()
    {
        return Auth::check();
    }
}; ?>

<div>
    <!-- Hero Section with Random Gallery Image -->
    <section class="relative overflow-hidden rounded-2xl mb-8 md:mb-12">
        @if($this->featuredImage)
            <div class="h-64 md:h-96 lg:h-[500px] w-full">
                <img 
                    src="{{ Storage::url($this->featuredImage->picture) }}" 
                    alt="{{ $this->featuredImage->name }}"
                    class="w-full h-full object-cover"
                />
            </div>
        @else
            <div class="h-64 md:h-96 lg:h-[500px] w-full bg-gradient-to-r from-amber-900 to-amber-700 flex items-center justify-center">
                <div class="text-center px-4">
                    <flux:icon.envelope class="w-16 h-16 md:w-24 md:h-24 text-amber-200 mx-auto mb-4 md:mb-6" />
                    <flux:heading size="xl" class="text-white">Get in Touch</flux:heading>
                    <flux:text class="text-amber-100 mt-2">We'd love to hear from you</flux:text>
                </div>
            </div>
        @endif
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent flex items-end">
            <div class="p-4 md:p-8 lg:p-12 w-full">
                <div class="max-w-4xl mx-auto">
                    <flux:heading size="xl" class="text-white mb-3 md:mb-4">Connect With Coffeemeex</flux:heading>
                    <flux:text class="text-white/90 mb-4 md:mb-8 max-w-2xl text-sm md:text-base">
                        Have questions, feedback, or just want to say hello? We're here to help and connect with our coffee community.
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
                            :href="route('about')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            class="text-white border-white hover:bg-white/10 text-xs md:text-sm"
                        >
                            <flux:icon.information-circle class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Our Story</span>
                            <span class="sm:hidden">Story</span>
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

    <!-- Contact Information Section -->
    <section class="mb-12 md:mb-16">
        <div class="text-center mb-8 md:mb-12">
            <flux:heading size="xl" class="mb-4">Contact Information</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 max-w-2xl mx-auto px-4">
                Reach us through any of these channels
            </flux:text>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 mb-8 md:mb-12">
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.phone class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Call Us</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 mb-4">
                    Available during business hours
                </flux:text>
                <div class="space-y-2">
                    <a href="tel:+622745678901" class="text-primary-600 hover:text-primary-800 font-medium block">
                        (0274) 567-8901
                    </a>
                    <a href="tel:+628112345678" class="text-primary-600 hover:text-primary-800 font-medium block">
                        +62 811-2345-678
                    </a>
                </div>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.envelope class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Email Us</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 mb-4">
                    We'll respond within 24 hours
                </flux:text>
                <div class="space-y-2">
                    <a href="mailto:hello@coffeemeex.id" class="text-primary-600 hover:text-primary-800 font-medium block">
                        hello@coffeemeex.id
                    </a>
                    <a href="mailto:reservation@coffeemeex.id" class="text-primary-600 hover:text-primary-800 font-medium block">
                        reservation@coffeemeex.id
                    </a>
                </div>
            </flux:card>
            
            <flux:card class="text-center">
                <div class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600">
                        <flux:icon.map-pin class="w-8 h-8" />
                    </div>
                </div>
                <flux:heading size="lg" class="mb-3">Visit Us</flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400 mb-4">
                    Come experience our space
                </flux:text>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    Jl. Coffee Street No. 123<br>
                    Yogyakarta 55281<br>
                    Indonesia
                </flux:text>
            </flux:card>
        </div>

        <!-- Google Maps Section -->
        <div class="mb-12 md:mb-16">
            <flux:heading size="lg" class="mb-4">Our Location</flux:heading>
            <div class="rounded-xl overflow-hidden shadow-lg h-64 md:h-96">
                <!-- Google Maps Embed - Updated to Yogyakarta central location -->
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3953.122584607211!2d110.3629356!3d-7.792384299999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a582b49ccb88f%3A0x318d8e7d22e0a554!2sMalioboro%2C%20Yogyakarta%20City%2C%20Special%20Region%20of%20Yogyakarta!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    class="w-full h-full"
                ></iframe>
            </div>
        </div>

        <!-- Social Media Section -->
        <div class="mb-12 md:mb-16">
            <flux:heading size="lg" class="mb-6">Connect on Social Media</flux:heading>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="https://instagram.com/coffeemeex" target="_blank" rel="noopener noreferrer">
                    <flux:button variant="ghost" class="w-full h-full p-4">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.user-circle class="w-8 h-8 text-pink-600" />
                            <flux:text class="font-medium">Instagram</flux:text>
                            <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">@coffeemeex</flux:text>
                        </div>
                    </flux:button>
                </a>
                
                <a href="https://facebook.com/coffeemeex" target="_blank" rel="noopener noreferrer">
                    <flux:button variant="ghost" class="w-full h-full p-4">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.user-circle class="w-8 h-8 text-blue-600" />
                            <flux:text class="font-medium">Facebook</flux:text>
                            <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">Coffeemeex ID</flux:text>
                        </div>
                    </flux:button>
                </a>
                
                <a href="https://twitter.com/coffeemeex" target="_blank" rel="noopener noreferrer">
                    <flux:button variant="ghost" class="w-full h-full p-4">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.user-circle class="w-8 h-8 text-blue-400" />
                            <flux:text class="font-medium">Twitter</flux:text>
                            <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">@coffeemeex_id</flux:text>
                        </div>
                    </flux:button>
                </a>
                
                <a href="https://tiktok.com/@coffeemeex" target="_blank" rel="noopener noreferrer">
                    <flux:button variant="ghost" class="w-full h-full p-4">
                        <div class="flex flex-col items-center gap-2">
                            <flux:icon.user-circle class="w-8 h-8 text-black dark:text-white" />
                            <flux:text class="font-medium">TikTok</flux:text>
                            <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">@coffeemeex</flux:text>
                        </div>
                    </flux:button>
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="mb-16">
        <div class="text-center mb-8 md:mb-12">
            <flux:heading size="xl" class="mb-4">Send Us a Message</flux:heading>
            <flux:text class="text-neutral-600 dark:text-neutral-400 max-w-2xl mx-auto px-4">
                Fill out the form below and we'll get back to you as soon as possible
            </flux:text>
            @if($this->isLoggedIn)
                <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-success-50 text-success-700 rounded-full">
                    <flux:icon.check-circle class="w-5 h-5" />
                    <flux:text class="text-sm">Your profile information has been auto-filled</flux:text>
                </div>
            @endif
        </div>
        
        <div class="max-w-3xl mx-auto">
            <form wire:submit.prevent="submitContact" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="name" 
                        :label="__('Full Name')" 
                        type="text" 
                        required 
                        :placeholder="__('Your full name')" 
                        :disabled="$this->isLoggedIn"
                    />
                    
                    <flux:input 
                        wire:model="email" 
                        :label="__('Email Address')" 
                        type="email" 
                        required 
                        :placeholder="__('email@example.com')" 
                        :disabled="$this->isLoggedIn"
                    />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="phone_numbers" 
                        :label="__('Phone Number')" 
                        type="tel" 
                        required 
                        :placeholder="__('Your phone number')" 
                        :disabled="$this->isLoggedIn"
                    />
                    
                    <flux:input 
                        wire:model="subject" 
                        :label="__('Subject')" 
                        type="text" 
                        required 
                        :placeholder="__('What is this regarding?')" 
                    />
                </div>
                
                <flux:textarea 
                    wire:model="address" 
                    :label="__('Address')" 
                    required 
                    :placeholder="__('Your complete address')" 
                    rows="2"
                    :disabled="$this->isLoggedIn"
                />
                
                <flux:textarea 
                    wire:model="message" 
                    :label="__('Message')" 
                    required 
                    :placeholder="__('Please provide details about your inquiry...')" 
                    rows="5"
                />
                
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                    @if(!$this->isLoggedIn)
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                            Already have an account? 
                            <flux:link :href="route('login')" wire:navigate class="text-primary-600">
                                Sign in
                            </flux:link> 
                        </flux:text>
                    @else
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                            Need to update your profile? 
                            <flux:link :href="route('settings.profile')" wire:navigate class="text-primary-600">
                                Edit profile
                            </flux:link>
                        </flux:text>
                    @endif
                    
                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                        <span wire:loading.remove wire:target="submitContact">
                            Send Message
                        </span>
                        <span wire:loading wire:target="submitContact">
                            <flux:icon.loading class="w-5 h-5" />
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    </section>

    <!-- Business Hours -->
    <section class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-8 md:p-12 mb-12 md:mb-16">
        <flux:heading size="xl" class="text-center mb-8">Business Hours</flux:heading>
        
        <div class="max-w-md mx-auto">
            <div class="space-y-4">
                <div class="flex justify-between items-center py-3 border-b">
                    <flux:text class="font-medium">Monday - Friday</flux:text>
                    <flux:text>7:00 AM - 10:00 PM</flux:text>
                </div>
                <div class="flex justify-between items-center py-3 border-b">
                    <flux:text class="font-medium">Saturday</flux:text>
                    <flux:text>8:00 AM - 11:00 PM</flux:text>
                </div>
                <div class="flex justify-between items-center py-3 border-b">
                    <flux:text class="font-medium">Sunday</flux:text>
                    <flux:text>8:00 AM - 10:00 PM</flux:text>
                </div>
                <div class="flex justify-between items-center py-3">
                    <flux:text class="font-medium">Holidays</flux:text>
                    <flux:text>9:00 AM - 9:00 PM</flux:text>
                </div>
            </div>
            
            <div class="mt-8 p-4 bg-white dark:bg-neutral-800 rounded-lg">
                <div class="flex items-start gap-3">
                    <flux:icon.information-circle class="w-6 h-6 text-amber-600 mt-0.5" />
                    <div>
                        <flux:text class="font-medium mb-1">Need Immediate Assistance?</flux:text>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                            For urgent matters outside business hours, please call our emergency line: 
                            <a href="tel:+628112345678" class="text-primary-600 hover:text-primary-800 font-medium">+62 811-2345-678</a>
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>