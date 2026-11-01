<?php

namespace App\Services\Enquiries;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The Enquiries screen's filters, read from the query string — shared by the
 * screen and its CSV export so a download is exactly the list on screen.
 *
 * The date range scopes everything (chart, campaign breakdown, monthly
 * summary, list); channel, campaign, form and search narrow the list only.
 */
class EnquiryFilters
{
    /** Preset ranges, in days; "custom" uses from/to. */
    public const PRESETS = [7, 30, 90];

    public const DEFAULT_RANGE = '30';

    /** Campaign filter value meaning "no utm_campaign". */
    public const NO_CAMPAIGN = '(not set)';

    public function __construct(
        public readonly string $range,
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly ?Channel $channel = null,
        public readonly ?string $campaign = null,
        public readonly ?EnquiryForm $form = null,
        public readonly string $search = '',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $range = (string) $request->query('range', self::DEFAULT_RANGE);
        $from = null;
        $to = null;

        if ($range === 'custom') {
            $from = self::date($request->query('from'))?->startOfDay();
            $to = self::date($request->query('to'))?->endOfDay();

            if ($from === null || $to === null || $from->greaterThan($to)) {
                $range = self::DEFAULT_RANGE;
            }
        }

        if ($range !== 'custom') {
            $days = in_array((int) $range, self::PRESETS, true) ? (int) $range : (int) self::DEFAULT_RANGE;
            $range = (string) $days;
            $to = now()->endOfDay();
            $from = now()->subDays($days - 1)->startOfDay();
        }

        $campaign = trim((string) $request->query('campaign', ''));

        return new self(
            range: $range,
            from: $from,
            to: $to,
            channel: Channel::tryFrom((string) $request->query('channel', '')),
            campaign: $campaign !== '' ? $campaign : null,
            form: EnquiryForm::tryFrom((string) $request->query('form', '')),
            search: trim((string) $request->query('q', '')),
        );
    }

    /**
     * Only the date range.
     *
     * @param  Builder<Enquiry>  $query
     * @return Builder<Enquiry>
     */
    public function applyRange(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [$this->from, $this->to]);
    }

    /**
     * The date range plus every list filter.
     *
     * @param  Builder<Enquiry>  $query
     * @return Builder<Enquiry>
     */
    public function apply(Builder $query): Builder
    {
        return $this->applyRange($query)
            ->search($this->search)
            ->when($this->channel, fn (Builder $q, Channel $channel) => $q->where('channel', $channel))
            ->when($this->form, fn (Builder $q, EnquiryForm $form) => $q->where('form', $form))
            ->when($this->campaign, fn (Builder $q, string $campaign) => $campaign === self::NO_CAMPAIGN
                ? $q->whereNull('utm_campaign')
                : $q->where('utm_campaign', $campaign));
    }

    /**
     * For the screen to show the active filters, and for the export link.
     *
     * @return array{range: string, from: string, to: string, channel: ?string, campaign: ?string, form: ?string, q: string}
     */
    public function toArray(): array
    {
        return [
            'range' => $this->range,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'channel' => $this->channel?->value,
            'campaign' => $this->campaign,
            'form' => $this->form?->value,
            'q' => $this->search,
        ];
    }

    protected static function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }
    }
}
