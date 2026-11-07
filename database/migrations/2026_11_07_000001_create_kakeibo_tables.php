<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One page of the book per month: the plan made at its start and the reflection at its end.
        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('income', 14, 2)->default(0);
            $table->decimal('fixed_costs', 14, 2)->default(0);
            $table->decimal('savings_goal', 14, 2)->default(0);
            $table->text('intention')->nullable();     // what I want to do with my money this month
            $table->text('reflection')->nullable();    // how did the month go?
            $table->text('improvement')->nullable();   // what will I do better?
            $table->timestamps();

            $table->unique(['user_id', 'year', 'month']);
        });

        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('spent_on')->index();
            $table->string('category', 20)->index();
            $table->string('description', 160);
            $table->decimal('amount', 14, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'spent_on']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('months');
    }
};
