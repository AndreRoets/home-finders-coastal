<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyEnquiryRequest;
use App\Mail\PropertyEnquiry;
use App\Services\Corex\AgencyMapper;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PropertyEnquiryController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    /**
     * Handle a property enquiry: validate (with a honeypot), email the listing's
     * agent(s) — with the visitor as reply-to — and push the lead to CoreX so it
     * lands in the agency's CRM. Always redirects back with a success flash, even
     * when a channel is skipped, so we never leak recipient configuration.
     */
    public function store(PropertyEnquiryRequest $request, string $idOrRef): RedirectResponse
    {
        $listing = $this->corex->listing(ListingMapper::idFromSlug($idOrRef));

        if ($listing === []) {
            throw new NotFoundHttpException('Listing not found.');
        }

        $property = ListingMapper::detail($listing);
        $validated = $request->validated();

        $this->emailAgents($property, $validated);
        $this->pushLead($property, $validated);

        return back()->with('success', 'Thanks for your enquiry — the agent will be in touch shortly.');
    }

    /**
     * Email the listing's agent(s). The primary agent is the recipient and any
     * co-listing agents are cc'd. Falls back to the agency's public address (then
     * the configured mail "from") when no agent email is on file, so an enquiry
     * is never silently dropped.
     *
     * @param  array<string, mixed>  $property
     * @param  array<string, mixed>  $data
     */
    protected function emailAgents(array $property, array $data): void
    {
        $recipients = $this->recipients($property);

        if ($recipients === []) {
            return;
        }

        $mailable = new PropertyEnquiry(
            senderName: $data['name'],
            senderEmail: $data['email'],
            senderPhone: (string) ($data['phone'] ?? ''),
            body: $data['message'],
            propertyTitle: (string) $property['title'],
            propertyReference: $property['ref'] ?? null,
            propertyUrl: route('property.show', $property['slug']),
        );

        Mail::to($recipients[0])
            ->cc(array_slice($recipients, 1))
            ->send($mailable);
    }

    /**
     * The enquiry recipients: every attributed agent's email (primary first),
     * falling back to the agency's public email and finally the configured mail
     * "from" address. Returns an empty list only when none of those resolve.
     *
     * @param  array<string, mixed>  $property
     * @return array<int, string>
     */
    protected function recipients(array $property): array
    {
        $emails = array_values(array_filter(array_map(
            static fn (array $agent): string => (string) ($agent['email'] ?? ''),
            $property['agents'] ?? [],
        )));

        if ($emails !== []) {
            return $emails;
        }

        $fallback = AgencyMapper::map($this->corex->agency())['contact']['email']
            ?? config('mail.from.address');

        return $fallback !== null ? [$fallback] : [];
    }

    /**
     * Push the enquiry to CoreX as a lead so it appears in the agency's CRM
     * alongside their other leads. Fails soft inside the client, so a CoreX
     * outage never blocks the email or errors the visitor's submission.
     *
     * @param  array<string, mixed>  $property
     * @param  array<string, mixed>  $data
     */
    protected function pushLead(array $property, array $data): void
    {
        $this->corex->createLead([
            'source' => 'website',
            'listing_id' => $property['id'] ?? null,
            'listing_reference' => $property['ref'] ?? null,
            'agent_ids' => array_values(array_filter(array_map(
                static fn (array $agent): mixed => $agent['id'] ?? null,
                $property['agents'] ?? [],
            ))),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => (string) ($data['phone'] ?? ''),
            'message' => $data['message'],
        ]);
    }
}
