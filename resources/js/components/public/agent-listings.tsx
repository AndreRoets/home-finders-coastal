import { useState } from 'react';
import { type Listing } from './listings';
import PropertyGrid from './property-grid';

type FilterKey = 'all' | 'active' | 'exclusive' | 'sold';

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
                <PropertyGrid listings={current.listings} emptyMessage={`${agentName} has no listings to show here right now.`} />
            </div>
        </div>
    );
}
