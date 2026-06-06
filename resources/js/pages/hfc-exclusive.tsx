import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Gem, KeyRound, Lock } from 'lucide-react';

const perks = [
    { icon: Lock, title: 'Off-Market Access', description: 'Discreet listings shared only with our exclusive network.' },
    { icon: Gem, title: 'Premium Properties', description: 'The finest coastal homes, curated for discerning buyers.' },
    { icon: KeyRound, title: 'Priority Viewings', description: 'Be first in line for private viewings before homes go public.' },
];

export default function HfcExclusive({ listings = [] }: { listings?: Listing[] }) {
    return (
        <PublicLayout title="HFC Exclusive" tone="light">
            <PageHero
                eyebrow="By Invitation"
                title="HFC Exclusive"
                description="A private collection of premium and off-market coastal properties, available only through Home Finders Coastal."
            />

            <section className="border-b border-slate-200 bg-slate-50">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-3 lg:px-8">
                    {perks.map((perk) => (
                        <div key={perk.title} className="flex gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-marine/40 text-marine">
                                <perk.icon className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 className="text-lg font-light text-navy">{perk.title}</h3>
                                <p className="mt-1.5 text-sm leading-relaxed text-neutral-600">{perk.description}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <h2 className="text-3xl font-light text-navy">Exclusive Listings</h2>
                <div className="mt-10">
                    <PropertyGrid listings={listings} emptyMessage="No exclusive listings are available right now." />
                </div>

                <div className="mt-16 rounded-sm border border-slate-200 bg-slate-50 px-8 py-12 text-center">
                    <h3 className="text-2xl font-light text-navy">Want early access to exclusive homes?</h3>
                    <p className="mx-auto mt-3 max-w-xl text-neutral-600">
                        Register your interest and our team will notify you when matching properties become available.
                    </p>
                    <Link
                        href={route('contact')}
                        className="mt-7 inline-flex items-center justify-center rounded-full bg-brand-red px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-brand-red-bright"
                    >
                        Register Your Interest
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
