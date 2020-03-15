<?php

namespace SanjabTicket\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use stdClass;
use Sanjab\Widgets\TextWidget;
use Sanjab\Helpers\SettingProperties;
use Sanjab\Controllers\SettingController;
use Sanjab\Widgets\ColorWidget;
use Sanjab\Widgets\ItemListWidget;
use SanjabTicket\Models\TicketCategory;
use SanjabTicket\Models\TicketPriority;

class TicketSettingController extends SettingController
{
    protected static function properties(): SettingProperties
    {
        return SettingProperties::create('sanjab-ticket')
                                ->title(trans('sanjab-ticket::sanjab-ticket.ticket_settings'));
    }

    protected function init(): void
    {
        $this->widgets[] = ItemListWidget::create('priorities', trans('sanjab-ticket::sanjab-ticket.priorities'))
                            ->customModifyResponse(function (stdClass $response, Model $model) {
                                $response->priorities = TicketPriority::orderBy('id', 'desc')->get();
                            })
                            ->customStore(function (Request $request, Model $model) {
                                if (is_array($request->input('priorities'))) {
                                    $priorityIds = [];
                                    foreach ($request->input('priorities') as $priority) {
                                        if (isset($priority['id'])) {
                                            TicketPriority::find($priority['id'])->update($priority);
                                            $priorityIds[] = $priority['id'];
                                        } else {
                                            $priorityIds[] = TicketPriority::create($priority)->id;
                                        }
                                    }
                                    TicketPriority::whereNotIn('id', $priorityIds)->delete();
                                }
                            })
                            ->addWidget(TextWidget::create('name', trans('sanjab-ticket::sanjab-ticket.name'))->cols(6)->required())
                            ->addWidget(ColorWidget::create('color', trans('sanjab-ticket::sanjab-ticket.color'))->cols(6)->nullable());

        $this->widgets[] = ItemListWidget::create('categories', trans('sanjab-ticket::sanjab-ticket.categories'))
                            ->customModifyResponse(function (stdClass $response, Model $model) {
                                $response->categories = TicketCategory::orderBy('id', 'desc')->get();
                            })
                            ->customStore(function (Request $request, Model $model) {
                                if (is_array($request->input('categories'))) {
                                    $categoryIds = [];
                                    foreach ($request->input('categories') as $category) {
                                        if (isset($category['id'])) {
                                            TicketCategory::find($category['id'])->update($category);
                                            $categoryIds[] = $category['id'];
                                        } else {
                                            $categoryIds[] = TicketCategory::create($category)->id;
                                        }
                                    }
                                    TicketCategory::whereNotIn('id', $categoryIds)->delete();
                                }
                            })
                            ->addWidget(TextWidget::create('name', trans('sanjab-ticket::sanjab-ticket.name'))->cols(6)->required())
                            ->addWidget(ColorWidget::create('color', trans('sanjab-ticket::sanjab-ticket.color'))->cols(6)->nullable());
    }
}
