import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';

export default function ForSale({ listings = [] }: { listings?: Listing[] }) {
    return (
        <PublicLayout title="For Sale">
            <PageHero
                eyebrow="Buy"
                title="Properties For Sale"
                description="Explore homes for sale along the coast, from beachfront apartments to family estates."
            />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="mb-6 text-sm text-slate-500">{listings.length} properties available</p>
                <PropertyGrid listings={listings} emptyMessage="No properties are currently listed for sale." />
            </section>
        </PublicLayout>
    );
}
