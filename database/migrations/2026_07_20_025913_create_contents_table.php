<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('title');
            $table->string('ustadz');
            $table->text('description');

            $table->enum('video_source', ['upload', 'youtube']);
            $table->string('video_url', 2048);

            $table->string('file_name')->nullable();
            $table->float('file_size_mb')->nullable();

            $table->string('thumbnail', 2048)->nullable();

            $table->string('duration_label');

            $table->enum('status', ['draft', 'published'])
                ->default('draft');

            $table->unsignedBigInteger('views')->default(0);

            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
 