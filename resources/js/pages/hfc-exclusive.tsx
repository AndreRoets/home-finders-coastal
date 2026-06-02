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
        <PublicLayout title="HFC Exclusive">
            <PageHero
                eyebrow="By Invitation"
                title="HFC Exclusive"
                description="A private collection of premium and off-market coastal properties, available only through Home Finders Coastal."
            />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-3 lg:px-8">
                    {perks.map((perk) => (
                        <div key={perk.title} className="flex gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <perk.icon className="h-6 w-6" />
                            </div>
                            <div>
                                <h3 className="font-semibold text-slate-900">{perk.title}</h3>
                                <p className="mt-1 text-sm leading-relaxed text-slate-600">{perk.description}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <h2 className="text-2xl font-bold tracking-tight text-slate-900">Exclusive Listings</h2>
                <div className="mt-8">
                    <PropertyGrid listings={listings} emptyMessage="No exclusive listings are available right now." />
                </div>

                <div className="mt-12 rounded-2xl border border-amber-200 bg-amber-50 px-8 py-10 text-center">
                    <h3 className="text-xl font-semibold text-slate-900">Want early access to exclusive homes?</h3>
                    <p className="mx-auto mt-2 max-w-xl text-slate-600">
                        Register your interest and our team will notify you when matching properties become available.
                    </p>
                    <Link
                        href={route('contact')}
                        className="mt-6 inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-slate-800"
                    >
                        Register Your Interest
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
