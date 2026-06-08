import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Bath, BedDouble, MapPin, Maximize } from 'lucide-react';
import ListingAgents from './listing-agents';
import { type Listing } from './listings';

const statusBadge: Record<Listing['status'], string> = {
    'for-sale': 'For Sale',
    'to-rent': 'To Rent',
    exclusive: 'HFC Exclusive',
    sold: 'Sold',
};

export default function PropertyCard({ listing }: { listing: Listing }) {
    const href = route('property.show', listing.slug ?? listing.ref ?? listing.id);

    return (
        // Not an <a>: the card holds two independent links (property + agent),
        // so nesting anchors would be invalid. `group` still drives the hover.
        <div className="group block">
            <Link href={href} className="block">
                <div className="relative aspect-[8/5] overflow-hidden bg-slate-100">
                    <img
                        src={listing.image}
                        alt={listing.title}
                        loading="lazy"
                        className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                    />
                    <div className="from-ink/70 absolute inset-0 bg-gradient-to-t via-transparent to-transparent" />
                    <div className="absolute top-4 left-4 flex flex-wrap gap-2">
                        <span
                            className={cn(
                                'rounded-full border px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur',
                                listing.status === 'sold' ? 'bg-brand-red border-brand-red' : 'bg-ink/50 border-white/40',
                            )}
                        >
                            {statusBadge[listing.status]}
                        </span>
                        {listing.exclusive && listing.status !== 'exclusive' && listing.status !== 'sold' && (
                            <span className="border-navy/60 bg-navy/80 rounded-full border px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                                Exclusive
                            </span>
                        )}
                    </div>
                </div>
            </Link>
            <Link href={href} className="mt-5 flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-navy group-hover:text-navy/70 line-clamp-1 text-xl font-light transition-colors">{listing.title}</h3>
                    <p className="mt-1 flex items-center gap-1.5 text-sm text-neutral-500">
                        <MapPin className="h-4 w-4 text-neutral-400" />
                        {listing.location}
                    </p>
                </div>
                <p className="text-brand-red shrink-0 text-sm font-semibold">{listing.price}</p>
            </Link>
            <div className="mt-4 flex items-center gap-5 border-t border-slate-200 pt-4 text-xs tracking-wide text-neutral-500">
                <span className="flex items-center gap-1.5">
                    <BedDouble className="h-4 w-4" />
                    {listing.beds} beds
                </span>
                <span className="flex items-center gap-1.5">
                    <Bath className="h-4 w-4" />
                    {listing.baths} baths
                </span>
                <span className="flex items-center gap-1.5">
                    <Maximize className="h-4 w-4" />
                    {listing.area}
                </span>
            </div>
            <ListingAgents listing={listing} />
        </div>
    );
}
