<?php

namespace SanjabTicket\Controllers;

use stdClass;
use Exception;
use Carbon\Carbon;
use Sanjab\Helpers\Action;
use Sanjab\Cards\StatsCard;
use Sanjab\Widgets\IdWidget;
use Illuminate\Http\Request;
use Sanjab\Widgets\ShowWidget;
use Sanjab\Widgets\TextWidget;
use SanjabTicket\Models\Ticket;
use Sanjab\Helpers\FilterOption;
use Sanjab\Helpers\MaterialIcons;
use Sanjab\Helpers\CrudProperties;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Sanjab\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use SanjabTicket\Resources\TicketMessageResource;
use Sanjab\Widgets\Relation\BelongsToPickerWidget;
use SanjabTicket\Models\TicketMessage;

class TicketController extends CrudController
{
    protected static function properties(): CrudProperties
    {
        return CrudProperties::create('tickets')
                ->model(Ticket::class)
                ->title(trans('sanjab-ticket::sanjab-ticket.ticket'))
                ->titles(trans('sanjab-ticket::sanjab-ticket.tickets'))
                ->icon(MaterialIcons::MESSAGE)
                ->badge(function () {
                    return Ticket::unanswered()->count();
                })
                ->creatable(false)
                ->editable(false)
                ->deletable(false)
                ->defaultOrder('closed_at')
                ->defaultOrderDirection('asc')
                ->autoRefresh(10)
                ->autoRefreshNotification(true);
    }

    protected function init(string $type, Model $item = null): void
    {
        $this->widgets[] = IdWidget::create();

        $this->widgets[] = BelongsToPickerWidget::create('user', trans('sanjab-ticket::sanjab-ticket.user'))
                            ->format(config('sanjab-ticket.database.format'))
                            ->ajax(true);

        $this->widgets[] = TextWidget::create('subject', trans('sanjab-ticket::sanjab-ticket.subject'));

        $this->widgets[] = BelongsToPickerWidget::create('category', trans('sanjab-ticket::sanjab-ticket.category'))
                            ->format('%name')
                            ->indexTag('ticket-info-view')
                            ->customModifyResponse(function (stdClass $response, Model $item = null) {
                                $response->category = [
                                    'text' => optional($item->category)->name,
                                    'color' => optional($item->category)->color
                                ];
                            });

        $this->widgets[] = BelongsToPickerWidget::create('priority', trans('sanjab-ticket::sanjab-ticket.priority'))
                            ->format('%name')
                            ->indexTag('ticket-info-view')
                            ->customModifyResponse(function (stdClass $response, Model $item = null) {
                                $response->priority = [
                                    'text' => optional($item->priority)->name,
                                    'color' => optional($item->priority)->color
                                ];
                            });

        $this->widgets[] = ShowWidget::create('status', trans('sanjab-ticket::sanjab-ticket.status'))
                            ->indexTag('ticket-info-view')
                            ->customModifyResponse(function (stdClass $response, Model $item = null) {
                                $response->status = [
                                    'text' => $item->status_localed,
                                    'variant' => $item->variant
                                ];
                            });

        $this->widgets[] = TextWidget::create('updated_at', trans('sanjab-ticket::sanjab-ticket.updated_at'))
                            ->customModifyResponse(function (stdClass $response, Model $item = null) {
                                $response->updated_at = $item->updated_at_diff;
                            })
                            ->searchable(false);

        $this->filters[] = FilterOption::create(trans('sanjab-ticket::sanjab-ticket.unanswered'))
                                ->query(function ($query) {
                                    $query->unanswered();
                                });

        $this->filters[] = FilterOption::create(trans('sanjab-ticket::sanjab-ticket.unread'))
                                ->query(function ($query) {
                                    $query->unread();
                                });

        $this->filters[] = FilterOption::create(trans('sanjab-ticket::sanjab-ticket.answered'))
                                ->query(function ($query) {
                                    $query->answered();
                                });

        $this->filters[] = FilterOption::create(trans('sanjab-ticket::sanjab-ticket.open'))
                                ->query(function ($query) {
                                    $query->open();
                                });

        $this->filters[] = FilterOption::create(trans('sanjab-ticket::sanjab-ticket.closed'))
                                ->query(function ($query) {
                                    $query->closed();
                                });

        $this->cards[] = StatsCard::create(trans('sanjab-ticket::sanjab-ticket.unanswered_tickets'))
                            ->value(Ticket::unanswered()->count())
                            ->link(route('sanjab.modules.'.static::property('route').'.index'))
                            ->variant('warning')
                            ->icon(static::property('icon'));

        $this->cards[] = StatsCard::create(trans('sanjab-ticket::sanjab-ticket.unread_tickets'))
                            ->value(Ticket::unread()->count())
                            ->link(route('sanjab.modules.'.static::property('route').'.index'))
                            ->variant('warning')
                            ->icon(static::property('icon'));

        $this->cards[] = StatsCard::create(trans('sanjab-ticket::sanjab-ticket.closed'))
                            ->value(Ticket::closed()->count())
                            ->link(route('sanjab.modules.'.static::property('route').'.index'))
                            ->variant('success')
                            ->icon(static::property('icon'));

        $this->actions[] = Action::create(trans('sanjab-ticket::sanjab-ticket.close'))
                                ->action('close')
                                ->icon(MaterialIcons::CLOSE)
                                ->variant('danger')
                                ->confirm(trans('sanjab::sanjab.are_you_sure'))
                                ->perItem(true);
    }

