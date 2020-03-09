<?php

namespace SanjabTicket\Observers;

use Illuminate\Support\Facades\Auth;
use SanjabTicket\Models\Ticket;

class TicketObserver
{
    /**
     * Handle the ticket "creating" event.
     *
     * @param  \SanjabTicket\Models\Ticket  $ticket
     * @return void
     */
    public function creating(Ticket $ticket)
    {
        if (Auth::check() && empty($ticket->user_id)) {
            $ticket->user_id = Auth::id();
        }
    }
}
