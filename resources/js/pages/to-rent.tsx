import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PropertySearch, { type SearchFacets, type SearchValues } from '@/components/public/property-search';
import PublicLayout from '@/layouts/public-layout';

export default function ToRent({ listings = [], filters, search }: { listings?: Listing[]; filters?: SearchFacets; search?: SearchValues }) {
    return (
        <PublicLayout title="To Rent" tone="light">
            <PageHero
                eyebrow="Rent"
                title="Properties To Rent"
                description="Find your next coastal rental, from short walks to the sand to long-term family homes."
            />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                {filters && (
                    <div className="mb-10">
                        <PropertySearch filters={filters} mode="rent" values={search} variant="page" />
                    </div>
                )}
                <p className="mb-8 text-xs tracking-[0.2em] text-marine/80 uppercase">{listings.length} properties available</p>
                <PropertyGrid listings={listings} emptyMessage="No rentals match your search." />
            </section>
        </PublicLayout>
    );
}
