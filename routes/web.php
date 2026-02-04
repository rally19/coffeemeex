<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Livewire\Volt\Volt;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

Route::prefix('checkout')->group(function () {
    Volt::route('/', 'checkout')->name('checkout');
});

Route::prefix('/')->group(function () {
    Volt::route('/', 'welcome')->name('home');
    Volt::route('/about', 'about')->name('about');
    Volt::route('/contact', 'contact')->name('contact');
    Volt::route('/gallery', 'gallery')->name('gallery');
    Volt::route('/legal', 'legal')->name('legal');
});

Route::prefix('menu')->group(function () {
    Volt::route('/', 'menu')->name('menu');
    Volt::route('/{code}', 'menu-details')->name('menu.item');
});

Route::prefix('cart')->group(function () {
    Volt::route('/', 'cart')->name('cart');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('/dashboard', 'dashboard')->name('dashboard');
    Volt::route('/order/{code}', 'order')->name('order');
    Volt::route('/order/{code}/edit', 'order-edit')->name('order.edit');
});

Route::middleware(['auth', 'verified'])->get('/payment-proof/{orderCode}/{filename}', function ($orderCode, $filename) {
    $order = Order::where('code', $orderCode)
                ->where('user_id', Auth::id())
                ->firstOrFail();
    $filePath = 'payment_proofs/' . $filename;
    if (!Storage::disk('local')->exists($filePath) || 
        basename($order->payment_proof) !== $filename) {
        abort(404);
    }
    return Response::file(Storage::disk('local')->path($filePath));
})->name('payment-proof');

Route::middleware(['auth', 'verified', 'check.staff'])->get('admin/payment-proof/{orderId}/{filename}', function ($orderId, $filename) {
    $order = Order::where('id', $orderId)->firstOrFail();
    $filePath = 'payment_proofs/' . $filename;
    if (!Storage::disk('local')->exists($filePath) || 
        basename($order->payment_proof) !== $filename) {
        abort(404);
    }
    return Response::file(Storage::disk('local')->path($filePath));
})->name('admin-payment-proof');

Route::prefix('admin')->middleware(['auth', 'verified', 'check.staff'])->group(function () {
    Volt::route('/', 'admin')->name('admin');

    Volt::route('/orders', 'admin-orders')->name('admin.orders');
    Volt::route('/order/{id}', 'admin-order')->name('admin.order');

    Volt::route('/tags', 'admin-tags')->name('admin.tags');
    
    Volt::route('/users', 'admin-users')->middleware(['check.admin'])->name('admin.users');
    Volt::route('/user/edit/{id}', 'admin-user-edit')->middleware(['check.admin'])->name('admin.edit.user');
    Volt::route('/user/view/{id}', 'admin-user-view')->middleware(['check.admin'])->name('admin.view.user');

    Volt::route('/items', 'admin-items')->name('admin.items');
    Volt::route('/item/edit/{id}', 'admin-item-edit')->name('admin.edit.item');
    Volt::route('/item/view/{id}', 'admin-item-view')->name('admin.view.item');
    
    Volt::route('/galleries', 'admin-galleries')->name('admin.galleries');

    Volt::route('/contacts', 'admin-contacts')->name('admin.contacts');
    Volt::route('/contact/view/{id}', 'admin-contact-view')->name('admin.view.contact');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
