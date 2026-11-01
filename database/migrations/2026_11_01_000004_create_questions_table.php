<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            // Every question belongs to a subject (and through it a stage) even when
            // it is not linked to a booklet — that is the standalone question bank.
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('link_type', 20)->default('none');
            $table->foreignId('booklet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->string('source_reference')->nullable(); // e.g. "وزاري 2023 - الدور الأول"
            $table->string('difficulty', 10)->default('medium');
            $table->text('text');
            $table->string('image')->nullable();
            $table->text('explanation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booklet_id', 'source', 'is_active']);
            $table->index(['booklet_id', 'difficulty', 'is_active']);
            $table->index(['subject_id', 'link_type', 'source']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->string('image')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('passage_anchors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_block_id')->constrained()->cascadeOnDelete();
            // Character offsets (Unicode code points) into content_blocks.plain_text, end exclusive.
            $table->unsignedInteger('start_offset');
            $table->unsignedInteger('end_offset');
            // Snapshot of the highlighted text at the time of anchoring; lets us detect drift after edits.
            $table->text('quoted_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passage_anchors');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
