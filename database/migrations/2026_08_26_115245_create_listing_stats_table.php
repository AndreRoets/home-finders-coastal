<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-listing, per-event, per-day engagement counters for the properties
 * syndicated from CoreX.
 *
 * Listings themselves are never stored locally (they are pulled + cached), so
 * `listing_id` is CoreX's id kept as a string — it is a foreign key into CoreX,
 * not into this database. `reference` rides along so CoreX can match on either.
 *
 * `count` is the lifetime-to-date total for that listing/event/day and
 * `pushed_count` is how much of it CoreX has already been told about. The
 * difference is the outstanding delta, which makes the push naturally
 * self-healing: a failed or skipped run simply sends a larger delta next time,
 * and a redelivered run can never double-count because `pushed_count` only
 * advances on a confirmed 2xx.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_stats', function (Blueprint $table) {
            $table->id();
            $table->string('listing_id', 64);
            $table->string('reference', 64)->nullable();
            $table->string('event', 32);
            $table->date('date');
            $table->unsignedBigInteger('count')->default(0);
            $table->unsignedBigInteger('pushed_count')->default(0);
            $table->timestamp('pushed_at')->nullable();
            $table->timestamps();

            $table->unique(['listing_id', 'event', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_stats');
    }
};
