import { sampleListings } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';

export default function ToRent() {
    const listings = sampleListings.filter((listing) => listing.status === 'to-rent');

    return (
        <PublicLayout title="To Rent">
            <PageHero
                eyebrow="Rent"
                title="Properties To Rent"
                description="Find your next coastal rental, from short walks to the sand to long-term family homes."
            />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="mb-6 text-sm text-slate-500">{listings.length} properties available</p>
                <PropertyGrid listings={listings} emptyMessage="No rentals are currently available." />
            </section>
        </PublicLayout>
    );
}
