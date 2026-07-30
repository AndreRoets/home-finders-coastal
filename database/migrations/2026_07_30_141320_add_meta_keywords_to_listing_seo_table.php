<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-listing SEO keywords, mirroring the meta_keywords column CMS-managed
     * pages already have. Blank leaves the listing without a keywords tag.
     */
    public function up(): void
    {
        Schema::table('listing_seo', function (Blueprint $table) {
            $table->string('meta_keywords')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_seo', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });
    }
};
