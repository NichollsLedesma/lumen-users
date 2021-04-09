<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_configs', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id")->unsigned();
            $table->enum('factor_authentication', ['email', 'phone'])->nullable();
            $table->string("code_auth", 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_configs');
    }
}
