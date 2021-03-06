<?php

namespace SanjabTicket\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use SanjabTicket\Database\Factories\SanjabTicketFactory;
use SanjabTicket\Observers\TicketObserver;

/**
 * @property int $user_id                                user who created ticket.
 * @property null|int $category_id                       category of ticket.
 * @property null|int $priority_id                       priority of ticket.
 * @property string $subject                             ticket subject.
 * @property null|\Illuminate\Support\Carbon $closed_at  ticket close datetime. if was null then ticket is still open.
 * @property-read string $status                         ticket status. Possible values: ('unread', 'unanswered', 'answered', 'closed', 'unknown')
 * @property-read string $status_localed                 ticket status but translated.
 * @property-read string $created_at_diff                created_at in diff for humans format.
 * @property-read string $updated_at_diff                updated_at in diff for humans format.
 */
class Ticket extends Model
{
    use HasFactory;

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
        'closed_at' => 'datetime',
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

    /**
     * First message inside this ticket.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function firstMessage()
    {
        return $this->hasOne(TicketMessage::class)->oldest();
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

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return new SanjabTicketFactory;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('sanjab-ticket.tables.tickets', 'sanjab_tickets');
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
            $query->whereColumn(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.user_id', '!=', $this->getTable().'.user_id')
                ->whereRaw(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.created_at IN (select MAX(created_at) from '.config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').' where '.config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.ticket_id = '.$this->getTable().'.id)');
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
            $query->whereColumn(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.user_id', '=', $this->getTable().'.user_id')
                ->whereRaw(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.created_at IN (select MAX(created_at) from '.config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').' where '.config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.ticket_id = '.$this->getTable().'.id)');
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
            $query->whereColumn(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.user_id', '=', $this->getTable().'.user_id')
                ->whereNull(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.seen_at')
                ->whereRaw(config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.created_at IN (select MAX(created_at) from '.config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').' where '.config('sanjab-ticket.tables.ticket_messages', 'sanjab_ticket_messages').'.ticket_id = '.$this->getTable().'.id)');
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
