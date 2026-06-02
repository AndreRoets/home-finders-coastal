import { type Listing } from './listings';
import PropertyCard from './property-card';

interface PropertyGridProps {
    listings: Listing[];
    emptyMessage?: string;
}

export default function PropertyGrid({ listings, emptyMessage = 'No properties to show yet.' }: PropertyGridProps) {
    if (listings.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">{emptyMessage}</div>
        );
    }

    return (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {listings.map((listing) => (
                <PropertyCard key={listing.id} listing={listing} />
            ))}
        </div>
    );
}
