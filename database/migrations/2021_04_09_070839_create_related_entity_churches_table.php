<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelatedEntityChurchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('related_entity_churches', function (Blueprint $table) {
            $table->id();
            $table->text('entity_church')->nullable();
            $table->text('type_entity')->nullable();
            $table->unsignedBigInteger('churches_id')->nullable();
            $table->timestamps();

            $table->foreign('churches_id')
            ->references('id')
            ->on('churches')
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
        Schema::dropIfExists('related_entity_churches');
    }
}
