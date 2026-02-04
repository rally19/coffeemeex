<x-layouts.app :title="__('Welcome')">
    <div class="flex h-full w-full flex-1 flex-col">
        <div class="relative h-full flex flex-1 items-center justify-center overflow-hidden border border-neutral-200 dark:border-neutral-700 text-center">
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
                <h1 class="text-2xl md:text-4xl font-bold text-white">
                    Welcome to TravelID!
                </h1>
                <p class="mt-2 text-sm md:text-lg text-neutral-400">
                    Discover new possibilities and elevate your experience with us.
                </p>
                <flux:button variant="filled" size="sm" class="w-24 mt-2 md:w-32" :href="route('menu')" wire:navigate>Order Now</flux:button><flux:button variant="filled" size="sm" class="ml-4 mt-2 w-24 md:w-32" :href="route('about')" wire:navigate>About Us</flux:button>
            </div>
        </div>
        {{-- <div class="relative h-full flex-1 overflow-hidden border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div> --}}
    </div>
</x-layouts.app>
