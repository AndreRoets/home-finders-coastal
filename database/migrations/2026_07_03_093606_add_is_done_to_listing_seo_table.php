<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks a listing's SEO as reviewed/completed so admins can see at a glance
     * which of the live properties still need attention.
     */
    public function up(): void
    {
        Schema::table('listing_seo', function (Blueprint $table) {
            $table->boolean('is_done')->default(false)->after('canonical_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_seo', function (Blueprint $table) {
            $table->dropColumn('is_done');
        });
    }
};
