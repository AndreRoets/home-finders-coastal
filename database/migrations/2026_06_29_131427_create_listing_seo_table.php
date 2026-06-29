<?php

use App\Support\DynamicSeo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-listing SEO overrides for the dynamic, CoreX-backed property pages.
     * Keyed by the CoreX listing id; any blank column falls back to the
     * auto-generated meta in {@see DynamicSeo}.
     */
    public function up(): void
    {
        Schema::create('listing_seo', function (Blueprint $table) {
            $table->id();
            $table->string('listing_id')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_seo');
    }
};
