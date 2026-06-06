import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';

export default function Sold({ listings = [] }: { listings?: Listing[] }) {
    return (
        <PublicLayout title="Sold" tone="light">
            <PageHero
                eyebrow="Track Record"
                title="Recently Sold"
                image="https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=2400&q=70"
            />
            <section className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6 lg:px-8">
                <p className="mb-8 text-xs tracking-[0.2em] text-marine/80 uppercase">{listings.length} recently sold</p>
                <PropertyGrid listings={listings} emptyMessage="No sold properties to display yet." />
            </section>
        </PublicLayout>
    );
}
