<?php

namespace App\Http\Controllers;

use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives webhook deliveries from CoreX OS.
 *
 * CoreX POSTs a JSON body signed with an HMAC-SHA256 of the RAW request body
 * (keyed by COREX_WEBHOOK_SECRET) in the X-CoreX-Signature header. We verify
 * the signature, then bust the relevant CoreX caches so the change shows on the
 * next page view (listings are pulled + cached, not stored locally). A 2xx is
 * returned quickly; non-2xx triggers CoreX's retry/backoff.
 */
class CorexWebhookController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    public function handle(Request $request): JsonResponse
    {
        $this->verifySignature($request);

        $event = (string) $request->header('X-CoreX-Event', $request->input('event', ''));
        $data = (array) $request->input('data', []);

        Log::info('CoreX webhook received', [
            'event' => $event,
            'agency_id' => $request->input('agency_id'),
            'occurred_at' => $request->input('occurred_at'),
        ]);

        if (str_starts_with($event, 'listing.')) {
            $this->corex->forgetListing(
                id: $data['id'] ?? null,
                reference: isset($data['reference']) ? (string) $data['reference'] : null,
                agentIds: $this->listingAgentIds($data),
            );
        } elseif (str_starts_with($event, 'agency.')) {
            $this->corex->forgetAgency();
        } elseif (str_starts_with($event, 'agent.')) {
            $this->corex->forgetAgent($data['id'] ?? null);
        } elseif (str_starts_with($event, 'testimonial.')) {
            $this->corex->forgetTestimonial($data['id'] ?? null);
        } elseif (str_starts_with($event, 'article.')) {
            // article.published / article.updated / article.removed all mean
            // "this agent's articles changed" — bust + re-pull the agent's list.
            $this->corex->refreshAgentArticles($data['agent_id'] ?? null, $data['id'] ?? null);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Extract the ids of every agent attributed to a listing webhook payload.
     *
     * The payload mirrors a CoreX listing resource, so the `agents[]` array is
     * the source of truth (primary + any co-listing agent, each flagged
     * `is_primary`), with the singular `agent` as the legacy fallback — exactly
     * what {@see ListingMapper::extractAgents()} already resolves. Their scoped
     * listings caches (GET /listings?agent_id={id}) are then busted so the
     * relisted property reappears on each agent's profile immediately.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, int|string>
     */
    protected function listingAgentIds(array $data): array
    {
        return array_values(array_filter(
            array_map(
                static fn (array $agent): int|string|null => $agent['id'] ?? null,
                ListingMapper::extractAgents($data),
            ),
            static fn (int|string|null $id): bool => $id !== null && $id !== '',
        ));
    }

    /**
     * Verify the HMAC-SHA256 signature over the RAW request body. Aborts with a
     * 401 when the secret/signature is missing or the digest does not match.
     *
     * The header value is normalised before comparison so we accept the common
     * shapes CoreX (and libraries generally) emit without weakening the check:
     * an optional "sha256=" prefix is stripped, and the HMAC is matched against
     * both its lowercase-hex and base64 encodings. We still compute the digest
     * ourselves with our secret — only the encoding of the *same* digest is
     * flexible, so a wrong secret or tampered body still fails.
     */
    protected function verifySignature(Request $request): void
    {
        $secret = (string) config('services.corex.webhook_secret');
        $body = $request->getContent();
        $signature = $this->normalizeSignature((string) $request->header('X-CoreX-Signature', ''));

        $expectedHex = hash_hmac('sha256', $body, $secret);
        $expectedBase64 = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $matches = $secret !== ''
            && $signature !== ''
            && (hash_equals($expectedHex, $signature) || hash_equals($expectedBase64, $signature));

        if (! $matches) {
            // Diagnose-first: emit the received vs computed signatures at WARNING
            // level (survives LOG_LEVEL=warning) so the next real delivery reveals
            // the exact mismatch. The secret is never logged; the body is truncated.
            Log::warning('CoreX webhook rejected: invalid signature', [
                'event' => $request->header('X-CoreX-Event'),
                'received_signature' => $signature,
                'expected_hex' => $expectedHex,
                'expected_base64' => $expectedBase64,
                'body_length' => strlen($body),
                'body_preview' => substr($body, 0, 60),
                'has_secret' => $secret !== '',
            ]);

            abort(Response::HTTP_UNAUTHORIZED, 'Invalid signature.');
        }
    }

    /**
     * Normalise the X-CoreX-Signature header for comparison: trim surrounding
     * whitespace and strip an optional "sha256=" algorithm prefix (case-insensitive).
     * The remaining value is the raw hex or base64 HMAC digest.
     */
    protected function normalizeSignature(string $signature): string
    {
        $signature = trim($signature);

        if (preg_match('/^sha256=/i', $signature) === 1) {
            $signature = substr($signature, 7);
        }

        return $signature;
    }
}
