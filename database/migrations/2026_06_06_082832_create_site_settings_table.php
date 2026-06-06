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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // General identity / defaults.
            $table->string('site_name')->nullable();
            $table->text('default_meta_description')->nullable();
            $table->string('default_og_image')->nullable();
            $table->string('default_twitter_handle')->nullable();

            // Google analytics & tags.
            $table->string('ga4_measurement_id')->nullable();
            $table->string('gtm_container_id')->nullable();
            $table->string('google_search_console_verification')->nullable();
            $table->string('google_ads_conversion_id')->nullable();
            $table->string('google_ads_conversion_label')->nullable();

            // Raw snippets injected verbatim.
            $table->text('head_scripts')->nullable();
            $table->text('body_scripts')->nullable();

            // robots.txt body (served at /robots.txt).
            $table->text('robots_txt')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
