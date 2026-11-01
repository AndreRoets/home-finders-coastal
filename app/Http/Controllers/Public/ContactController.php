<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessage;
use App\Services\Corex\AgencyMapper;
use App\Services\Corex\CorexClient;
use App\Services\Enquiries\EnquiryForm;
use App\Services\Enquiries\EnquiryRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        protected CorexClient $corex,
        protected EnquiryRecorder $enquiries,
    ) {}

    /**
     * The public contact page. Contact details and social links come from the
     * shared agency prop (see HandleInertiaRequests), so no page props needed.
     */
    public function show(): Response
    {
        return Inertia::render('contact');
    }

    /**
     * Handle a contact-form submission: validate (with a honeypot), save it as
     * an enquiry with the visitor's first touch, then email the agency's public
     * address. Always redirects back with a success flash — even when delivery
     * is skipped — so we never leak whether a recipient is configured. The
     * `form_submitted` flash (which fires GA4's form_submit) is only set when
     * the enquiry was actually saved.
     */
    public function send(ContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $enquiry = $this->enquiries->record(EnquiryForm::Contact, $validated, $validated['attribution'] ?? [], $request);

        if (($recipient = $this->recipient()) !== null) {
            Mail::to($recipient)->send(new ContactMessage(
                senderName: $validated['name'],
                senderEmail: $validated['email'],
                senderPhone: (string) ($validated['phone'] ?? ''),
                body: $validated['message'],
            ));
        }

        return back()
            ->with('success', 'Thanks for getting in touch — we’ll be in contact shortly.')
            ->with('form_submitted', $enquiry?->form->value);
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
