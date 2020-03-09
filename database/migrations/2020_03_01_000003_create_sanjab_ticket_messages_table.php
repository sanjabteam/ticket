<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanjabTicketMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sanjab_ticket_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->{ config('sanjab-ticket.database.type') }('user_id');
            $table->unsignedBigInteger('ticket_id');
            $table->longText('text')->nullable();
            $table->string('file')->nullable();
            $table->{ config('sanjab-ticket.database.type') }('seen_id')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                    ->references(config('sanjab-ticket.database.id'))
                    ->on(app(config('sanjab-ticket.database.model'))->getTable())
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->foreign('ticket_id')
                    ->references('id')
                    ->on('sanjab_tickets')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->foreign('seen_id')
                    ->references(config('sanjab-ticket.database.id'))
                    ->on(app(config('sanjab-ticket.database.model'))->getTable())
                    ->onDelete('set null')
                    ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sanjab_ticket_messages');
    }
}
