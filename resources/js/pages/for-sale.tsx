import BranchFilter from '@/components/public/branch-filter';
import { type BranchOption } from '@/components/public/branches';
import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PropertySearch, { type SearchFacets, type SearchValues } from '@/components/public/property-search';
import PublicLayout from '@/layouts/public-layout';

export default function ForSale({
    listings = [],
    filters,
    search,
    branches = [],
    activeBranch = null,
}: {
    listings?: Listing[];
    filters?: SearchFacets;
    search?: SearchValues;
    branches?: BranchOption[];
    activeBranch?: number | string | null;
}) {
    return (
        <PublicLayout title="For Sale" tone="light">
            <PageHero
                eyebrow="Buy"
                title="Properties For Sale"
                image="https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=2400&q=70"
            />
            <section className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6 lg:px-8">
                <BranchFilter branches={branches} active={activeBranch} className="mb-8" />
                {filters && (
                    <div className="mb-10">
                        <PropertySearch filters={filters} mode="sale" values={search} variant="page" branchId={activeBranch} />
                    </div>
                )}
                <p className="mb-8 text-xs tracking-[0.2em] text-marine/80 uppercase">{listings.length} properties available</p>
                <PropertyGrid listings={listings} emptyMessage="No properties match your search." />
            </section>
        </PublicLayout>
    );
}
