<?php

namespace SanjabTicket\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TicketMessageResource extends JsonResource
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
            'text' => $this->text,
            'file' => $this->file_link,
            'user' => ['id' => $this->user->id, 'name' => $this->user->id == Auth::id() ? trans('sanjab-ticket::sanjab-ticket.you') : $this->user->name],
            'seen_by' => $this->seenBy ? ['id' => $this->seenBy->id, 'name' => $this->seenBy->id == Auth::id() ? trans('sanjab-ticket::sanjab-ticket.you') : $this->seenBy->name] : null,
            'created_at' => $this->created_at->timestamp,
            'created_at_diff' => $this->created_at_diff,
        ];
    }
}
