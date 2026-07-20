<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction,
            'type' => $this->type,
            'status' => $this->status,
            'content' => $this->content,
            'meta_message_id' => $this->meta_message_id,
            'error' => $this->error,
            'contact' => [
                'id' => $this->contact_id,
                'phone' => $this->whenLoaded('contact', fn () => $this->contact->phone),
                'name' => $this->whenLoaded('contact', fn () => $this->contact->name),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
