import { Link } from '@inertiajs/react';
import { Bath, BedDouble, MapPin, Maximize } from 'lucide-react';
import { type Listing } from './listings';

const statusBadge: Record<Listing['status'], string> = {
    'for-sale': 'For Sale',
    'to-rent': 'To Rent',
    exclusive: 'HFC Exclusive',
    sold: 'Sold',
};

export default function PropertyCard({ listing }: { listing: Listing }) {
    return (
        <Link href={route('property.show', listing.ref ?? listing.id)} className="group block">
            <div className="relative aspect-[8/5] overflow-hidden bg-ink-soft">
                <img
                    src={listing.image}
                    alt={listing.title}
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent" />
                <span className="absolute top-4 left-4 rounded-full border border-white/40 bg-ink/50 px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                    {statusBadge[listing.status]}
                </span>
            </div>
            <div className="mt-5 flex items-start justify-between gap-4">
                <div>
                    <h3 className="line-clamp-1 text-xl font-light text-white transition-colors group-hover:text-marine">
                        {listing.title}
                    </h3>
                    <p className="mt-1 flex items-center gap-1.5 text-sm text-neutral-400">
                        <MapPin className="h-4 w-4 text-neutral-500" />
                        {listing.location}
                    </p>
                </div>
                <p className="shrink-0 text-sm text-marine">{listing.price}</p>
            </div>
            <div className="mt-4 flex items-center gap-5 border-t border-white/10 pt-4 text-xs tracking-wide text-neutral-400">
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
        </Link>
    );
}
