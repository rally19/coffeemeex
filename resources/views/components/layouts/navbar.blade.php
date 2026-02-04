<flux:navbar>
    <flux:navbar.item href="#" icon="home" :current="request()->is('/')">Home</flux:navbar.item>
    <flux:navbar.item href="#" icon="puzzle-piece">Features</flux:navbar.item>
    <flux:navbar.item href="#" icon="currency-dollar">Pricing</flux:navbar.item>
    @if (Route::has('login'))
        @auth
        <flux:navbar.item href="{{ url('/dashboard') }}" icon="user">Dashboard</flux:navbar.item>
            @else
            <flux:navbar.item href="{{ route('login') }}" icon="user">Log in</flux:navbar.item>
            @if (Route::has('register'))
            <flux:navbar.item href="{{ route('register') }}" icon="user">Register</flux:navbar.item>
            @endif
        @endauth
    @endif
</flux:navbar>
