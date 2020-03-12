<?php

namespace SanjabTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use SanjabTicket\Observers\TicketMessageObserver;

/**
 * @property int $ticket_id                             ticket of this message.
 * @property int $user_id                               user who sent this message.
 * @property string $text                               message text.
 * @property null|string $file                          file path.
 * @property null|int $seen_id                          who seen this message.
 * @property null|\Illuminate\Support\Carbon $seen_at   when this message seen.
 * @property-read string $created_at_diff               created_at in diff for humans format.
 * @property-read string $updated_at_diff               updated_at in diff for humans format.
 * @property-read null|string $file_link                full url of file.
 */
class TicketMessage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sanjab_ticket_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'text',
        'file',
        'seen_at',
        'seen_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'seen_at' => 'datetime',
    ];

    /**
     * The relationships that should be touched on save.
     *
     * @var array
     */
    protected $touches = ['ticket'];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['user', 'seenBy'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_diff', 'updated_at_diff', 'file_link'];

    /* -------------------------------- Relations ------------------------------- */

    /**
     * Ticket of this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * User that sent this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('sanjab-ticket.database.model'), 'user_id');
    }

    /**
     * User that seen this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function seenBy()
    {
        return $this->belongsTo(config('sanjab-ticket.database.model'), 'seen_id');
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

        static::observe(TicketMessageObserver::class);
    }

    /* -------------------------------- Mutators -------------------------------- */

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

    /**
     * File link attribute.
     *
     * @return null|string
     */
    public function getFileLinkAttribute()
    {
        if ($this->file) {
            return Storage::disk(config('sanjab-ticket.files.disk'))->url($this->file);
        }
        return null;
    }
}
