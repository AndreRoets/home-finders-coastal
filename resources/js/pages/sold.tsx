import BranchFilter from '@/components/public/branch-filter';
import { type BranchOption } from '@/components/public/branches';
import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import Pagination, { type Paginated } from '@/components/public/pagination';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';

export default function Sold({
    listings,
    branches = [],
    activeBranch = null,
}: {
    listings: Paginated<Listing>;
    branches?: BranchOption[];
    activeBranch?: number | string | null;
}) {
    return (
        <PublicLayout title="Sold" tone="light">
            <PageHero
                eyebrow="Track Record"
                title="Recently Sold"
                image="https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=2400&q=70"
            />
            <section className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6 lg:px-8">
                <BranchFilter branches={branches} active={activeBranch} className="mb-8" />
                <p className="mb-8 text-xs tracking-[0.2em] text-marine/80 uppercase">{listings.total} recently sold</p>
                <PropertyGrid listings={listings.data} emptyMessage="No sold properties to display yet." />
                <Pagination page={listings} />
            </section>
        </PublicLayout>
    );
}
