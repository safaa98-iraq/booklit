<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reading position, completion and study time per student per topic.
        Schema::create('topic_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booklet_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('scroll_percent')->default(0);
            $table->unsignedInteger('reading_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'topic_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable'); // booklet | topic
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
        });

        // A student's own highlights inside a block (code-point offsets, like passage anchors).
        Schema::create('user_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_block_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('start_offset');
            $table->unsignedInteger('end_offset');
            $table->timestamps();

            $table->index(['user_id', 'content_block_id']);
        });

        // Quizzes started from the reader are scoped to one topic.
        Schema::table('attempts', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('booklet_id')->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('preferences')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('preferences'));
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topic_id');
        });
        Schema::dropIfExists('user_highlights');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('topic_progress');
    }
};
