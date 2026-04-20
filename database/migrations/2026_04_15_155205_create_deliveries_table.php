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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('driver_id');
            $table->enum('delivery_status', ['unassigned', 'assigned', 'accepted', 'rejected', 'picked_up', 'delivered', 'failed'])->default('unassigned');
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('proof_image_url')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('driver_profiles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
