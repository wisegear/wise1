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
        // Create categories table
        Schema::create('blog_categories', function(Blueprint $table)
        {
            $table->id('id');
            $table->string('name', 150);
            
        });

        // Create Blog Tables
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id('id');
            $table->text('original_image')->nullable();
            $table->text('small_image')->nullable();
            $table->text('medium_image')->nullable();
            $table->text('large_image')->nullable();
            $table->date('date')->nullable();
            $table->string('title', 150);
            $table->string('slug', 200);
            $table->text('excerpt');
            $table->boolean('featured')->default(false);
            $table->boolean('published')->default(true);
            $table->text('body');
            $table->json('images')->nullable();
            $table->Biginteger('user_id')->unsigned();
            $table->Biginteger('categories_id')->unsigned();     
            $table->timestamps();  

            // Create foreign keys

            $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');
            
            $table->foreign('categories_id')
            ->references('id')
            ->on('blog_categories')
            ->onDelete('cascade');
        
        });


        // Create blog tags table
        Schema::create('blog_tags', function(Blueprint $table)
        {
            $table->id('id');
            $table->string('name', 50);

        });            

        // Create pivot table for blog tags
        Schema::create('blog_post_tags', function(Blueprint $table)
        {
            $table->id('id');
            $table->Biginteger('tag_id')->unsigned();
            $table->Biginteger('post_id')->unsigned();
            
            // Create foreign keys
            $table->foreign('tag_id')
            ->references('id')
            ->on('blog_tags')
            ->onDelete('cascade');
            
            $table->foreign('post_id')
            ->references('id')
            ->on('blog_posts')
            ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('blog_categories');
        Schema::drop('blog_posts');
        Schema::drop('blog_tags');
        Schema::drop('post_tags');
    }
};
