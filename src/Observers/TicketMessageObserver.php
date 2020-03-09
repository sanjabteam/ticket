<?php

namespace SanjabTicket\Observers;

use Illuminate\Support\Facades\Auth;
use SanjabTicket\Models\TicketMessage;

class TicketMessageObserver
{
    /**
     * Handle the ticket "creating" event.
     *
     * @param  \SanjabTicket\Models\TicketMessage  $ticket
     * @return void
     */
    public function creating(TicketMessage $ticket)
    {
        if (Auth::check() && empty($ticket->user_id)) {
            $ticket->user_id = Auth::id();
        }
    }
}
