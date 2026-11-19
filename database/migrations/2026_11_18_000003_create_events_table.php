<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('title', 160);
            $table->text('description');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('venue_id')->constrained('venues');
            $table->foreignId('category_id')->constrained('categories');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->text('payment_note')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->foreignId('manager_id')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['venue_id', 'starts_at', 'ends_at']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
