<?php

namespace App\Events;

use App\Models\MenuOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MenuOrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MenuOrder $order)
    {
        $this->order->loadMissing('items');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('restaurant.'.$this->order->restaurant_id);
    }

    public function broadcastAs(): string
    {
        return 'menu-order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'customer_name' => $this->order->customer_name,
            'table_number' => $this->order->table_number,
            'total' => number_format((float) $this->order->total, 2),
            'currency' => $this->order->currency,
            'items_count' => $this->order->items->sum('quantity'),
            'created_at' => $this->order->created_at?->format('Y/m/d h:i A'),
            'orders_url' => route('dashboard.orders.index'),
        ];
    }
}
