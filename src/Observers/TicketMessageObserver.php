<?php

namespace SanjabTicket\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Sanjab\Sanjab;
use SanjabTicket\Models\Ticket;
use SanjabTicket\Models\TicketMessage;

class TicketMessageObserver
{
    /**
     * Handle the ticket "creating" event.
     *
     * @param  \SanjabTicket\Models\TicketMessage  $ticket
     * @return void
     */
    public function creating(TicketMessage $ticketMessage)
    {
        $ticketMessage->ticket->markSeen();
        if (Auth::check() && empty($ticketMessage->user_id)) {
            $ticketMessage->user_id = Auth::id();
        }
        $userModel = Sanjab::userModel();
        if (config('sanjab-ticket.notifications.new_ticket.admin') && $ticketMessage->user_id == $ticketMessage->ticket->user_id) {
            $notifyClass = config('sanjab-ticket.notifications.new_ticket.admin');
            Notification::send($userModel::whereCanModel('view', Ticket::class)->get(), new $notifyClass($ticketMessage->ticket));
        }
        if (config('sanjab-ticket.notifications.new_ticket.client') && $ticketMessage->user_id != $ticketMessage->ticket->user_id) {
            $notifyClass = config('sanjab-ticket.notifications.new_ticket.client');
            Notification::send($userModel::whereCanModel('view', Ticket::class)->get(), new $notifyClass($ticketMessage->ticket));
        }
    }
}
