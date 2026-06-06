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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();

            // Stable identifier mapping the row to a controller action / route name.
            $table->string('key')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);

            // Core SEO meta.
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);

            // Social / Open Graph + Twitter cards.
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_type')->default('website');
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();

            // Structured data + per-page custom scripts.
            $table->text('json_ld')->nullable();
            $table->text('head_scripts')->nullable();

            // Sitemap hints.
            $table->string('sitemap_priority')->default('0.5');
            $table->string('sitemap_frequency')->default('weekly');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
