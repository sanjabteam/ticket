<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use SanjabTicket\Models\Ticket;
use SanjabTicket\Models\TicketCategory;
use SanjabTicket\Models\TicketPriority;

$factory->define(Ticket::class, function (Faker $faker) {
    $userModel = config('sanjab-ticket.database.model');
    return [
        'user_id'       => $userModel::inRandomOrder()->first()->{ config('sanjab-ticket.database.id') },
        'category_id'   => optional(TicketCategory::inRandomOrder()->first())->id,
        'priority_id'   => optional(TicketPriority::inRandomOrder()->first())->id,
        'subject'       => $faker->word,
        'closed_at'     => $faker->numberBetween(0, 5) == 0 ? $faker->dateTimeBetween('-30 days', 'now') : null,
        'created_at'    => $faker->dateTimeBetween('-30 days', '-5 hours')
    ];
});

$factory->afterCreating(Ticket::class, function (Ticket $ticket, Faker $faker) {
    $userModel = config('sanjab-ticket.database.model');
    $randomNumber = $faker->numberBetween(0, 10);
    $createdAt = $ticket->created_at;
    $randomUserId = $userModel::inRandomOrder()->first()->{ config('sanjab-ticket.database.id') };
    foreach (range(0, $randomNumber) as $range) {
        $seen = $range < $randomNumber;
        $message = $ticket->messages()->create([
            'user_id' => $range % 2 == 1 ? $randomUserId : $ticket->user_id,
            'text' => $faker->text,
            'file' => null,
            'seen_at' => $seen ? $faker->dateTimeBetween('-30 days', 'now') : null,
            'seen_id' => $seen ? ($range % 2 == 0 ? $randomUserId : $ticket->user_id) : null,
        ]);
        $message->created_at = $createdAt->addMinutes($range * 5);
        $message->save();
    }
});
