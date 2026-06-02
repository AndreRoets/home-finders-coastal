import { type Listing, type ListingStatus } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Bath, BedDouble, Car, Mail, MapPin, Maximize, PawPrint, Phone, Trees } from 'lucide-react';

interface PropertyAgent {
    name: string;
    phone: string;
    email: string;
    photo: string;
}

interface Property extends Listing {
    headline: string | null;
    description: string | null;
    propertyType: string | null;
    category: string | null;
    listingType: string | null;
    garages: number;
    erfSize: string | null;
    petFriendly: boolean;
    address: string | null;
    features: string[];
    images: string[];
    costs: { ratesTaxes: number | null; levy: number | null; specialLevy: number | null };
    agent: PropertyAgent | null;
}

const statusBadge: Record<ListingStatus, { label: string; className: string }> = {
    'for-sale': { label: 'For Sale', className: 'bg-sky-600 text-white' },
    'to-rent': { label: 'To Rent', className: 'bg-emerald-600 text-white' },
    exclusive: { label: 'HFC Exclusive', className: 'bg-amber-500 text-slate-900' },
    sold: { label: 'Sold', className: 'bg-slate-700 text-white' },
};

function formatRand(amount: number): string {
    return 'R ' + amount.toLocaleString('en-ZA');
}

export default function PropertyDetail({ property }: { property: Property }) {
    const badge = statusBadge[property.status];
    const images = property.images.length > 0 ? property.images : [property.image];
    const [cover, ...rest] = images;

    const specs = [
        { icon: BedDouble, label: 'Beds', value: property.beds },
        { icon: Bath, label: 'Baths', value: property.baths },
        { icon: Car, label: 'Garages', value: property.garages },
        { icon: Maximize, label: 'Floor', value: property.area },
        ...(property.erfSize ? [{ icon: Trees, label: 'Erf', value: property.erfSize }] : []),
    ];

    const costs = [
        { label: 'Rates & taxes', value: property.costs.ratesTaxes },
        { label: 'Levy', value: property.costs.levy },
        { label: 'Special levy', value: property.costs.specialLevy },
    ].filter((c) => c.value != null) as { label: string; value: number }[];

    return (
        <PublicLayout title={property.title}>
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Link href={route('for-sale')} className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-sky-700">
                    <ArrowLeft className="h-4 w-4" />
                    Back to listings
                </Link>

                {/* Gallery */}
                <div className="mt-6 grid gap-3 md:grid-cols-[2fr_1fr]">
                    <div className="relative aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 md:aspect-[16/10]">
                        <img src={cover} alt={property.title} className="h-full w-full object-cover" />
                        <span className={cn('absolute top-4 left-4 rounded-full px-3 py-1 text-xs font-semibold', badge.className)}>
                            {badge.label}
                        </span>
                    </div>
                    {rest.length > 0 && (
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-1">
                            {rest.slice(0, 2).map((src, i) => (
                                <div key={i} className="aspect-[4/3] overflow-hidden rounded-xl bg-slate-100">
                                    <img src={src} alt={`${property.title} ${i + 2}`} className="h-full w-full object-cover" />
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mt-8 grid gap-10 lg:grid-cols-[2fr_1fr]">
                    {/* Main column */}
                    <div>
                        <p className="text-2xl font-bold text-sky-700">{property.price}</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{property.title}</h1>
                        <p className="mt-2 flex items-center gap-1.5 text-slate-500">
                            <MapPin className="h-4 w-4 text-slate-400" />
                            {property.address ? `${property.address}, ${property.location}` : property.location}
                        </p>
                        {property.propertyType && (
                            <p className="mt-1 text-sm text-slate-500">{[property.propertyType, property.category].filter(Boolean).join(' · ')}</p>
                        )}

                        {/* Specs */}
                        <div className="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-3 lg:grid-cols-5">
                            {specs.map((spec) => (
                                <div key={spec.label} className="flex flex-col items-center gap-1 text-center">
                                    <spec.icon className="h-5 w-5 text-sky-600" />
                                    <span className="text-sm font-semibold text-slate-900">{spec.value}</span>
                                    <span className="text-xs text-slate-500">{spec.label}</span>
                                </div>
                            ))}
                        </div>

                        {property.description && (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold text-slate-900">Description</h2>
                                {property.headline && <p className="mt-2 font-medium text-slate-700">{property.headline}</p>}
                                <p className="mt-2 leading-relaxed whitespace-pre-line text-slate-600">{property.description}</p>
                            </section>
                        )}

                        {property.features.length > 0 && (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold text-slate-900">Features</h2>
                                <ul className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {property.features.map((feature) => (
                                        <li key={feature} className="flex items-center gap-2 text-sm text-slate-600">
                                            <span className="h-1.5 w-1.5 rounded-full bg-sky-500" />
                                            {feature}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {(costs.length > 0 || property.petFriendly) && (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold text-slate-900">Monthly costs</h2>
                                <dl className="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                                    {costs.map((cost) => (
                                        <div key={cost.label} className="flex justify-between px-5 py-3 text-sm">
                                            <dt className="text-slate-500">{cost.label}</dt>
                                            <dd className="font-medium text-slate-900">{formatRand(cost.value)}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {property.petFriendly && (
                                    <p className="mt-3 inline-flex items-center gap-2 text-sm text-emerald-700">
                                        <PawPrint className="h-4 w-4" />
                                        Pet friendly
                                    </p>
                                )}
                            </section>
                        )}
                    </div>

                    {/* Agent sidebar */}
                    <aside>
                        <div className="sticky top-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            {property.agent ? (
                                <>
                                    <div className="flex items-center gap-4">
                                        <img src={property.agent.photo} alt={property.agent.name} className="h-16 w-16 rounded-full object-cover" />
                                        <div>
                                            <p className="text-xs tracking-wide text-slate-400 uppercase">Listed by</p>
                                            <p className="font-semibold text-slate-900">{property.agent.name}</p>
                                        </div>
                                    </div>
                                    <div className="mt-5 space-y-2 text-sm">
                                        {property.agent.phone && (
                                            <a
                                                href={`tel:${property.agent.phone.replace(/\s/g, '')}`}
                                                className="flex items-center gap-2 text-slate-600 hover:text-sky-700"
                                            >
                                                <Phone className="h-4 w-4 text-slate-400" />
                                                {property.agent.phone}
                                            </a>
                                        )}
                                        {property.agent.email && (
                                            <a
                                                href={`mailto:${property.agent.email}`}
                                                className="flex items-center gap-2 text-slate-600 hover:text-sky-700"
                                            >
                                                <Mail className="h-4 w-4 text-slate-400" />
                                                <span className="truncate">{property.agent.email}</span>
                                            </a>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-slate-500">Contact our office for more information on this property.</p>
                            )}
                            <Link
                                href={route('contact')}
                                className="mt-6 flex w-full items-center justify-center rounded-lg bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-sky-700"
                            >
                                Enquire about this property
                            </Link>
                            {property.ref && <p className="mt-4 text-center text-xs text-slate-400">Ref: {property.ref}</p>}
                        </div>
                    </aside>
                </div>
            </div>
        </PublicLayout>
    );
}
