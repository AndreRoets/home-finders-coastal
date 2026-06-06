import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Clock, Mail, MapPin, Phone } from 'lucide-react';
import { type FormEventHandler } from 'react';

const contactDetails = [
    { icon: MapPin, label: 'Office', value: '12 Beachfront Road, Coastal Bay' },
    { icon: Phone, label: 'Phone', value: '+27 21 000 0000' },
    { icon: Mail, label: 'Email', value: 'hello@homefinderscoastal.com' },
    { icon: Clock, label: 'Hours', value: 'Mon – Fri, 8:30am – 5:00pm' },
];

export default function Contact() {
    // Scaffold only — wire this up to a backend route + form request when ready.
    const handleSubmit: FormEventHandler<HTMLFormElement> = (event) => {
        event.preventDefault();
    };

    return (
        <PublicLayout title="Contact" tone="light">
            <PageHero
                eyebrow="Get in Touch"
                title="Contact Us"
                description="Have a question about a listing or thinking of selling? We’d love to hear from you."
            />

            <section className="mx-auto grid max-w-7xl gap-12 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
                {/* Contact details */}
                <div className="space-y-6 lg:col-span-1">
                    {contactDetails.map((detail) => (
                        <div key={detail.label} className="flex gap-4">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-marine/40 text-marine">
                                <detail.icon className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs tracking-[0.2em] text-marine uppercase">{detail.label}</p>
                                <p className="mt-1 text-sm text-neutral-600">{detail.value}</p>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Contact form */}
                <div className="lg:col-span-2">
                    <form onSubmit={handleSubmit} className="rounded-sm border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label htmlFor="name" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Full name
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    className="w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 outline-none transition-colors placeholder:text-neutral-400 focus:border-marine"
                                    placeholder="Jane Smith"
                                />
                            </div>
                            <div>
                                <label htmlFor="email" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    className="w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 outline-none transition-colors placeholder:text-neutral-400 focus:border-marine"
                                    placeholder="jane@example.com"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="phone" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    className="w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 outline-none transition-colors placeholder:text-neutral-400 focus:border-marine"
                                    placeholder="+27 ..."
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="message" className="mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase">
                                    Message
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows={5}
                                    className="w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 outline-none transition-colors placeholder:text-neutral-400 focus:border-marine"
                                    placeholder="How can we help?"
                                />
                            </div>
                        </div>
                        <button
                            type="submit"
                            className="mt-7 inline-flex items-center justify-center rounded-full bg-brand-red px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-brand-red-bright"
                        >
                            Send Message
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
