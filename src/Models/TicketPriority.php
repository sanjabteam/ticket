<?php

namespace SanjabTicket\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name           priority name
 * @property null|string $color     label color
 */
class TicketPriority extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sanjab_ticket_priorities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'color'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /* -------------------------------- Relations ------------------------------- */

    /**
     * Tickets with this priority.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }
}
