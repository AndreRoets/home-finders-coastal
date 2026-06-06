import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { type SharedData } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import { Clock, Mail, MapPin, Phone } from 'lucide-react';
import { type FormEventHandler } from 'react';

export default function Contact() {
    const { agency } = usePage<SharedData>().props;
    const contact = agency?.contact;
    const openHours = agency?.openHours ?? [];
    const email = contact?.email;

    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: '',
        email: '',
        phone: '',
        message: '',
        company: '', // honeypot — must stay empty
    });

    const handleSubmit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
        post(route('contact.send'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <PublicLayout title="Contact" tone="light">
            <PageHero
                eyebrow="Get in Touch"
                title="Contact Us"
                image="https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto grid max-w-7xl gap-12 px-4 pt-6 pb-12 sm:px-6 lg:grid-cols-3 lg:px-8">
                {/* Contact details — pulled from CoreX; each block shows only when set. */}
                <div className="space-y-6 lg:col-span-1">
                    {contact?.address && (
                        <div className="flex gap-4">
                            <div className="border-marine/40 text-marine flex h-11 w-11 shrink-0 items-center justify-center rounded-full border">
                                <MapPin className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-marine text-xs tracking-[0.2em] uppercase">Office</p>
                                <p className="mt-1 text-sm text-neutral-600">{contact.address}</p>
                            </div>
                        </div>
                    )}

                    {contact?.phone && (
                        <div className="flex gap-4">
                            <div className="border-marine/40 text-marine flex h-11 w-11 shrink-0 items-center justify-center rounded-full border">
                                <Phone className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-marine text-xs tracking-[0.2em] uppercase">Phone</p>
                                <a
                                    href={`tel:${contact.phoneHref}`}
                                    className="hover:text-marine mt-1 block text-sm text-neutral-600 transition-colors"
                                >
                                    {contact.phone}
                                </a>
                            </div>
                        </div>
                    )}

                    {email && (
                        <div className="flex gap-4">
                            <div className="border-marine/40 text-marine flex h-11 w-11 shrink-0 items-center justify-center rounded-full border">
                                <Mail className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-marine text-xs tracking-[0.2em] uppercase">Email</p>
                                <a href={`mailto:${email}`} className="hover:text-marine mt-1 block text-sm text-neutral-600 transition-colors">
                                    {email}
                                </a>
                            </div>
                        </div>
                    )}

                    {openHours.length > 0 && (
                        <div className="flex gap-4">
                            <div className="border-marine/40 text-marine flex h-11 w-11 shrink-0 items-center justify-center rounded-full border">
                                <Clock className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-marine text-xs tracking-[0.2em] uppercase">Hours</p>
                                <ul className="mt-1 space-y-0.5 text-sm text-neutral-600">
                                    {openHours.map((row) => (
                                        <li key={row.days}>
                                            <span className="text-neutral-700">{row.days}:</span> {row.hours}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}

                    {/* Prominent call action using the API number. */}
                    {contact?.phone && (
                        <a
                            href={`tel:${contact.phoneHref}`}
                            className="bg-navy hover:bg-navy/90 inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                        >
                            <Phone className="h-4 w-4" />
                            Call us
                        </a>
                    )}
                </div>

                {/* Contact form */}
                <div className="lg:col-span-2">
                    <form onSubmit={handleSubmit} className="rounded-sm border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        {recentlySuccessful && (
                            <div className="border-marine/30 bg-marine/5 text-marine mb-6 rounded-sm border px-4 py-3 text-sm">
                                Thanks for getting in touch — we’ll be in contact shortly.
                            </div>
                        )}

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label htmlFor="name" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Full name
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                                    placeholder="Jane Smith"
                                />
                                {errors.name && <p className="text-brand-red mt-1.5 text-xs">{errors.name}</p>}
                            </div>
                            <div>
                                <label htmlFor="email" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                                    placeholder="jane@example.com"
                                />
                                {errors.email && <p className="text-brand-red mt-1.5 text-xs">{errors.email}</p>}
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="phone" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(event) => setData('phone', event.target.value)}
                                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                                    placeholder="+27 ..."
                                />
                                {errors.phone && <p className="text-brand-red mt-1.5 text-xs">{errors.phone}</p>}
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="message" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Message
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows={5}
                                    value={data.message}
                                    onChange={(event) => setData('message', event.target.value)}
                                    className="focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400"
                                    placeholder="How can we help?"
                                />
                                {errors.message && <p className="text-brand-red mt-1.5 text-xs">{errors.message}</p>}
                            </div>
                        </div>

                        {/* Honeypot: hidden from users, a magnet for bots. */}
                        <div aria-hidden="true" className="hidden">
                            <label htmlFor="company">Company</label>
                            <input
                                id="company"
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
                            className="bg-brand-red hover:bg-brand-red-bright mt-7 inline-flex items-center justify-center rounded-full px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {processing ? 'Sending…' : 'Send Message'}
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
