<?php

namespace SanjabTicket\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SanjabTicket\Models\Ticket;
use SanjabTicket\Models\TicketCategory;
use SanjabTicket\Models\TicketPriority;

class SanjabTicketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $userModel = config('sanjab-ticket.database.model');
        return [
            'user_id'       => $userModel::inRandomOrder()->first()->{ config('sanjab-ticket.database.id') },
            'category_id'   => optional(TicketCategory::inRandomOrder()->first())->id,
            'priority_id'   => optional(TicketPriority::inRandomOrder()->first())->id,
            'subject'       => $this->faker->word,
            'closed_at'     => $this->faker->numberBetween(0, 5) == 0 ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'created_at'    => $this->faker->dateTimeBetween('-30 days', '-5 hours')
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Ticket $ticket) {
            $userModel = config('sanjab-ticket.database.model');
            $randomNumber = $this->faker->numberBetween(0, 10);
            $createdAt = $ticket->created_at;
            $randomUserId = $userModel::inRandomOrder()->first()->{ config('sanjab-ticket.database.id') };
            foreach (range(0, $randomNumber) as $range) {
                $seen = $range < $randomNumber;
                $message = $ticket->messages()->create([
                    'user_id' => $range % 2 == 1 ? $randomUserId : $ticket->user_id,
                    'text' => $this->faker->text,
                    'file' => null,
                    'seen_at' => $seen ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
                    'seen_id' => $seen ? ($range % 2 == 0 ? $randomUserId : $ticket->user_id) : null,
                ]);
                $message->created_at = $createdAt->addMinutes($range * 5);
                $message->save();
            }
        });
    }
}
