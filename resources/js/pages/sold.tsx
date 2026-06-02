import { sampleListings } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';

export default function Sold() {
    const listings = sampleListings.filter((listing) => listing.status === 'sold');

    return (
        <PublicLayout title="Sold">
            <PageHero
                eyebrow="Track Record"
                title="Recently Sold"
                description="A snapshot of homes we’ve recently sold along the coast — proof of results in your neighbourhood."
            />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="mb-6 text-sm text-slate-500">{listings.length} recently sold</p>
                <PropertyGrid listings={listings} emptyMessage="No sold properties to display yet." />
            </section>
        </PublicLayout>
    );
}
