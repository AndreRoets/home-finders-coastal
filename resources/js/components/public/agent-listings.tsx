import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { type Listing } from './listings';
import PropertyGrid from './property-grid';

type FilterKey = 'all' | 'active' | 'exclusive' | 'sold';

const PER_PAGE = 21;

const isSold = (listing: Listing): boolean => listing.status === 'sold';

/**
 * Order active listings (anything not sold) ahead of sold ones, keeping the
 * feed's original order within each group. Array.prototype.sort is stable, so a
 * newer sold listing never jumps ahead of an older active one.
 */
function activeFirst(listings: Listing[]): Listing[] {
    return [...listings].sort((a, b) => Number(isSold(a)) - Number(isSold(b)));
}

/**
 * An agent's listings with a small Active / Exclusives / Sold filter. The
 * default ("All") shows every listing with active ones first, then sold.
 * Filter tabs that have no matching listings are hidden (except "All").
 */
export default function AgentListings({ listings, agentName }: { listings: Listing[]; agentName: string }) {
    const [filter, setFilter] = useState<FilterKey>('all');
    const [page, setPage] = useState(1);

    // Switching tabs starts the new tab from its first page.
    useEffect(() => {
        setPage(1);
    }, [filter]);

    const active = listings.filter((listing) => !isSold(listing));
    const sold = listings.filter(isSold);
    // A sold property is only ever "sold" — it drops out of the exclusives.
    const exclusive = listings.filter((listing) => listing.exclusive && !isSold(listing));

    const allTabs: Array<{ key: FilterKey; label: string; listings: Listing[] }> = [
        { key: 'all', label: 'All', listings: activeFirst(listings) },
        { key: 'active', label: 'Active', listings: active },
        { key: 'exclusive', label: 'Exclusives', listings: exclusive },
        { key: 'sold', label: 'Sold', listings: sold },
    ];

    const tabs = allTabs.filter((tab) => tab.key === 'all' || tab.listings.length > 0);

    const current = tabs.find((tab) => tab.key === filter) ?? tabs[0];

    const lastPage = Math.max(1, Math.ceil(current.listings.length / PER_PAGE));
    // Guard against a stale page when the active tab holds fewer pages.
    const safePage = Math.min(page, lastPage);
    const pageListings = current.listings.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE);

    return (
        <div>
            {listings.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {tabs.map((tab) => (
                        <button
                            key={tab.key}
                            type="button"
                            onClick={() => setFilter(tab.key)}
                            className={`rounded-full px-4 py-1.5 text-sm font-medium tracking-wide transition-colors ${
                                current.key === tab.key ? 'bg-brand-red text-white' : 'bg-slate-100 text-neutral-600 hover:bg-slate-200'
                            }`}
                        >
                            {tab.label}
                            <span className="ml-1.5 opacity-60">{tab.listings.length}</span>
                        </button>
                    ))}
                </div>
            )}

            <div className="mt-8">
                <PropertyGrid listings={pageListings} emptyMessage={`${agentName} has no listings to show here right now.`} />
            </div>

            {lastPage > 1 && (
                <nav className="mt-12 flex items-center justify-between border-t border-slate-200 pt-6" aria-label="Pagination">
                    <PageButton onClick={() => setPage(safePage - 1)} disabled={safePage <= 1} rel="prev">
                        <ChevronLeft className="h-4 w-4" />
                        Previous
                    </PageButton>

                    <span className="text-sm text-neutral-600">
                        Page <span className="text-navy font-medium">{safePage}</span> of {lastPage}
                    </span>

                    <PageButton onClick={() => setPage(safePage + 1)} disabled={safePage >= lastPage} rel="next">
                        Next
                        <ChevronRight className="h-4 w-4" />
                    </PageButton>
                </nav>
            )}
        </div>
    );
}

function PageButton({
    onClick,
    disabled,
    rel,
    children,
}: {
    onClick: () => void;
    disabled: boolean;
    rel: 'prev' | 'next';
    children: React.ReactNode;
}) {
    const className =
        'inline-flex items-center gap-1.5 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-medium text-navy transition-colors hover:border-marine hover:text-marine';

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            rel={rel}
            className={`${className} disabled:hover:text-navy disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-300`}
        >
            {children}
        </button>
    );
}
