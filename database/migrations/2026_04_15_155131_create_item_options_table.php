<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_group_id');
            $table->string('name');
            $table->decimal('extra_price', 10, 2)->default(0.00);
            $table->tinyInteger('is_available')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('option_group_id')->references('id')->on('item_option_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_options');
    }
};
