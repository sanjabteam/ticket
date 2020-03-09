<?php

namespace SanjabTicket\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'user' => ['id' => $this->user->id, 'name' => $this->user->name],
            'created_at' => $this->created_at->timestamp,
            'created_at_diff' => $this->created_at_diff,
            'messages' => TicketMessageResource::collection($this->messages),
        ];
    }
}
