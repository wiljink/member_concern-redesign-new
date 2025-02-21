<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('branch')->unsigned();
            $table->string('contact_number');
            $table->text('concern');
            $table->string('message');
            $table->string('endorse_by')->nullable();  // Make 'endorse_by' nullable
            $table->string('endorse_to')->nullable();  // Make 'endorse_to' nullable
            $table->string('status')->nullable()->default('pending');      // Make 'status' nullable
            $table->json('tasks')->nullable();
            $table->timestamp('endorsed_date')->nullable();  // Add 'endorsed_date' column
            $table->timestamp('resolved_date')->nullable();  // Add 'resolved_date' column
            $table->json('resolved_days')->nullable();
            $table->string('resolve_by')->nullable();
            $table->timestamp('assess_date')->nullable();
            $table->enum('assess', ['satisfied','unsatisfied', 'unresolved'])->nullable();
            $table->string('member_comments')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
