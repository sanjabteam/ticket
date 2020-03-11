@extends('sanjab::master')

@section('title', trans('sanjab-ticket::sanjab-ticket.ticket').' '.$ticket->user->name)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div id="sanjab_app">
                <material-card title="@lang('sanjab-ticket::sanjab-ticket.ticket'): {{ $ticket->subject }}">
                    <b-card>
                        <b-row>
                            <b-col :sm="12" :md="6">
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.user')</b>: <span>{{ $ticket->user->name }}</span></p>
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.subject')</b>: <span>{{ $ticket->subject }}</span></p>
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.priority')</b>: <span @if(optional($ticket->priority)->color) style="color: {{ $ticket->priority->color }}" @endif>{{ $ticket->priority->name ?? trans('sanjab-ticket::sanjab-ticket.unknown') }}</span></p>
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.category')</b>: <span @if(optional($ticket->category)->color) style="color: {{ $ticket->category->color }}" @endif>{{ $ticket->category->name ?? trans('sanjab-ticket::sanjab-ticket.unknown') }}</span></p>
                            </b-col>
                            <b-col :sm="12" :md="6">
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.status')</b>: <span class="text-{{ $ticket->variant }}">{{ $ticket->status_localed }}</span></p>
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.created_at')</b>: <span>{{ $ticket->created_at_diff }}</span></p>
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.updated_at')</b>: <span>{{ $ticket->updated_at_diff }}</span></p>
                                <p><b>@lang('sanjab-ticket::sanjab-ticket.responsers')</b>:
                                    @foreach ($ticket->messages->where('user_id', '!=', $ticket->user_id)->unique('user_id') as $message)
                                        <span>{{ $message->user->name }} @if(! $loop->last), @endif</span>
                                    @endforeach
                                </p>
                            </b-col>
                            @if(count(config('sanjab-ticket.database.fields')) > 0)
                                @foreach(array_chunk(config('sanjab-ticket.database.fields'), max(count(config('sanjab-ticket.database.fields'))/2, 1), true) as $chunkedFields)
                                    <b-col :sm="12" :md="6">
                                        @foreach($chunkedFields as $field => $fieldTitle)
                                            <p><b>{{ $fieldTitle }}</b>: <span>{{ $ticket->user->{ $field } }}</span></p>
                                        @endforeach
                                    </b-col>
                                @endforeach
                            @endif
                        </b-row>
                    </b-card>
                    <ticket-messages :ticket='@json(\SanjabTicket\Resources\TicketResource::make($ticket))' />
                </material-card>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <script>
        $(document).ready(function () {
            window.scrollTo(0, document.body.scrollHeight);
        });
    </script>
@endsection
