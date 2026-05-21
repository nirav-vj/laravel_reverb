<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public int $senderId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, int $senderId)
    {
        $this->message  = $message;
        $this->senderId = $senderId;
    }

    /**
     * Broadcast on the private channel of the OTHER user in the conversation.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->senderId),
        ];
    }

    /**
     * The data to send with the event.
     */
    public function broadcastWith(): array
    {
        // Load fresh reactions with user info
        $this->message->load('reactions.user');

        return [
            'message_id' => $this->message->id,
            'reactions'  => $this->message->reactions->map(fn($r) => [
                'id'        => $r->id,
                'user_id'   => $r->user_id,
                'user_name' => $r->user->name ?? 'Unknown',
                'reaction'  => $r->reaction,
            ])->values()->all(),
        ];
    }
}
