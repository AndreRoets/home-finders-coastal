import AgentAvatar from '@/components/public/agent-avatar';
import BondCalculator from '@/components/public/bond-calculator';
import { type Listing, type ListingStatus } from '@/components/public/listings';
import PropertyGallery from '@/components/public/property-gallery';
import PublicLayout from '@/layouts/public-layout';
import { agentUrl } from '@/lib/routes';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Bath, BedDouble, Car, Mail, MapPin, Maximize, PawPrint, Phone, Play, SquareParking, Trees } from 'lucide-react';

interface PropertyAgent {
    id: number | string;
    name: string;
    designation: string | null;
    phone: string;
    email: string;
    photo: string;
}

/** A per-room detail within a space (e.g. "Bedroom 1" with its own features). */
interface SpaceUnit {
    label: string;
    features: string[];
}

/** A space on the property — Bedroom, Pool, Garage, Parking, Study, etc. */
interface Space {
    type: string;
    /** Integer, a half (2.5), or null when CoreX has no count. */
    count: number | null;
    features: string[];
    description: string | null;
    units: SpaceUnit[];
}

/** Features bucketed by category, in display order. */
interface FeatureGroup {
    group: string;
    label: string;
    items: string[];
}

interface PropertyVideo {
    youtubeId: string | null;
    youtubeUrl: string | null;
    matterportId: string | null;
    virtualTourUrl: string | null;
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
    /** Features grouped by category — prefer over `features` when non-empty. */
    featuresGrouped: FeatureGroup[];
    /** Every space on the property, with counts and optional per-room units. */
    spaces: Space[];
    /** YouTube / Matterport / virtual-tour media; null when none is set. */
    video: PropertyVideo | null;
    images: string[];
    costs: { ratesTaxes: number | null; levy: number | null; specialLevy: number | null };
    /** Primary agent, kept for backwards compatibility — prefer `agents`. */
    agent: PropertyAgent | null;
    /** Every agent attributed to the listing (a property may be co-listed). */
    agents: PropertyAgent[];
}

