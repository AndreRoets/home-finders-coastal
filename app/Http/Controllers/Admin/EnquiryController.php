<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Services\Enquiries\Channel;
use App\Services\Enquiries\EnquiryFilters;
use App\Services\Enquiries\EnquiryForm;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Form submissions from the public site and the marketing channel each one is
 * credited to — the panel's answer to "which channel actually produced
 * enquiries?", which GA4's form_start count cannot give.
 */
class EnquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = EnquiryFilters::fromRequest($request);

        $paginator = $filters->apply(Enquiry::query())
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/enquiries/index', [
            'filters' => $filters->toArray(),
            'channels' => $this->channelCounts($filters),
            'campaigns' => $this->paidCampaigns($filters),
            'monthly' => $this->monthly($filters),
            'enquiries' => collect($paginator->items())->map(fn (Enquiry $enquiry): array => $this->row($enquiry))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'options' => [
                'channels' => Channel::options(),
                'forms' => EnquiryForm::options(),
                'campaigns' => Enquiry::query()
                    ->whereNotNull('utm_campaign')
                    ->distinct()
                    ->orderBy('utm_campaign')
                    ->pluck('utm_campaign')
                    ->prepend(EnquiryFilters::NO_CAMPAIGN)
                    ->values(),
                'presets' => EnquiryFilters::PRESETS,
            ],
        ]);
    }

    public function show(Enquiry $enquiry): Response
    {
        return Inertia::render('admin/enquiries/show', [
            'enquiry' => [
                ...$this->row($enquiry),
                ...$enquiry->only(['phone', 'message', 'listing_id', 'page_url', 'landing_page', 'referrer', 'utm_term', 'utm_content', 'gclid', 'fbclid']),
                'first_seen_at' => $enquiry->first_seen_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * The filtered list as a spreadsheet, with every raw attribution value so
     * the classification can be checked.
     */
    public function download(Request $request): StreamedResponse
    {
        $filters = EnquiryFilters::fromRequest($request);

        // Who exported what — not the contents.
        Log::info('Enquiries CSV exported', [
            'filters' => $filters->toArray(),
            'user_id' => $request->user()?->id,
        ]);

        $columns = [
            'id', 'received_at', 'form', 'name', 'email', 'phone', 'listing_reference', 'channel',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid',
            'referrer', 'landing_page', 'page_url', 'first_seen_at', 'message',
        ];

        $filename = 'enquiries-'.$filters->from->toDateString().'-to-'.$filters->to->toDateString().'.csv';

        return response()->streamDownload(function () use ($filters, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);

            $filters->apply(Enquiry::query())->chunkById(200, function (Collection $rows) use ($out): void {
                foreach ($rows as $enquiry) {
                    /** @var Enquiry $enquiry */
                    fputcsv($out, array_map($this->csvCell(...), [
                        $enquiry->id,
                        $enquiry->created_at?->toDateTimeString(),
                        $enquiry->form->label(),
                        $enquiry->name,
                        $enquiry->email,
                        $enquiry->phone,
                        $enquiry->listing_reference,
                        $enquiry->channel->label(),
                        $enquiry->utm_source,
                        $enquiry->utm_medium,
                        $enquiry->utm_campaign,
                        $enquiry->utm_term,
                        $enquiry->utm_content,
                        $enquiry->gclid,
                        $enquiry->fbclid,
                        $enquiry->referrer,
                        $enquiry->landing_page,
                        $enquiry->page_url,
                        $enquiry->first_seen_at?->toDateTimeString(),
                        $enquiry->message,
                    ]));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Enquiries per channel in the date range — every channel, zeros included,
     * so the chart always shows the full set.
     *
     * @return list<array{value: string, label: string, count: int}>
     */
    protected function channelCounts(EnquiryFilters $filters): array
    {
        $counts = $filters->applyRange(Enquiry::query())
            ->selectRaw('channel, count(*) as total')
            ->groupBy('channel')
            ->pluck('total', 'channel');

        return array_map(
            static fn (array $option): array => [...$option, 'count' => (int) ($counts[$option['value']] ?? 0)],
            Channel::options(),
        );
    }

    /**
     * Paid Search / Paid Social enquiries in the date range by utm_campaign,
     * biggest first.
     *
     * @return list<array{channel: string, campaign: string, count: int}>
     */
    protected function paidCampaigns(EnquiryFilters $filters): array
    {
        return $filters->applyRange(Enquiry::query())
            ->whereIn('channel', [Channel::PaidSearch, Channel::PaidSocial])
            ->selectRaw('channel, utm_campaign, count(*) as total')
            ->groupBy('channel', 'utm_campaign')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Enquiry $row): array => [
                'channel' => $row->channel->label(),
                'campaign' => $row->utm_campaign ?? EnquiryFilters::NO_CAMPAIGN,
                'count' => (int) $row->getAttribute('total'),
            ])
            ->all();
    }

    /**
     * Enquiries per channel per calendar month across the date range, empty
     * months included — the numbers to hold up against GA4's form_start.
     *
     * Bucketed in PHP rather than SQL so it works the same on SQLite and MySQL.
     *
     * @return list<array{month: string, label: string, counts: array<string, int>, total: int}>
     */
    protected function monthly(EnquiryFilters $filters): array
    {
        $months = [];

        for ($month = $filters->from->copy()->startOfMonth(); $month->lte($filters->to); $month->addMonth()) {
            $months[$month->format('Y-m')] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'counts' => array_fill_keys(array_column(Channel::options(), 'value'), 0),
                'total' => 0,
            ];
        }

        $filters->applyRange(Enquiry::query())
            ->select(['id', 'created_at', 'channel'])
            ->lazyById(500)
            ->each(function (Enquiry $enquiry) use (&$months): void {
                $key = $enquiry->created_at->format('Y-m');

                if (isset($months[$key])) {
                    $months[$key]['counts'][$enquiry->channel->value]++;
                    $months[$key]['total']++;
                }
            });

        return array_values($months);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Enquiry $enquiry): array
    {
        return [
            'id' => $enquiry->id,
            'form' => $enquiry->form->label(),
            'name' => $enquiry->name,
            'email' => $enquiry->email,
            'listing_reference' => $enquiry->listing_reference,
            'excerpt' => Str::limit($enquiry->message, 90),
            'channel' => $enquiry->channel->value,
            'channel_label' => $enquiry->channel->label(),
            'utm_source' => $enquiry->utm_source,
            'utm_medium' => $enquiry->utm_medium,
            'utm_campaign' => $enquiry->utm_campaign,
            'referrer' => $enquiry->referrer,
            'gclid' => $enquiry->gclid !== null,
            'fbclid' => $enquiry->fbclid !== null,
            'received_at' => $enquiry->created_at?->toIso8601String(),
        ];
    }

    /**
     * Neutralise spreadsheet formulas: a value from a visitor that starts with
     * = + - @ would otherwise run as a formula when the CSV is opened in Excel.
     */
    protected function csvCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
