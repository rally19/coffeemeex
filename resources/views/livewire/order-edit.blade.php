<?php
use Livewire\Attributes\{Layout, Title, Computed};
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use App\Models\{Order, OrderItem};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Flux\Flux;

new #[Layout('components.layouts.app')]
    #[Title('Edit Order')]
class extends Component {
    use WithFileUploads;
    
    public Order $order;
    public array $items = [];
    public string $paymentMethod = '';
    public $paymentProof;
    public ?string $existingPaymentProof = null;
    
    public function mount($code): void
    {
        $this->order = Order::with(['items', 'user'])
            ->where('code', $code)
            ->where('user_id', Auth::id())
            ->where('order_status', 'pending')
            ->firstOrFail();
            
        $this->items = $this->order->items->map(function($item) {
            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'item_price' => $item->item_price,
                'quantity' => $item->quantity,
                'cost' => $item->cost,
            ];
        })->toArray();
        
        $this->paymentMethod = $this->order->payment_method;
        $this->existingPaymentProof = $this->order->payment_proof;
    }

    #[Computed]
    public function total()
    {
        return collect($this->items)->sum(fn($item) => $item['quantity'] * $item['item_price']);
    }
    
    public function updateOrder(): void
    {
        if ($this->isDisabled()) {
            Flux::toast(variant: 'error', heading: 'Cannot Update', text: 'Order is locked.');
            return;
        }
        
        $this->validate([
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'paymentMethod' => ['required', 'in:bank_transfer,credit_card,e_wallet,cash'],
            'paymentProof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        
        $totalCost = 0;
        foreach ($this->items as $itemData) {
            $item = OrderItem::find($itemData['id']);
            if ($item) {
                $cost = $itemData['quantity'] * $itemData['item_price'];
                $item->update([
                    'quantity' => $itemData['quantity'],
                    'cost' => $cost,
                ]);
                $totalCost += $cost;
            }
        }
        
        $paymentProofPath = $this->existingPaymentProof;
        if ($this->paymentProof) {
            if ($this->existingPaymentProof) {
                Storage::disk('local')->delete($this->existingPaymentProof);
            }
            $paymentProofPath = $this->paymentProof->store('payment_proofs', 'local');
        }
        
        $this->order->update([
            'payment_method' => $this->paymentMethod,
            'payment_proof' => $paymentProofPath,
            'total_cost' => $totalCost,
        ]);
        
        Flux::toast(variant: 'success', heading: 'Order Updated');
    }
    
    public function removePaymentProof(): void
    {
        if ($this->isDisabled()) return;
        
        if ($this->existingPaymentProof) {
            Storage::disk('local')->delete($this->existingPaymentProof);
            $this->order->update(['payment_proof' => null]);
            $this->existingPaymentProof = null;
        }
        $this->paymentProof = null;
        Flux::toast(variant: 'success', heading: 'Proof Removed');
    }
    
    public function isDisabled(): bool
    {
        return in_array($this->order->payment_status, ['paid', 'refunded']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="xl">Edit Order #{{ $order->code }}</flux:heading>
            @if($this->isDisabled())
                <flux:badge variant="solid" :color="$order->payment_status === 'paid' ? 'lime' : 'red'" class="mt-2">
                    Payment {{ ucfirst($order->payment_status) }}
                </flux:badge>
            @endif
        </div>
        <flux:button :href="route('dashboard')" wire:navigate>Back</flux:button>
    </div>
    
    @if($order->comments_public)
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-start gap-3">
            <flux:icon.information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div>
                <flux:heading size="sm" class="text-blue-800 dark:text-blue-300">Admin Message</flux:heading>
                <flux:text class="text-blue-700 dark:text-blue-200 mt-1">{{ $order->comments_public }}</flux:text>
            </div>
        </div>
    </div>
    @endif
    
    <form wire:submit.prevent="updateOrder" class="space-y-6">
        <div class="space-y-4">
            <flux:heading size="lg">Order Items</flux:heading>
            @foreach($items as $index => $item)
            <div class="p-4 outline rounded-lg" wire:key="item-{{ $item['id'] }}">
                <flux:heading size="md" class="mb-2">Item {{ $index + 1 }}</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <flux:input label="Item Code" value="{{ $item['item_code'] }}" disabled />
                    <flux:input label="Item Name" value="{{ $item['item_name'] }}" disabled />
                    <flux:input label="Price" value="{{ $item['item_price'] }}" disabled />
                    <flux:input 
                        label="Quantity"
                        type="number" 
                        wire:model.live="items.{{ $index }}.quantity" 
                        min="1" 
                        required
                        :disabled="$this->isDisabled()"
                    />
                </div>
                <div class="mt-4">
                    <flux:text class="font-semibold">
                        Subtotal: {{ format_rupiah($item['quantity'] * $item['item_price']) }}
                    </flux:text>
                </div>
            </div>
            @endforeach
            
            <div class="p-4 outline rounded-lg bg-primary-50 dark:bg-primary-900/20">
                <div class="flex justify-between items-center">
                    <flux:heading size="md">Order Total</flux:heading>
                    <flux:heading size="xl">{{ format_rupiah($this->total) }}</flux:heading>
                </div>
            </div>
        </div>
        
        <div class="space-y-4">
            <flux:heading size="lg">Payment Information</flux:heading>
            
            @if($existingPaymentProof)
            <div class="mb-4">
                <flux:label>Payment Proof</flux:label>
                <div class="flex items-center gap-4 mt-2">
                    <a href="{{ route('payment-proof', ['orderId' => $order->id, 'filename' => basename($order->payment_proof)]) }}" target="_blank" class="flex items-center gap-2 text-primary-600 hover:underline">
                        <flux:icon.document-text class="w-6 h-6" />
                        {{ basename($order->payment_proof) }}
                    </a>
                    @if(!$this->isDisabled())
                        <flux:button variant="danger" size="sm" wire:click="removePaymentProof">Remove</flux:button>
                    @endif
                </div>
            </div>
            @elseif(!$this->isDisabled())
                <flux:input type="file" wire:model="paymentProof" label="Upload Proof" accept="image/*,.pdf" />
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:select label="Payment Method" wire:model.live="paymentMethod" :disabled="$this->isDisabled()">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="e_wallet">E-Wallet</option>
                        <option value="cash">Cash</option>
                    </flux:select>
                </div>
                <flux:input label="Status" value="{{ ucfirst($order->payment_status) }}" disabled />
            </div>

            @if(in_array($paymentMethod, ['bank_transfer', 'credit_card', 'e_wallet']) && !$this->isDisabled())
                <div class="p-4 bg-gray-800 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="text-gray-200">
                            <flux:icon.information-circle class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="font-medium text-gray-200">Payment Instructions</div>
                            <div class="mt-2 text-sm text-gray-100">
                                @if($paymentMethod === 'bank_transfer')
                                    Please transfer the exact amount to:<br>
                                    Bank: Bank Indonesia<br>
                                    Account Number: 1234567890<br>
                                    Account Name: Your Store Name<br>
                                    Amount: {{ format_rupiah($this->total) }}<br>
                                    <br>
                                    After payment, please upload your payment proof above.
                                @elseif($paymentMethod === 'credit_card')
                                    Credit card payments are processed through our secure payment gateway.<br>
                                    You will be redirected to the payment page after order confirmation.<br>
                                    <br>
                                    For manual payment, upload your payment confirmation above.
                                @elseif($paymentMethod === 'e_wallet')
                                    Scan the QR code or transfer to:<br>
                                    E-Wallet Provider: Dana/OVO/GoPay<br>
                                    Phone Number: +62 876 5432 1098<br>
                                    Amount: {{ format_rupiah($this->total) }}<br>
                                    <br>
                                    Upload your payment confirmation screenshot above.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @if($paymentMethod === 'cash' && !$this->isDisabled())
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="text-green-600">
                            <flux:icon.check-circle class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="font-medium text-green-800">Cash</div>
                            <div class="mt-1 text-sm text-green-700">
                                No online payment required.
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        
        @if(!$this->isDisabled())
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Save Changes</flux:button>
            </div>
        @endif
    </form>
</div>