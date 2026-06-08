import BranchFilter from '@/components/public/branch-filter';
import { type BranchOption } from '@/components/public/branches';
import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';

export default function HfcExclusive({
    listings = [],
    branches = [],
    activeBranch = null,
}: {
    listings?: Listing[];
    branches?: BranchOption[];
    activeBranch?: number | string | null;
}) {
    return (
        <PublicLayout title="HFC Exclusive" tone="light">
            <PageHero
                eyebrow="By Invitation"
                title="HFC Exclusive"
                image="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <BranchFilter branches={branches} active={activeBranch} className="mb-8" />
                <h2 className="text-navy text-3xl font-light">Exclusive Listings</h2>
                <div className="mt-10">
                    <PropertyGrid listings={listings} emptyMessage="No exclusive listings are available right now." />
                </div>

                <div className="mt-16 rounded-sm border border-slate-200 bg-slate-50 px-8 py-12 text-center">
                    <h3 className="text-navy text-2xl font-light">Want early access to exclusive homes?</h3>
                    <p className="mx-auto mt-3 max-w-xl text-neutral-600">
                        Register your interest and our team will notify you when matching properties become available.
                    </p>
                    <Link
                        href={route('contact')}
                        className="bg-brand-red hover:bg-brand-red-bright mt-7 inline-flex items-center justify-center rounded-full px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                    >
                        Register Your Interest
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
