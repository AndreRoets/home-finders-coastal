import { cn } from '@/lib/utils';
import { Bath, BedDouble, Maximize, MapPin } from 'lucide-react';
import { type Listing } from './listings';

const statusBadge: Record<Listing['status'], { label: string; className: string }> = {
    'for-sale': { label: 'For Sale', className: 'bg-sky-600 text-white' },
    'to-rent': { label: 'To Rent', className: 'bg-emerald-600 text-white' },
    exclusive: { label: 'HFC Exclusive', className: 'bg-amber-500 text-slate-900' },
    sold: { label: 'Sold', className: 'bg-slate-700 text-white' },
};

export default function PropertyCard({ listing }: { listing: Listing }) {
    const badge = statusBadge[listing.status];

    return (
        <article className="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md">
            <div className="relative aspect-[4/3] overflow-hidden bg-slate-100">
                <img
                    src={listing.image}
                    alt={listing.title}
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                />
                <span className={cn('absolute top-3 left-3 rounded-full px-3 py-1 text-xs font-semibold', badge.className)}>{badge.label}</span>
            </div>
            <div className="p-5">
                <p className="text-lg font-semibold text-sky-700">{listing.price}</p>
                <h3 className="mt-1 line-clamp-1 font-semibold text-slate-900">{listing.title}</h3>
                <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                    <MapPin className="h-4 w-4 text-slate-400" />
                    {listing.location}
                </p>
                <div className="mt-4 flex items-center gap-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
                    <span className="flex items-center gap-1.5">
                        <BedDouble className="h-4 w-4 text-slate-400" />
                        {listing.beds} beds
                    </span>
                    <span className="flex items-center gap-1.5">
                        <Bath className="h-4 w-4 text-slate-400" />
                        {listing.baths} baths
                    </span>
                    <span className="flex items-center gap-1.5">
                        <Maximize className="h-4 w-4 text-slate-400" />
                        {listing.area}
                    </span>
                </div>
            </div>
        </article>
    );
}
