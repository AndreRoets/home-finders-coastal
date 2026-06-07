import { type Listing, type ListingStatus } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { agentUrl } from '@/lib/routes';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Bath, BedDouble, Car, Mail, MapPin, Maximize, PawPrint, Phone, Trees } from 'lucide-react';
import { useState } from 'react';

interface PropertyAgent {
    id: number | string;
    name: string;
    designation: string | null;
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

const statusBadge: Record<ListingStatus, string> = {
    'for-sale': 'For Sale',
    'to-rent': 'To Rent',
    exclusive: 'HFC Exclusive',
    sold: 'Sold',
};

function formatRand(amount: number): string {
    return 'R ' + amount.toLocaleString('en-ZA');
}

export default function PropertyDetail({ property }: { property: Property }) {
    const images = property.images.length > 0 ? property.images : [property.image];
    const [active, setActive] = useState(0);
    const activeImage = images[active] ?? images[0];
    const showExclusive = property.exclusive && property.status !== 'exclusive' && property.status !== 'sold';

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
        <PublicLayout title={property.title} tone="light">
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    href={route('for-sale')}
                    className="inline-flex items-center gap-2 text-sm font-medium tracking-wide text-neutral-600 transition-colors hover:text-marine"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to listings
                </Link>

                {/* Gallery — large active image with a thumbnail strip of every photo */}
                <div className="mt-6">
                    <div className="relative aspect-[4/3] overflow-hidden bg-slate-100 md:aspect-[16/9]">
                        <img src={activeImage} alt={property.title} className="h-full w-full object-cover" />
                        <div className="absolute top-4 left-4 flex flex-wrap gap-2">
                            <span className="rounded-full border border-white/40 bg-ink/50 px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                                {statusBadge[property.status]}
                            </span>
                            {showExclusive && (
                                <span className="rounded-full border border-marine/60 bg-marine/80 px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                                    Exclusive
                                </span>
                            )}
                        </div>
                    </div>
                    {images.length > 1 && (
                        <div className="mt-3 grid grid-cols-4 gap-3 sm:grid-cols-5 lg:grid-cols-6">
                            {images.map((src, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    onClick={() => setActive(i)}
                                    aria-label={`View image ${i + 1} of ${images.length}`}
                                    aria-current={i === active}
                                    className={cn(
                                        'relative aspect-[4/3] overflow-hidden rounded-sm bg-slate-100 ring-2 transition',
                                        i === active ? 'ring-marine' : 'ring-transparent hover:ring-slate-300',
                                    )}
                                >
                                    <img src={src} alt={`${property.title} ${i + 1}`} loading="lazy" className="h-full w-full object-cover" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mt-8 grid gap-10 lg:grid-cols-[2fr_1fr]">
                    {/* Main column */}
                    <div>
                        <p className="text-2xl text-marine">{property.price}</p>
                        <h1 className="mt-2 text-3xl font-light tracking-tight text-navy sm:text-4xl">{property.title}</h1>
                        <p className="mt-3 flex items-center gap-1.5 text-neutral-600">
                            <MapPin className="h-4 w-4 text-neutral-400" />
                            {property.address ? `${property.address}, ${property.location}` : property.location}
                        </p>
                        {property.propertyType && (
                            <p className="mt-1 text-sm text-neutral-500">{[property.propertyType, property.category].filter(Boolean).join(' · ')}</p>
                        )}

                        {/* Specs */}
                        <div className="mt-8 grid grid-cols-2 gap-4 rounded-sm border border-slate-200 bg-slate-50 p-6 sm:grid-cols-3 lg:grid-cols-5">
                            {specs.map((spec) => (
                                <div key={spec.label} className="flex flex-col items-center gap-1.5 text-center">
                                    <spec.icon className="h-5 w-5 text-marine" />
                                    <span className="text-sm font-medium text-navy">{spec.value}</span>
                                    <span className="text-xs tracking-wide text-neutral-500 uppercase">{spec.label}</span>
                                </div>
                            ))}
                        </div>

                        {property.description && (
                            <section className="mt-10">
                                <h2 className="text-2xl font-light text-navy">Description</h2>
                                {property.headline && <p className="mt-3 font-medium text-neutral-700">{property.headline}</p>}
                                <p className="mt-3 leading-relaxed whitespace-pre-line text-neutral-600">{property.description}</p>
                            </section>
                        )}

                        {property.features.length > 0 && (
                            <section className="mt-10">
                                <h2 className="text-2xl font-light text-navy">Features</h2>
                                <ul className="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                    {property.features.map((feature) => (
                                        <li key={feature} className="flex items-center gap-2 text-sm text-neutral-600">
                                            <span className="h-1.5 w-1.5 rounded-full bg-marine" />
                                            {feature}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}

                        {(costs.length > 0 || property.petFriendly) && (
                            <section className="mt-10">
                                <h2 className="text-2xl font-light text-navy">Monthly costs</h2>
                                <dl className="mt-4 divide-y divide-slate-200 rounded-sm border border-slate-200 bg-slate-50">
                                    {costs.map((cost) => (
                                        <div key={cost.label} className="flex justify-between px-5 py-3.5 text-sm">
                                            <dt className="text-neutral-600">{cost.label}</dt>
                                            <dd className="font-medium text-navy">{formatRand(cost.value)}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {property.petFriendly && (
                                    <p className="mt-4 inline-flex items-center gap-2 text-sm text-marine">
                                        <PawPrint className="h-4 w-4" />
                                        Pet friendly
                                    </p>
                                )}
                            </section>
                        )}
                    </div>

                    {/* Agent sidebar */}
                    <aside>
                        <div className="sticky top-24 rounded-sm border border-slate-200 bg-slate-50 p-6">
                            {property.agent ? (
                                <>
                                    <Link href={agentUrl(property.agent.id)} className="group flex items-center gap-4">
                                        <img src={property.agent.photo} alt={property.agent.name} className="h-16 w-16 rounded-full object-cover" />
                                        <div>
                                            <p className="text-xs tracking-[0.2em] text-marine uppercase">Listed by</p>
                                            <p className="text-lg font-light text-navy transition-colors group-hover:text-marine">{property.agent.name}</p>
                                            {property.agent.designation && (
                                                <p className="text-sm text-neutral-500">{property.agent.designation}</p>
                                            )}
                                        </div>
                                    </Link>
                                    <div className="mt-6 space-y-2.5 text-sm">
                                        {property.agent.phone && (
                                            <a
                                                href={`tel:${property.agent.phone.replace(/\s/g, '')}`}
                                                className="flex items-center gap-2 text-neutral-600 transition-colors hover:text-marine"
                                            >
                                                <Phone className="h-4 w-4 text-neutral-400" />
                                                {property.agent.phone}
                                            </a>
                                        )}
                                        {property.agent.email && (
                                            <a
                                                href={`mailto:${property.agent.email}`}
                                                className="flex items-center gap-2 text-neutral-600 transition-colors hover:text-marine"
                                            >
                                                <Mail className="h-4 w-4 text-neutral-400" />
                                                <span className="truncate">{property.agent.email}</span>
                                            </a>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-neutral-600">Contact our office for more information on this property.</p>
                            )}
                            <Link
                                href={route('contact')}
                                className="mt-6 flex w-full items-center justify-center rounded-full bg-brand-red px-6 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-brand-red-bright"
                            >
                                Enquire about this property
                            </Link>
                            {property.ref && <p className="mt-4 text-center text-xs tracking-wide text-neutral-500">Ref: {property.ref}</p>}
                        </div>
                    </aside>
                </div>
            </div>
        </PublicLayout>
    );
}
