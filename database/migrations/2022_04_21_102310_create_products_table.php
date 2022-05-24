<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("name", 200)->unique();
            $table->integer("price");
            $table->integer("quantity")->default(1);
            $table->boolean("isActive")->default(true);
            $table->string("image", 255)->nullable();
            $table->string("type", 10);
            $table->string("unit", 100);
            $table->smallInteger("sale")->default(0);
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
        Schema::dropIfExists('products');
    }
}
