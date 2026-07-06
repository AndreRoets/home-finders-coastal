import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface PropertyEnquiryFormProps {
    /** Canonical property slug — the enquiry POST target. */
    slug: string;
    /** Property title, shown in the intro line. */
    title: string;
}

/**
 * Property24-style enquiry form shown in the property sidebar. Posts to
 * `property.enquire`, which emails the listing's agent(s) and pushes the lead
 * to CoreX. The message field is pre-filled with a sensible default so a
 * visitor can enquire in one click.
 */
export default function PropertyEnquiryForm({ slug, title }: PropertyEnquiryFormProps) {
    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: '',
        email: '',
        phone: '',
        message: 'I’m interested in this property, please contact me.',
        company: '', // honeypot — must stay empty
    });

    const handleSubmit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('property.enquire', slug), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    if (recentlySuccessful) {
        return (
            <div className="border-marine/30 bg-marine/5 text-marine rounded-sm border px-4 py-6 text-center text-sm">
                <p className="font-medium">Thanks for your enquiry.</p>
                <p className="mt-1 text-neutral-600">The agent will be in touch with you shortly.</p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <p className="text-navy text-sm font-medium">Enquire about {title}</p>

            <div>
                <input
                    id="enquiry-name"
                    name="name"
                    type="text"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                    placeholder="Name"
                    autoComplete="name"
                />
                {errors.name && <p className="text-brand-red mt-1 text-xs">{errors.name}</p>}
            </div>

            <div>
                <input
                    id="enquiry-email"
                    name="email"
                    type="email"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                    placeholder="Email"
                    autoComplete="email"
                />
                {errors.email && <p className="text-brand-red mt-1 text-xs">{errors.email}</p>}
            </div>

            <div>
                <input
                    id="enquiry-phone"
                    name="phone"
                    type="tel"
                    value={data.phone}
                    onChange={(event) => setData('phone', event.target.value)}
                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                    placeholder="Contact number"
                    autoComplete="tel"
                />
                {errors.phone && <p className="text-brand-red mt-1 text-xs">{errors.phone}</p>}
            </div>

            <div>
                <textarea
                    id="enquiry-message"
                    name="message"
                    rows={4}
                    value={data.message}
                    onChange={(event) => setData('message', event.target.value)}
                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                    placeholder="Your message"
                />
                {errors.message && <p className="text-brand-red mt-1 text-xs">{errors.message}</p>}
            </div>

            {/* Honeypot: hidden from users, a magnet for bots. */}
            <div aria-hidden="true" className="hidden">
                <label htmlFor="enquiry-company">Company</label>
                <input
                    id="enquiry-company"
                    name="company"
                    type="text"
                    tabIndex={-1}
                    autoComplete="off"
                    value={data.company}
                    onChange={(event) => setData('company', event.target.value)}
                />
            </div>

            <button
                type="submit"
                disabled={processing}
                className="bg-navy hover:bg-navy/90 flex w-full items-center justify-center rounded-full px-6 py-3 text-sm font-semibold tracking-wide text-white transition-colors disabled:cursor-not-allowed disabled:opacity-50"
            >
                {processing ? 'Sending…' : 'Contact agent'}
            </button>

            <p className="text-center text-xs leading-relaxed text-neutral-500">
                By continuing I understand and agree with the{' '}
                <a href={route('privacy-policy')} className="hover:text-marine underline">
                    Terms &amp; Conditions and Privacy Policy
                </a>
                .
            </p>
        </form>
    );
}
