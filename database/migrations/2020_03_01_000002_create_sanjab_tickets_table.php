<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanjabTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sanjab_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->{ config('sanjab-ticket.database.type') }('user_id');
            $table->unsignedSmallInteger('category_id')->nullable();
            $table->unsignedSmallInteger('priority_id')->nullable();
            $table->string('subject');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                    ->references(config('sanjab-ticket.database.id'))
                    ->on(app(config('sanjab-ticket.database.model'))->getTable())
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

            $table->foreign('category_id')
                    ->references('id')
                    ->on('sanjab_ticket_categories')
                    ->onDelete('set null')
                    ->onUpdate('cascade');

            $table->foreign('priority_id')
                    ->references('id')
                    ->on('sanjab_ticket_priorities')
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
        Schema::dropIfExists('sanjab_tickets');
    }
}
