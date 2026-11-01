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
        // The lists the enquiry channel rules match against, editable from the
        // Marketing panel. One entry per line; null means "use the defaults" in
        // App\Services\Enquiries\ChannelClassifier.
        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('attribution_window_days')->nullable();
            $table->text('channel_search_sources')->nullable();
            $table->text('channel_social_sources')->nullable();
            $table->text('channel_paid_mediums')->nullable();
            $table->text('channel_email_mediums')->nullable();
            $table->text('channel_social_mediums')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'attribution_window_days',
                'channel_search_sources',
                'channel_social_sources',
                'channel_paid_mediums',
                'channel_email_mediums',
                'channel_social_mediums',
            ]);
        });
    }
};
