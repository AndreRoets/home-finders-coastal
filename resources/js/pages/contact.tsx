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
        <PublicLayout title="Contact">
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
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                                <detail.icon className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900">{detail.label}</p>
                                <p className="mt-0.5 text-sm text-slate-600">{detail.value}</p>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Contact form */}
                <div className="lg:col-span-2">
                    <form onSubmit={handleSubmit} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label htmlFor="name" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Full name
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    className="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                                    placeholder="Jane Smith"
                                />
                            </div>
                            <div>
                                <label htmlFor="email" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    className="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                                    placeholder="jane@example.com"
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="phone" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    className="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                                    placeholder="+27 ..."
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <label htmlFor="message" className="mb-1.5 block text-sm font-medium text-slate-700">
                                    Message
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows={5}
                                    className="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                                    placeholder="How can we help?"
                                />
                            </div>
                        </div>
                        <button
                            type="submit"
                            className="mt-6 inline-flex items-center justify-center rounded-lg bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-sky-700"
                        >
                            Send Message
                        </button>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
