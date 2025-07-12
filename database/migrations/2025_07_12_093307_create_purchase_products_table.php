<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_products', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('purchase_id');
            $table->unsignedInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->float('purchase_time_unit_price');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_products');
    }
};