    public function show(Request $request, $id)
    {
        $ticket = Ticket::where('id', $id)->firstOrFail();
        $this->authorize('view', $ticket);
        $this->initCrud("show", $ticket);
        if ($request->has('last_created_at') && is_numeric($request->input('last_created_at'))) {
            try {
                set_time_limit(600);
                ini_set('max_execution_time', 600);
            } catch (Exception $e) {
            }
            Session::save();
            $response = response()->stream(function () use ($request, $ticket) {
                echo "data: []\n\n";
                ob_flush();
                flush();
                $lastCreatedAt = $request->input('last_created_at');
                $unseenMessagesQuery = $ticket->messages()->select('id')->where('user_id', '!=', $ticket->user_id)->whereNull('seen_id');
                $unseenMessages = $unseenMessagesQuery->get()->pluck('id')->toArray();
                $lastMessageTime = time();
                while (true) {
                    $ticket->markSeen();
                    $messages = $ticket->messages()->where('created_at', '>', Carbon::createFromTimestamp($lastCreatedAt))->get();

                    // Mark previous messages as seen.
                    if ($ticket->messages()->whereNotNull('seen_id')->whereIn('id', $unseenMessages)->exists()) {
                        $unseenMessages = [];
                        echo "data: seen\n\n";
                        ob_flush();
                        flush();
                        $lastMessageTime = time();
                    }

                    // Show new messages.
                    if ($messages->count() > 0) {
                        $unseenMessages = $unseenMessagesQuery->get()->pluck('id')->toArray();
                        $lastCreatedAt = $messages->max('created_at')->timestamp;
                        echo "data: ".json_encode(TicketMessageResource::collection($messages)->toArray($request))."\n\n";
                        ob_flush();
                        flush();
                        $lastMessageTime = time();
                    }

                    // Prevent Maximum execution time of N seconds exceeded error.
                    if ((microtime(true) - LARAVEL_START) + 3 >= intval(ini_get('max_execution_time'))) {
                        echo "data: close\n\n";
                        ob_flush();
                        flush();
                        return;
                    }

                    // Prevent keep alive timeout
                    if (time() - $lastMessageTime >= 10) {
                        echo "data: []\n\n";
                        ob_flush();
                        flush();
                        $lastMessageTime = time();
                    }

                    usleep(800000);
                }
            });
            $response->headers->set('Content-Type', 'text/event-stream');
            $response->headers->set('X-Accel-Buffering', 'no');
            $response->headers->set('Cach-Control', 'no-cache');
            $response->headers->set('Connection', 'keep-alive');
            return $response;
        }
        $ticket->markSeen();
        if ($request->wantsJson()) {
            $item = $this->itemResponse($ticket);
            return $item;
        }
        return view('sanjab-ticket::ticket', get_defined_vars());
    }

    /**
     * Close ticket.
     *
     * @return array
     */
    public function close(Ticket $ticket)
    {
        $ticket->close();
        return ['success' => true];
    }

    /**
     * Should play notification sound or not.
     *
     * @param Request $request
     * @param LengthAwarePaginator $items
     * @return bool|string
     */
    protected function notification(Request $request, LengthAwarePaginator $items)
    {
        $lastCreatedAt = Session::get('sanjab_notification_last_message_created_at');
        $result = config('sanjab-ticket.notifications.new_ticket.admin') == null && $request->input('autoRefreshing') == true && $lastCreatedAt && TicketMessage::where('created_at', '>', Carbon::createFromTimestamp($lastCreatedAt))->exists();
        Session::put('sanjab_notification_last_message_created_at', optional(optional(TicketMessage::latest()->first())->created_at)->timestamp);
        return $result ? trans("sanjab-ticket::sanjab-ticket.new_ticket") : false;
    }

    /**
     * Send new message to ticket.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return mixed
     */
    public function send(Request $request, Ticket $ticket)
    {
        $request->validate(['text' => 'required|string', 'file' => 'nullable|array']);
        if ($ticket->closed_at) {
            $ticket->closed_at = null;
            $ticket->save();
        }
        $file = null;
        if ($request->filled('file.value')) {
            $fileInfo = preg_replace('/.*\/helpers\/uppy\/upload\//', '', $request->input('file.value'));
            if (is_array(Session::get("sanjab_uppy_files.".$fileInfo)) && File::exists(Session::get("sanjab_uppy_files.".$fileInfo)['file_path'])) {
                $fileInfo = Session::get("sanjab_uppy_files.".$fileInfo);
                $file = trim(trim(config('sanjab-ticket.files.directory'), '\\/') . "/" . sha1(time().'_'.uniqid()).'.'.File::extension($fileInfo['file_path']), '\\/');
                $file = str_replace('{TICKET_ID}', $ticket->id, $file);
                Storage::disk(config('sanjab-ticket.files.disk'))->put($file, File::get($fileInfo['file_path']));
            }
        }
        $ticket->messages()->create(['text' => $request->input('text'), 'file' => $file]);
        return ['success' => true];
    }

    public static function dashboardCards(): array
    {
        if (static::property('defaultDashboardCards') && Auth::user()->can('viewAny'.static::property('permissionsKey'), static::property('model'))) {
            return [
                StatsCard::create(trans('sanjab-ticket::sanjab-ticket.unanswered_tickets'))
                            ->value(Ticket::unanswered()->count())
                            ->link(route('sanjab.modules.'.static::property('route').'.index'))
                            ->variant('warning')
                            ->icon(static::property('icon')),
                StatsCard::create(trans('sanjab-ticket::sanjab-ticket.unread_tickets'))
                            ->value(Ticket::unread()->count())
                            ->link(route('sanjab.modules.'.static::property('route').'.index'))
                            ->variant('warning')
                            ->icon(static::property('icon')),
            ];
        }
        return [];
    }

    public static function routes(): void
    {
        parent::routes();
        Route::prefix("modules")->name("modules.")->group(function () {
            Route::post(static::property('route').'/{ticket}/send', static::class.'@send')->name(static::property('route').'.send');
        });
    }
}
