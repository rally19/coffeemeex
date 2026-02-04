<x-layouts.app :title="__('About')">
    <div>
        <div class="relative h-[400px] flex flex-1 items-center justify-center overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 text-center">
            @php
                $photos = [
                    'https://cdn1-production-images-kly.akamaized.net/q5GNCPhXV6wmbPxs3RLsBHL-r7k=/1200x1200/smart/filters:quality(75):strip_icc():format(webp)/kly-media-production/medias/2552640/original/084804000_1545285455-20181220-Naik-Bus_-Jokowi-Uji-Coba-Tol-Trans-Jawa-Angga4.jpg',
                    'https://asset.kompas.com/crops/ZNv6oDt8qtT93jJDdJfxLrweb1I=/0x272:3264x2448/1200x800/data/photo/2023/07/05/64a5a12ab6833.jpg',
                    'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTvag5PMDocbNQpGo-a8MPF-OqCdO48JP7STw&s',
                    'https://i.ytimg.com/vi/qD0_5PzCt2I/maxresdefault.jpg',
                    'https://awsimages.detik.net.id/community/media/visual/2019/05/28/aa10eb72-f67a-4c27-8189-0637a7c2e8f9_169.jpeg?w=600&q=90',
                    'https://asset.kompas.com/crops/RFXgIhJzDSnXxtjoLnmkHFz6foU=/0x3:1280x857/1200x800/data/photo/2022/01/09/61dabcb9d2c9b.jpg',
                    'https://pict.sindonews.net/dyn/850/pena/news/2021/11/19/171/604611/catat-21-terminal-bus-di-jakarta-melayani-penumpang-akdp-dan-akap-mct.jpg',
                ];
                $randomPhoto = Arr::random($photos);
            @endphp
            <div class="absolute z-1 inset-0 bg-neutral-900">
                <img src="{{ asset($randomPhoto) }}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/50 to-black/80"></div>
            </div>
            <div class="z-1">
                <h1 class="text-2xl md:text-4xl font-bold  text-white">
                    What is TravelID?
                </h1>
                <p class="mt-2 text-sm md:text-lg  text-neutral-400">
                    Discover new possibilities and elevate your experience with us.<br>Jl. Travelid Raya No. 31, Jakarta, Indonesia
                </p>
            </div>
        </div>
        <div class="mt-5 relative flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 text-center">
            <br><flux:heading size="xl">Why us?</flux:heading>
            <div class="grid auto-rows-min p-5 gap-4 xl:grid-cols-3 sm:grid-cols-2 grid-cols-1">
                <flux:card class="items-center overflow-hiddenl">
                    <flux:text><flux:icon.shield-check />Secure Payment</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums inline-flex items-center">Very secure and ancient payment Amen</flux:heading>
                </flux:card>
                <flux:card class="items-center overflow-hiddenl">
                    <flux:text><flux:icon.eye-slash />Protected User Data</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums inline-flex items-center">We do not sell user data to third parties</flux:heading>
                </flux:card>
                <flux:card class="items-center overflow-hiddenlo">
                    <flux:text><flux:icon.bolt-slash />Slow Azz website</flux:text>
                    <flux:heading size="xl" class="mt-2 tabular-nums inline-flex items-center">Gotta be honest on this one, bro</flux:heading>
                </flux:card>
            </div>
        </div>
        <div id="payments" class="mt-5 relative flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 text-center">
            <br><flux:heading size="xl">How to pay</flux:heading>
            <div class="p-4 rounded-lg text-left">
                <div class="flex items-start gap-3">
                    <div class="dark:text-gray-200 text-gray-600">
                        <flux:icon.information-circle class="w-6 h-6" />
                    </div>
                    <div>
                        <flux:heading>Bank Transfer Instructions</flux:heading>
                        <div class="mt-2 text-sm text-gray-100">
                            <flux:text>Option 1: Bank Indonesia</flux:text>
                            <flux:text>Please transfer the exact amount to:</flux:text>
                            <flux:text>Account Number: 1234567890</flux:text>
                            <flux:text>Account Name: TravelID</flux:text>
                            <flux:text>Amount: Specified ammount</flux:text>
                            <br>
                            <flux:text>Option 2: Bank Sulawesi Timur</flux:text>
                            <flux:text>Please transfer the exact amount to:</flux:text>
                            <flux:text>Account Number: 1234567890</flux:text>
                            <flux:text>Account Name: TravelID</flux:text>
                            <flux:text>Amount: Specified ammount</flux:text>
                            <br>
                            <flux:text>Option 3: Bank Mandiri</flux:text>
                            <flux:text>Please transfer the exact amount to:</flux:text>
                            <flux:text>Account Number: 1234567890</flux:text>
                            <flux:text>Account Name: TravelID</flux:text>
                            <flux:text>Amount: Specified ammount</flux:text>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 rounded-lg text-left">
                <div class="flex items-start gap-3">
                    <div class="dark:text-gray-200 text-gray-60000">
                        <flux:icon.information-circle class="w-6 h-6" />
                    </div>
                    <div>
                        <flux:heading>Credit Card Payment Instructions</flux:heading>
                        <div class="mt-2 text-sm text-gray-100">
                            <flux:text>Option 1: Online Portal Visit: <flux:link href="#">BayarYuk!</flux:link></flux:text>
                            <br>
                            <flux:text>Option 2: Over the Phone</flux:text>
                            <flux:text>Call our customer service at: +62 812 3456 7890 to provide your card details.</flux:text>
                            <br>
                            <flux:text>Option 3: In-Person</flux:text>
                            <flux:text>Visit our ticketing office at: Jl. Travelid Raya No. 31, to use our POS terminal.</flux:text>
                            <br>
                            <flux:text>Important: After completing your credit card payment, please upload a screenshot or a PDF of your payment confirmation in the "Payment Proof" section above with specified ammount.</flux:text>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 rounded-lg text-left">
                <div class="flex items-start gap-3">
                    <div class="dark:text-gray-200 text-gray-600">
                        <flux:icon.information-circle class="w-6 h-6" />
                    </div>
                    <div> 
                        <flux:heading>E-Wallet Payment Instructions</flux:heading>
                        <div class="mt-2 text-sm text-gray-100">
                            <flux:text>To pay using E-Wallet, please follow the instructions below and complete your payment via your chosen e-wallet application:</flux:text>
                            <br>
                            <flux:text>Option 1: Scan QR Code</flux:text>
                            <div class="bg-white w-[300px] h-[300px] flex justify-center items-center">@php echo DNS2D::getBarcodeHTML('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'QRCODE'); @endphp</div><br>
                            <br>
                            <flux:text>Option 2: Transfer to E-Wallet Account</flux:text>
                            <flux:text>E-Wallet Provider:  Dana, OVO, GoPay, LinkAja</flux:text>
                            <flux:text>E-Wallet Phone Number: +62 876 5432 1098</flux:text>
                            <flux:text>Account Name: TravelID</flux:text>
                            <br>
                            <flux:text>Important: After completing your e-wallet payment, please upload a screenshot of your successful transaction in the "Payment Proof" section above with the specified ammount.</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="terms-privacy" class="p-5 mt-5 relative flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 text-center">
            <br><flux:heading size="xl">Terms Service and Privacy Policy</flux:heading><br>
            <div class="text-left">
                1. Do this and don't do that <br>
                2. And we no sell ur data <br>
                3. Do this and don't do that <br>
                4. And we no sell ur data <br>
                5. Do this and don't do that <br>
                6. And we no sell ur data <br>
                7. Do this and don't do that <br>
                8. And we no sell ur data <br>
                9. Bla bla bla, etc. <br>
                10. Pls don't take ts so srsly. <br>
                <br>
            </div>
        </div>
    </div>
</x-layouts.app>
