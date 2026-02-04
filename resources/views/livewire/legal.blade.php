<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\Volt\Component;
use App\Models\Gallery;

new #[Layout('components.layouts.app')]
    #[Title('Legal - Terms & Privacy | Coffeemeex')]
class extends Component {
    
    #[Computed]
    public function featuredImage()
    {
        return Gallery::query()
            ->where('status', 'show')
            ->whereNotNull('picture')
            ->inRandomOrder()
            ->first();
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
            <div class="h-64 md:h-96 lg:h-[500px] w-full bg-gradient-to-r from-amber-900 to-amber-800 flex items-center justify-center">
                <div class="text-center px-4">
                    <flux:icon.shield-check class="w-16 h-16 md:w-24 md:h-24 text-amber-200 mx-auto mb-4 md:mb-6" />
                    <flux:heading size="xl" class="text-white">Legal Information</flux:heading>
                    <flux:text class="text-amber-100 mt-2">Terms of Service & Privacy Policy</flux:text>
                </div>
            </div>
        @endif
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent flex items-end">
            <div class="p-4 md:p-8 lg:p-12 w-full">
                <div class="max-w-4xl mx-auto">
                    <flux:heading size="xl" class="text-white mb-3 md:mb-4">Our Legal Commitment</flux:heading>
                    <flux:text class="text-white/90 mb-4 md:mb-8 max-w-2xl text-sm md:text-base">
                        Transparent terms and privacy practices that protect both you and Coffeemeex
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
                            <flux:icon.envelope class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" />
                            <span class="hidden sm:inline">Contact Us</span>
                            <span class="sm:hidden">Contact</span>
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
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto">

        <!-- Introduction -->
        <div class="mb-10 text-center">
            <flux:heading size="xl" class="mb-6">Legal Information</flux:heading>
            <flux:text class="text-base mb-4 max-w-3xl mx-auto">
                This page contains our Terms of Service and Privacy Policy. Please read these documents carefully 
                as they govern your use of Coffeemeex's services and explain how we handle your personal information.
            </flux:text>
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-800 rounded-full">
                <flux:icon.information-circle class="w-5 h-5" />
                <flux:text class="text-sm font-medium text-neutral-800">Last Updated: January 2024</flux:text>
            </div>
        </div>

        <!-- Table of Contents -->
        <div class="mb-12 bg-amber-50 dark:bg-amber-900/20 rounded-xl p-6 md:p-8">
            <flux:heading size="lg" class="mb-4">Table of Contents</flux:heading>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:text class="font-medium mb-3 text-amber-700 dark:text-amber-300">Terms of Service</flux:text>
                    <ul class="space-y-2">
                        <li><a href="#account-terms" class="text-primary-600 hover:text-primary-800 text-sm">1. Account Registration</a></li>
                        <li><a href="#ordering-terms" class="text-primary-600 hover:text-primary-800 text-sm">2. Orders and Payments</a></li>
                        <li><a href="#user-conduct" class="text-primary-600 hover:text-primary-800 text-sm">3. User Conduct</a></li>
                        <li><a href="#intellectual-property" class="text-primary-600 hover:text-primary-800 text-sm">4. Intellectual Property</a></li>
                        <li><a href="#liability" class="text-primary-600 hover:text-primary-800 text-sm">5. Limitation of Liability</a></li>
                    </ul>
                </div>
                <div>
                    <flux:text class="font-medium mb-3 text-amber-700 dark:text-amber-300">Privacy Policy</flux:text>
                    <ul class="space-y-2">
                        <li><a href="#information-collection" class="text-primary-600 hover:text-primary-800 text-sm">1. Information We Collect</a></li>
                        <li><a href="#information-use" class="text-primary-600 hover:text-primary-800 text-sm">2. How We Use Your Information</a></li>
                        <li><a href="#information-sharing" class="text-primary-600 hover:text-primary-800 text-sm">3. Information Sharing</a></li>
                        <li><a href="#data-security" class="text-primary-600 hover:text-primary-800 text-sm">4. Data Security</a></li>
                        <li><a href="#your-rights" class="text-primary-600 hover:text-primary-800 text-sm">5. Your Privacy Rights</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Terms of Service Section -->
        <section class="mb-16">
            <div class="flex items-center gap-4 mb-8">
                <div class="flex-shrink-0">
                    <flux:icon.document-text class="w-10 h-10 text-amber-600" />
                </div>
                <div>
                    <flux:heading size="xl" id="terms-of-service">Terms of Service</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        These terms govern your use of Coffeemeex services
                    </flux:text>
                </div>
            </div>

            <div class="space-y-10">
                <!-- Account Terms -->
                <div id="account-terms">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">1. Account Registration</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            <strong>1.1 Eligibility:</strong> You must be at least 13 years old to use our Services. 
                            By creating an account, you represent that you meet this age requirement.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>1.2 Account Security:</strong> You are responsible for maintaining the 
                            confidentiality of your account credentials and for all activities that occur under your account.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>1.3 Accurate Information:</strong> You agree to provide accurate, current, 
                            and complete information during registration and to update such information to keep it accurate.
                        </flux:text>
                    </div>
                </div>

                <!-- Ordering Terms -->
                <div id="ordering-terms">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">2. Orders and Payments</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            <strong>2.1 Order Acceptance:</strong> All orders placed through our Services are subject 
                            to acceptance by Coffeemeex. We reserve the right to refuse or cancel any order for any reason.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>2.2 Pricing:</strong> Prices are displayed in Indonesian Rupiah (IDR) and are subject 
                            to change without notice. All prices include applicable taxes unless stated otherwise.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>2.3 Payment Methods:</strong> We accept various payment methods including bank transfer, 
                            credit card, e-wallet, and cash. Payment must be completed before order processing begins.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>2.4 Order Modifications:</strong> You may modify or cancel pending orders through 
                            your account dashboard. Once an order is being processed, modifications may not be possible.
                        </flux:text>
                    </div>
                </div>

                <!-- User Conduct -->
                <div id="user-conduct">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">3. User Conduct</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            You agree not to:
                        </flux:text>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><flux:text class="text-base">Use the Services for any illegal purpose or in violation of any laws</flux:text></li>
                            <li><flux:text class="text-base">Harass, abuse, or harm another person</flux:text></li>
                            <li><flux:text class="text-base">Use automated systems to access the Services</flux:text></li>
                            <li><flux:text class="text-base">Interfere with the proper functioning of the Services</flux:text></li>
                            <li><flux:text class="text-base">Attempt to gain unauthorized access to any part of the Services</flux:text></li>
                        </ul>
                    </div>
                </div>

                <!-- Intellectual Property -->
                <div id="intellectual-property">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">4. Intellectual Property</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            <strong>4.1 Our Content:</strong> All content on the Services, including text, graphics, 
                            logos, images, and software, is the property of Coffeemeex or its licensors and is protected 
                            by copyright and other intellectual property laws.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>4.2 License:</strong> We grant you a limited, non-exclusive, non-transferable 
                            license to access and use the Services for personal, non-commercial purposes.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>4.3 User Content:</strong> By submitting any content to the Services, you grant 
                            Coffeemeex a worldwide, royalty-free license to use, reproduce, and display such content.
                        </flux:text>
                    </div>
                </div>

                <!-- Limitation of Liability -->
                <div id="liability">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">5. Limitation of Liability</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            To the maximum extent permitted by law, Coffeemeex shall not be liable for any indirect, 
                            incidental, special, consequential, or punitive damages, or any loss of profits or revenues.
                        </flux:text>
                        <flux:text class="text-base">
                            Our total liability for any claims under these Terms shall not exceed the amount you paid 
                            to Coffeemeex in the 12 months preceding the claim.
                        </flux:text>
                    </div>
                </div>

                <!-- Contact for Terms -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 mt-8">
                    <flux:heading size="md" class="mb-3">Terms of Service Contact</flux:heading>
                    <flux:text class="text-base mb-2">
                        If you have any questions about our Terms of Service, please contact us:
                    </flux:text>
                    <flux:text class="font-medium">Coffeemeex</flux:text>
                    <flux:text class="text-base">Jl. Coffee Street No. 123, Yogyakarta 55281, Indonesia</flux:text>
                    <flux:text class="text-base mt-1">Email: legal@coffeemeex.id</flux:text>
                    <flux:text class="text-base mt-1">Phone: (0274) 567-8901</flux:text>
                </div>
            </div>
        </section>

        <!-- Privacy Policy Section -->
        <section class="mb-16">
            <div class="flex items-center gap-4 mb-8">
                <div class="flex-shrink-0">
                    <flux:icon.shield-check class="w-10 h-10 text-amber-600" />
                </div>
                <div>
                    <flux:heading size="xl" id="privacy-policy">Privacy Policy</flux:heading>
                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                        How we collect, use, and protect your personal information
                    </flux:text>
                </div>
            </div>

            <div class="space-y-10">
                <!-- Information Collection -->
                <div id="information-collection">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">1. Information We Collect</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            <strong>1.1 Personal Information:</strong> We collect personal information that you voluntarily 
                            provide to us when you register for an account, place an order, or contact us.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>1.2 Usage Information:</strong> We automatically collect certain information when 
                            you visit our website, such as device information, browser type, and pages visited.
                        </flux:text>
                        <flux:text class="text-base">
                            <strong>1.3 Order Information:</strong> We collect information related to your purchases, 
                            including items ordered, order history, and preferences.
                        </flux:text>
                    </div>
                </div>

                <!-- Use of Information -->
                <div id="information-use">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">2. How We Use Your Information</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            We use the information we collect for various purposes, including:
                        </flux:text>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><flux:text class="text-base">To process and fulfill your orders</flux:text></li>
                            <li><flux:text class="text-base">To manage your account and provide customer support</flux:text></li>
                            <li><flux:text class="text-base">To send you order confirmations and updates</flux:text></li>
                            <li><flux:text class="text-base">To improve our website and services</flux:text></li>
                            <li><flux:text class="text-base">To send promotional communications (with your consent)</flux:text></li>
                            <li><flux:text class="text-base">To comply with legal obligations</flux:text></li>
                        </ul>
                    </div>
                </div>

                <!-- Information Sharing -->
                <div id="information-sharing">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">3. Information Sharing and Disclosure</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            We do not sell your personal information. We may share your information with:
                        </flux:text>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><flux:text class="text-base">Service Providers (payment processors, delivery services)</flux:text></li>
                            <li><flux:text class="text-base">When required by law or to respond to legal process</flux:text></li>
                            <li><flux:text class="text-base">In connection with a merger, acquisition, or sale of assets</flux:text></li>
                            <li><flux:text class="text-base">With your explicit permission</flux:text></li>
                        </ul>
                    </div>
                </div>

                <!-- Data Security -->
                <div id="data-security">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">4. Data Security</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            We implement appropriate technical and organizational security measures designed to protect 
                            your personal information. However, no electronic transmission over the Internet or 
                            information storage technology can be guaranteed to be 100% secure.
                        </flux:text>
                        <flux:text class="text-base">
                            We retain your personal information only for as long as necessary to fulfill the purposes 
                            outlined in this Privacy Policy, unless a longer retention period is required or 
                            permitted by law.
                        </flux:text>
                    </div>
                </div>

                <!-- Your Rights -->
                <div id="your-rights">
                    <flux:heading size="lg" class="mb-4 pb-2 border-b">5. Your Privacy Rights</flux:heading>
                    <div class="space-y-3">
                        <flux:text class="text-base">
                            Depending on your location, you may have the following rights regarding your personal information:
                        </flux:text>
                        <ul class="list-disc pl-6 space-y-2">
                            <li><flux:text class="text-base">Access and receive a copy of your personal information</flux:text></li>
                            <li><flux:text class="text-base">Correct inaccurate or incomplete information</flux:text></li>
                            <li><flux:text class="text-base">Request deletion of your personal information</flux:text></li>
                            <li><flux:text class="text-base">Object to processing of your personal information</flux:text></li>
                            <li><flux:text class="text-base">Request restriction of processing</flux:text></li>
                            <li><flux:text class="text-base">Data portability</flux:text></li>
                            <li><flux:text class="text-base">Withdraw consent at any time</flux:text></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact for Privacy -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 mt-8">
                    <flux:heading size="md" class="mb-3">Privacy Contact</flux:heading>
                    <flux:text class="text-base mb-2">
                        If you have any questions or concerns about this Privacy Policy or our privacy practices, 
                        please contact our Data Protection Officer:
                    </flux:text>
                    <flux:text class="font-medium">Data Protection Officer - Coffeemeex</flux:text>
                    <flux:text class="text-base">Jl. Coffee Street No. 123, Yogyakarta 55281, Indonesia</flux:text>
                    <flux:text class="text-base mt-1">Email: privacy@coffeemeex.id</flux:text>
                    <flux:text class="text-base mt-1">Phone: (0274) 567-8901</flux:text>
                </div>
            </div>
        </section>

        <!-- Combined Footer -->
        <div class="mt-12 pt-8 border-t">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <flux:heading size="md" class="mb-3">Document Information</flux:text>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        Terms of Service and Privacy Policy combined document.<br>
                        Last Updated: January 1, 2024
                    </flux:text>
                </div>
                <div>
                    <flux:heading size="md" class="mb-3">Quick Links</flux:heading>
                    <div class="flex flex-wrap gap-3">
                        <flux:button 
                            :href="route('contact')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                        >
                            Contact Support
                        </flux:button>
                        <flux:button 
                            :href="route('home')" 
                            wire:navigate
                            variant="ghost"
                            size="sm"
                        >
                            Return to Homepage
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <div class="fixed bottom-6 right-6 z-10">
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
                class="bg-amber-600 text-white p-3 rounded-full shadow-lg hover:bg-amber-700 transition-colors">
            <flux:icon.arrow-up class="w-5 h-5" />
        </button>
    </div>
</div>