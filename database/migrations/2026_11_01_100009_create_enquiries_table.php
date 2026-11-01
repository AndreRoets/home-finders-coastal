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
        // Every form submission on the public site, with where the visitor came
        // from. The email to the agent and the CoreX lead are how the enquiry is
        // handled; this row is the record marketing reports on.
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();

            // Which form produced it (see App\Services\Enquiries\EnquiryForm).
            $table->string('form', 40);

            $table->string('name', 120);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('message');

            // The CoreX listing, for property enquiries.
            $table->string('listing_id')->nullable();
            $table->string('listing_reference')->nullable();

            // The page the form was submitted from, and the visitor's first
            // touch: the page an ad / search / referrer actually landed them on.
            $table->text('page_url')->nullable();
            $table->text('landing_page')->nullable();
            $table->text('referrer')->nullable();

            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('gclid')->nullable();
            $table->string('fbclid')->nullable();

            // When that first touch happened.
            $table->timestamp('first_seen_at')->nullable();

            // Classified once, when saved, so a later change to the channel
            // rules never relabels enquiries that were already reported on.
            $table->string('channel', 40);

            $table->timestamps();

            $table->index('created_at');
            $table->index(['channel', 'created_at']);
            $table->index('utm_campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
