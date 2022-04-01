<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('type_id');
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email', 128)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_confirm')->default(false);
            $table->timestamp('confirm_at')->nullable();
            $table->string('email_verify_token')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
