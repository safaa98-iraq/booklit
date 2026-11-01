<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booklets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            // Number of questions drawn for "Quiz Mode" — set by the editor.
            $table->unsignedSmallInteger('quiz_question_count')->default(10);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_id', 'is_published', 'sort_order']);
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booklet_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['booklet_id', 'sort_order']);
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            // Stable public identifier. Survives re-ordering and edits so that
            // passage anchors keep pointing at the same passage.
            $table->uuid('uuid')->unique();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->json('data');
            // Denormalised plain text of the block: anchor offsets index into this string.
            $table->longText('plain_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['topic_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('booklets');
    }
};
