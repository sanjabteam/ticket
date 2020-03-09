<?php

namespace SanjabTicket\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use SanjabTicket\Observers\TicketObserver;

class Ticket extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sanjab_tickets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'priority_id',
        'subject',
        'closed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'closed_at' => 'timestamp',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['user', 'category', 'priority', 'lastMessage'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_diff', 'updated_at_diff'];

    /* -------------------------------- Relations ------------------------------- */

    /**
     * Category of ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Priority of ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    /**
     * User that created ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('sanjab-ticket.database.model'), 'user_id');
    }

    /**
     * Messages inside ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    /**
     * Last message inside this ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lastMessage()
    {
        return $this->hasOne(TicketMessage::class)->latest();
    }

    /* -------------------------------- Functions ------------------------------- */

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::observe(TicketObserver::class);
    }

    /**
     * Mark messages seen.
     *
     * @return void
     */
    public function markSeen()
    {
        if (Auth::check()) {
            if (Auth::id() == $this->user_id) {
                $this->messages()->where('user_id', '!=', Auth::id())->whereNull('seen_id')->update(['seen_at' => now(), 'seen_id' => Auth::id()]);
            } else {
                $this->messages()->where('user_id', '=', $this->user_id)->whereNull('seen_id')->update(['seen_at' => now(), 'seen_id' => Auth::id()]);
            }
            $this->unsetRelation('messages');
        }
    }

    /**
     * Close ticket.
     *
     * @return void
     */
    public function close()
    {
        $this->closed_at = now();
        $this->save();
    }

    /* --------------------------------- Scopes --------------------------------- */

    /**
     * Query answered tickets.
     *
     * @param Builder $query
     * @return void
     */
    public function scopeAnswered(Builder $query)
    {
        $query->whereNull('closed_at')->whereHas('lastMessage', function ($query) {
            $query->whereColumn('sanjab_ticket_messages.user_id', '!=', 'sanjab_tickets.user_id')
                ->whereRaw('sanjab_ticket_messages.created_at IN (select MAX(created_at) from sanjab_ticket_messages where sanjab_ticket_messages.ticket_id = sanjab_tickets.id)');
        });
    }

    /**
     * Query tickets that has unanswered messages.
     *
     * @param Builder $query
     * @return void
     */
    public function scopeUnanswered(Builder $query)
    {
        $query->whereNull('closed_at')->whereHas('lastMessage', function ($query) {
            $query->whereColumn('sanjab_ticket_messages.user_id', '=', 'sanjab_tickets.user_id')
                ->whereRaw('sanjab_ticket_messages.created_at IN (select MAX(created_at) from sanjab_ticket_messages where sanjab_ticket_messages.ticket_id = sanjab_tickets.id)');
        });
    }

    /**
     * Query tickets that has unread message.
     *
     * @param Builder $query
     * @return void
     */
    public function scopeUnread(Builder $query)
    {
        $query->whereNull('closed_at')->whereHas('lastMessage', function ($query) {
            $query->whereColumn('sanjab_ticket_messages.user_id', '=', 'sanjab_tickets.user_id')
                ->whereNull('sanjab_ticket_messages.seen_at')
                ->whereRaw('sanjab_ticket_messages.created_at IN (select MAX(created_at) from sanjab_ticket_messages where sanjab_ticket_messages.ticket_id = sanjab_tickets.id)');
        });
    }

    /**
     * Query tickets that open.
     *
     * @param Builder $query
     * @return void
     */
    public function scopeOpen(Builder $query)
    {
        $query->whereNull('closed_at');
    }

    /**
     * Query tickets that closed.
     *
     * @param Builder $query
     * @return void
     */
    public function scopeClosed(Builder $query)
    {
        $query->whereNotNull('closed_at');
    }

    /* -------------------------------- Mutators -------------------------------- */

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->closed_at == null) {
            if ($this->lastMessage) {
                if ($this->lastMessage->user_id == $this->user_id) {
                    if ($this->lastMessage->seen_at == null) {
                        return 'unread';
                    } else {
                        return 'unanswered';
                    }
                } else {
                    return "answered";
                }
            }
            return "unknown";
        }
        return "closed";
    }

    /**
     * Get status localed.
     *
     * @return string
     */
    public function getStatusLocaledAttribute()
    {
        return trans('sanjab-ticket::sanjab-ticket.'. $this->status);
    }

    /**
     * Variant for bootstrap style.
     *
     * @return string
     */
    public function getVariantAttribute()
    {
        return $this->status == 'answered' ? 'success' : ($this->status == 'closed' ? 'info' : 'danger');
    }

    /**
     * Created at in diffrence format.
     *
     * @return string
     */
    public function getCreatedAtDiffAttribute()
    {
        return ($this->created_at->diff()->d > 0 ? $this->created_at->locale(config('app.locale'), config('app.fallback_locale'), 'en')->diffForHumans().' - ' :'').$this->created_at->format('H:i');
    }

    /**
     * Updated at in diffrence format.
     *
     * @return string
     */
    public function getUpdatedAtDiffAttribute()
    {
        return ($this->updated_at->diff()->d > 0 ? $this->updated_at->locale(config('app.locale'), config('app.fallback_locale'), 'en')->diffForHumans().' - ' :'').$this->updated_at->format('H:i');
    }
}