/** "Pool × 1", "Parking × 2", or just "Study" when there is no count. */
function spaceHeading(space: Space): string {
    return space.count != null ? `${space.type} × ${space.count}` : space.type;
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
    const agents = property.agents?.length ? property.agents : property.agent ? [property.agent] : [];
    const showExclusive = property.exclusive && property.status !== 'exclusive' && property.status !== 'sold';

    // Drop spaces the property doesn't actually have (an explicit count of 0).
    // A null count is kept — it's an unquantified space that still exists.
    const spaces = (property.spaces ?? []).filter((space) => space.count !== 0);

    // Parking is carried as a space, not a top-level field. Sum any "Parking"
    // spaces (a null count means the space exists but is unquantified → 1).
    const parkingCount = spaces.filter((space) => space.type.toLowerCase() === 'parking').reduce((total, space) => total + (space.count ?? 1), 0);

    // Only surface specs that have a meaningful value: skip zero counts and an
    // empty floor area ("—"), so e.g. a property with no garages drops it.
    const specs = [
        ...(property.beds > 0 ? [{ icon: BedDouble, label: 'Beds', value: property.beds as string | number }] : []),
        ...(property.baths > 0 ? [{ icon: Bath, label: 'Baths', value: property.baths }] : []),
        ...(property.garages > 0 ? [{ icon: Car, label: 'Garages', value: property.garages }] : []),
        ...(parkingCount > 0 ? [{ icon: SquareParking, label: 'Parking', value: parkingCount }] : []),
        ...(property.area && property.area !== '—' ? [{ icon: Maximize, label: 'Floor', value: property.area }] : []),
        ...(property.erfSize ? [{ icon: Trees, label: 'Erf', value: property.erfSize }] : []),
    ];

    const costs = [
        { label: 'Rates & taxes', value: property.costs.ratesTaxes },
        { label: 'Levy', value: property.costs.levy },
        { label: 'Special levy', value: property.costs.specialLevy },
    ].filter((c) => c.value != null) as { label: string; value: number }[];

    // Prefer the labelled, grouped features; fall back to the flat list.
    const featureGroups = property.featuresGrouped ?? [];
    const flatFeatures = property.features ?? [];
    const video = property.video;
    const hasVideo = !!(video && (video.youtubeId || video.youtubeUrl || video.matterportId || video.virtualTourUrl));

    return (
        <PublicLayout title={property.title} tone="light">
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    href={route('for-sale')}
                    className="hover:text-marine inline-flex items-center gap-2 text-sm font-medium tracking-wide text-neutral-600 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to listings
                </Link>

                {/* Gallery — cover + thumbnails, opening a full-screen lightbox */}
                <div className="mt-6">
                    <PropertyGallery
                        images={images}
                        title={property.title}
                        badges={
                            <div className="absolute top-4 left-4 flex flex-wrap gap-2">
                                <span
                                    className={cn(
                                        'rounded-full border px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur',
                                        property.status === 'sold' ? 'bg-brand-red border-brand-red' : 'bg-ink/50 border-white/40',
                                    )}
                                >
                                    {statusBadge[property.status]}
                                </span>
                                {showExclusive && (
                                    <span className="border-navy/60 bg-navy/80 rounded-full border px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                                        Exclusive
                                    </span>
                                )}
                            </div>
                        }
                    />
                </div>

                <div className="mt-8 grid gap-10 lg:grid-cols-[2fr_1fr]">
                    {/* Main column */}
                    <div>
                        <p className="text-brand-red text-2xl font-semibold">{property.price}</p>
                        <h1 className="text-navy mt-2 text-3xl font-light tracking-tight sm:text-4xl">{property.title}</h1>
                        <p className="mt-3 flex items-center gap-1.5 text-neutral-600">
                            <MapPin className="h-4 w-4 text-neutral-400" />
                            {property.address ? `${property.address}, ${property.location}` : property.location}
                        </p>
                        {property.propertyType && (
                            <p className="mt-1 text-sm text-neutral-500">{[property.propertyType, property.category].filter(Boolean).join(' · ')}</p>
                        )}

                        {/* Specs — centred, only the values that are present */}
                        {specs.length > 0 && (
                            <div className="mt-8 flex flex-wrap justify-center gap-x-10 gap-y-6 rounded-sm border border-slate-200 bg-slate-50 p-6">
                                {specs.map((spec) => (
                                    <div key={spec.label} className="flex w-16 flex-col items-center gap-1.5 text-center">
                                        <spec.icon className="text-marine h-5 w-5" />
                                        <span className="text-navy text-sm font-medium">{spec.value}</span>
                                        <span className="text-xs tracking-wide text-neutral-500 uppercase">{spec.label}</span>
                                    </div>
                                ))}
                            </div>
                        )}

                        {spaces.length > 0 && (
                            <section className="mt-10">
                                <h2 className="text-navy text-2xl font-light">Spaces &amp; amenities</h2>
                                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                    {spaces.map((space, i) => (
                                        <div key={`${space.type}-${i}`} className="rounded-sm border border-slate-200 bg-slate-50 p-4">
                                            <div className="flex items-baseline justify-between gap-3">
                                                <span className="text-navy text-sm font-medium">{spaceHeading(space)}</span>
                                                {space.features.length > 0 && (
                                                    <span className="text-right text-xs text-neutral-500">{space.features.join(' · ')}</span>
                                                )}
                                            </div>
                                            {space.description && <p className="mt-2 text-sm text-neutral-600">{space.description}</p>}
                                            {space.units.length > 0 && (
                                                <ul className="mt-3 space-y-1.5 border-t border-slate-200 pt-3">
                                                    {space.units.map((unit, u) => (
                                                        <li key={`${unit.label}-${u}`} className="flex flex-wrap items-baseline gap-x-2 text-sm">
                                                            <span className="text-neutral-700">{unit.label}</span>
                                                            {unit.features.length > 0 && (
                                                                <span className="text-xs text-neutral-500">{unit.features.join(' · ')}</span>
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {property.description && (
                            <section className="mt-10">
                                <h2 className="text-navy text-2xl font-light">Description</h2>
                                {property.headline && <p className="mt-3 font-medium text-neutral-700">{property.headline}</p>}
                                <p className="mt-3 leading-relaxed whitespace-pre-line text-neutral-600">{property.description}</p>
                            </section>
                        )}

                        {featureGroups.length > 0 ? (
                            <section className="mt-10">
                                <h2 className="text-navy text-2xl font-light">Features</h2>
                                <div className="mt-4 space-y-6">
                                    {featureGroups.map((group) => (
                                        <div key={group.group || group.label}>
                                            <h3 className="text-marine text-xs font-semibold tracking-[0.2em] uppercase">{group.label}</h3>
                                            <ul className="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                                {group.items.map((item) => (
                                                    <li key={item} className="flex items-center gap-2 text-sm text-neutral-600">
                                                        <span className="bg-marine h-1.5 w-1.5 rounded-full" />
                                                        {item}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        ) : (
                            flatFeatures.length > 0 && (
                                <section className="mt-10">
                                    <h2 className="text-navy text-2xl font-light">Features</h2>
                                    <ul className="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                        {flatFeatures.map((feature) => (
                                            <li key={feature} className="flex items-center gap-2 text-sm text-neutral-600">
                                                <span className="bg-marine h-1.5 w-1.5 rounded-full" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )
                        )}

                        {hasVideo && video && (
                            <section className="mt-10">
                                <h2 className="text-navy text-2xl font-light">Video &amp; virtual tour</h2>
                                <div className="mt-4 space-y-4">
                                    {video.youtubeId ? (
                                        <div className="aspect-video w-full overflow-hidden rounded-sm border border-slate-200 bg-black">
                                            <iframe
                                                src={`https://www.youtube.com/embed/${video.youtubeId}`}
                                                title={`${property.title} — video tour`}
                                                className="h-full w-full"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowFullScreen
                                            />
                                        </div>
                                    ) : (
                                        video.youtubeUrl && (
                                            <a
                                                href={video.youtubeUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="hover:text-marine inline-flex items-center gap-2 text-sm font-medium text-neutral-700 transition-colors"
                                            >
                                                <Play className="h-4 w-4" />
                                                Watch the video tour
                                            </a>
                                        )
                                    )}

                                    {video.matterportId && (
                                        <div className="aspect-video w-full overflow-hidden rounded-sm border border-slate-200 bg-black">
                                            <iframe
                                                src={`https://my.matterport.com/show/?m=${video.matterportId}`}
                                                title={`${property.title} — 3D tour`}
                                                className="h-full w-full"
                                                allow="xr-spatial-tracking; gyroscope; accelerometer; fullscreen"
                                                allowFullScreen
                                            />
                                        </div>
                                    )}

                                    {video.virtualTourUrl && (
                                        <div className="aspect-video w-full overflow-hidden rounded-sm border border-slate-200 bg-black">
                                            <iframe
                                                src={video.virtualTourUrl}
                                                title={`${property.title} — virtual tour`}
                                                className="h-full w-full"
                                                allow="xr-spatial-tracking; gyroscope; accelerometer; fullscreen; autoplay; encrypted-media"
                                                allowFullScreen
                                            />
                                        </div>
                                    )}
                                </div>
                            </section>
                        )}

                        {(costs.length > 0 || property.petFriendly) && (
                            <section className="mt-10">
                                <h2 className="text-navy text-2xl font-light">Monthly costs</h2>
                                <dl className="mt-4 divide-y divide-slate-200 rounded-sm border border-slate-200 bg-slate-50">
                                    {costs.map((cost) => (
                                        <div key={cost.label} className="flex justify-between px-5 py-3.5 text-sm">
                                            <dt className="text-neutral-600">{cost.label}</dt>
                                            <dd className="text-navy font-medium">{formatRand(cost.value)}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {property.petFriendly && (
                                    <p className="text-marine mt-4 inline-flex items-center gap-2 text-sm">
                                        <PawPrint className="h-4 w-4" />
                                        Pet friendly
                                    </p>
                                )}
                            </section>
                        )}
                    </div>

                    {/* Agent sidebar — the card and calculator stick together as one unit */}
                    <aside>
                        <div className="sticky top-24 space-y-6">
                            <div className="rounded-sm border border-slate-200 bg-slate-50 p-6">
                                {agents.length > 0 ? (
                                    <div className="space-y-6">
                                        {agents.map((agent) => (
                                            <div
                                                key={agent.id}
                                                className="[&:not(:first-child)]:border-t [&:not(:first-child)]:border-slate-200 [&:not(:first-child)]:pt-6"
                                            >
                                                <Link href={agentUrl(agent.id)} className="group flex items-center gap-4">
                                                    <AgentAvatar src={agent.photo} alt={agent.name} className="h-16 w-16" />
                                                    <div>
                                                        <p className="text-marine text-xs tracking-[0.2em] uppercase">
                                                            {agents.length > 1 ? 'Co-listed by' : 'Listed by'}
                                                        </p>
                                                        <p className="text-navy group-hover:text-marine text-lg font-light transition-colors">
                                                            {agent.name}
                                                        </p>
                                                        {agent.designation && <p className="text-sm text-neutral-500">{agent.designation}</p>}
                                                    </div>
                                                </Link>
                                                <div className="mt-4 space-y-2.5 text-sm">
                                                    {agent.phone && (
                                                        <a
                                                            href={`tel:${agent.phone.replace(/\s/g, '')}`}
                                                            className="hover:text-marine flex items-center gap-2 text-neutral-600 transition-colors"
                                                        >
                                                            <Phone className="h-4 w-4 text-neutral-400" />
                                                            {agent.phone}
                                                        </a>
                                                    )}
                                                    {agent.email && (
                                                        <a
                                                            href={`mailto:${agent.email}`}
                                                            className="hover:text-marine flex items-center gap-2 text-neutral-600 transition-colors"
                                                        >
                                                            <Mail className="h-4 w-4 text-neutral-400" />
                                                            <span className="truncate">{agent.email}</span>
                                                        </a>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-neutral-600">Contact our office for more information on this property.</p>
                                )}
                                <Link
                                    href={route('contact')}
                                    className="bg-navy hover:bg-navy/90 mt-6 flex w-full items-center justify-center rounded-full px-6 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                                >
                                    Enquire about this property
                                </Link>
                            </div>

                            {/* Bond repayment estimate, pre-filled with this property's price. */}
                            <BondCalculator initialAmount={property.price} compact />
                        </div>
                    </aside>
                </div>
            </div>
        </PublicLayout>
    );
}
