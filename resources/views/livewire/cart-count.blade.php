<?php

use function Livewire\Volt\{computed};
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public $cartCount = 0;

    public function mount()
    {
        $this->refreshCartCount();
    }

    public function refreshCartCount()
    {
        if (Auth::check()) {
            $this->cartCount = (int) Auth::user()->cartItems()->sum('quantity');
        }
    }

    // Listen for cart updates from other components
    protected function getListeners()
    {
        return [
            'cartUpdated' => 'refreshCartCount',
        ];
    }
};

?>

<div>
    @if($cartCount > 0)
        <flux:badge rounded variant="solid" color="red" size="sm" class="mr-1">
            {{ min($cartCount, 99) }}
        </flux:badge>
    @endif
</div>