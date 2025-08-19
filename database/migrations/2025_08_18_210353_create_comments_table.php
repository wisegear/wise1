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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commentable_id'); // ID of the blog post or article
            $table->string('commentable_type');           // Type: 'App\Models\BlogPosts' or 'App\Models\Article'
            $table->unsignedBigInteger('user_id');        // ID of the user making the comment
            $table->text('comment_text');                 // Content of the comment
            $table->timestamps();                         // Timestamps for comment created and updated times
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
