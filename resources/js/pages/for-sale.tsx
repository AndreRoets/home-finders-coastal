import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PropertySearch, { type SearchFacets, type SearchValues } from '@/components/public/property-search';
import PublicLayout from '@/layouts/public-layout';

export default function ForSale({ listings = [], filters, search }: { listings?: Listing[]; filters?: SearchFacets; search?: SearchValues }) {
    return (
        <PublicLayout title="For Sale">
            <PageHero
                eyebrow="Buy"
                title="Properties For Sale"
                description="Explore homes for sale along the coast, from beachfront apartments to family estates."
            />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                {filters && (
                    <div className="mb-10">
                        <PropertySearch filters={filters} mode="sale" values={search} variant="page" />
                    </div>
                )}
                <p className="mb-8 text-xs tracking-[0.2em] text-marine/80 uppercase">{listings.length} properties available</p>
                <PropertyGrid listings={listings} emptyMessage="No properties match your search." />
            </section>
        </PublicLayout>
    );
}
