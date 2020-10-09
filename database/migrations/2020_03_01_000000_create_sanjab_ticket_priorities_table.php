<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanjabTicketPrioritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('sanjab-ticket.tables.ticket_priorities', 'sanjab_ticket_priorities'), function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('color')->nullable();
            $table->string('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('sanjab-ticket.tables.ticket_priorities', 'sanjab_ticket_priorities'));
    }
}
