<?php

namespace SanjabTicket\Observers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
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
        if (Auth::check() && empty($ticketMessage->user_id)) {
            $ticketMessage->user_id = Auth::id();
        }
        if ($ticketMessage->ticket) {
            $ticketMessage->ticket->markSeen();
            $userModel = Sanjab::userModel();
            if (config('sanjab-ticket.notifications.new_ticket.admin') && $ticketMessage->user_id == $ticketMessage->ticket->user_id) {
                $notifyClass = config('sanjab-ticket.notifications.new_ticket.admin');
                if (Session::get('sanjab_ticket_last_time_admin_'.$ticketMessage->ticket_id, Cache::get('sanjab_ticket_last_time_admin_'.$ticketMessage->ticket_id, time() - 3600)) + 60 < time()) {
                    Notification::send($userModel::whereCanModel('view', Ticket::class)->get(), new $notifyClass($ticketMessage->ticket));
                    Session::put('sanjab_ticket_last_time_admin_'.$ticketMessage->ticket_id, time());
                    Cache::put('sanjab_ticket_last_time_admin_'.$ticketMessage->ticket_id, time());
                }
            }
            if (config('sanjab-ticket.notifications.new_ticket.client') && $ticketMessage->user_id != $ticketMessage->ticket->user_id) {
                $notifyClass = config('sanjab-ticket.notifications.new_ticket.client');
                if (Session::get('sanjab_ticket_last_time_client_'.$ticketMessage->ticket_id, Cache::get('sanjab_ticket_last_time_client_'.$ticketMessage->ticket_id, time() - 3600)) + 60 < time()) {
                    $ticketMessage->ticket->user->notify(new $notifyClass($ticketMessage->ticket));
                    Session::put('sanjab_ticket_last_time_client_'.$ticketMessage->ticket_id, time());
                    Cache::put('sanjab_ticket_last_time_client_'.$ticketMessage->ticket_id, time());
                }
            }
        }
    }
}
