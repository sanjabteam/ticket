<?php

namespace SanjabTicket;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SanjabTicket\Skeleton\SkeletonClass
 */
class SanjabTicketFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sanjab-ticket';
    }
}
