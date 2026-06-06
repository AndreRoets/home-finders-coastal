<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessage;
use App\Services\Corex\AgencyMapper;
use App\Services\Corex\CorexClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    /**
     * The public contact page. Contact details and social links come from the
     * shared agency prop (see HandleInertiaRequests), so no page props needed.
     */
    public function show(): Response
    {
        return Inertia::render('contact');
    }

    /**
     * Handle a contact-form submission: validate (with a honeypot), then email
     * the agency's public address. Always redirects back with a success flash —
     * even when delivery is skipped — so we never leak whether a recipient is
     * configured.
     */
    public function send(ContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (($recipient = $this->recipient()) !== null) {
            Mail::to($recipient)->send(new ContactMessage(
                senderName: $validated['name'],
                senderEmail: $validated['email'],
                senderPhone: (string) ($validated['phone'] ?? ''),
                body: $validated['message'],
            ));
        }

        return back()->with('success', 'Thanks for getting in touch — we’ll be in contact shortly.');
    }

    /**
     * The agency's public email from CoreX, falling back to the configured mail
     * "from" address when CoreX has none set.
     */
    protected function recipient(): ?string
    {
        $email = AgencyMapper::map($this->corex->agency())['contact']['email'] ?? null;

        return $email ?? config('mail.from.address');
    }
}
