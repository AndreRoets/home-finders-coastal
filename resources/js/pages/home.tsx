import { sampleListings, type Listing } from '@/components/public/listings';
import PropertySearch, { type SearchFacets } from '@/components/public/property-search';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, Bath, BedDouble, ChevronDown, Maximize, MoveRight } from 'lucide-react';
import { type ReactNode, useEffect, useRef, useState } from 'react';

const categories = [
    { label: 'For Sale', routeName: 'for-sale', image: 'photo-1568605114967-8130f3a36994' },
    { label: 'To Rent', routeName: 'to-rent', image: 'photo-1502672260266-1c1ef2d93688' },
    { label: 'Exclusives', routeName: 'hfc-exclusive', image: 'photo-1512917774080-9991f1c4c750' },
    { label: 'Sold by us', routeName: 'sold', image: 'photo-1493809842364-78817add7ffb' },
];

interface Category {
    label: string;
    routeName: string;
    image: string;
}

/**
 * A category square: the image slowly drifts (Ken Burns) and a marine outline
 * draws itself around the whole square on hover.
 */
function CategoryTile({ category }: { category: Category }) {
    const { label, routeName, image } = category;

    return (
        <Link href={route(routeName)} className="group relative block aspect-square rounded-sm">
            <div className="absolute inset-0 overflow-hidden rounded-sm">
                <img
                    src={`https://images.unsplash.com/${image}?auto=format&fit=crop&w=900&q=70`}
                    alt={label}
                    loading="lazy"
                    className="h-full w-full animate-kenburns object-cover will-change-transform"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-ink/90 via-ink/25 to-transparent transition-opacity group-hover:from-ink" />
            </div>

            {/* Outline that traces around the square on hover */}
            <svg
                className="pointer-events-none absolute inset-0 h-full w-full text-white"
                viewBox="0 0 100 100"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <rect
                    x="0.6"
                    y="0.6"
                    width="98.8"
                    height="98.8"
                    rx="1.5"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    vectorEffect="non-scaling-stroke"
                    pathLength={100}
                    className="[stroke-dasharray:100] [stroke-dashoffset:100] transition-[stroke-dashoffset] duration-500 ease-out group-hover:[stroke-dashoffset:0]"
                />
            </svg>

            <div className="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 p-5">
                <h3 className="text-xl font-light text-white sm:text-2xl">{label}</h3>
                <ArrowUpRight className="h-5 w-5 shrink-0 text-white transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
            </div>
        </Link>
    );
}

const collections = [
    {
        index: '01',
        title: 'Oceanfront',
        copy: 'Homes where the Atlantic is your front garden — infinity edges, glass walls, salt air.',
        image: 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1400&q=70',
    },
    {
        index: '02',
        title: 'Clifftop Estates',
        copy: 'Elevated landmark residences with panoramic, protected views that cannot be built out.',
        image: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1400&q=70',
    },
    {
        index: '03',
        title: 'Village Retreats',
        copy: 'Restored cottages and quiet sanctuaries tucked into the coast’s most storied villages.',
        image: 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?auto=format&fit=crop&w=1400&q=70',
    },
];

/**
 * Fades and lifts its children into view the first time they are scrolled near.
 */
function Reveal({ children, className }: { children: ReactNode; className?: string }) {
    const ref = useRef<HTMLDivElement>(null);
    const [shown, setShown] = useState(false);

    useEffect(() => {
        const element = ref.current;
        if (!element) {
            return;
        }
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setShown(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15 },
        );
        observer.observe(element);
        return () => observer.disconnect();
    }, []);

    return (
        <div
            ref={ref}
            className={cn('transition-all duration-700 ease-out', shown ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0', className)}
        >
            {children}
        </div>
    );
}

export default function Home({ featured = [], filters }: { featured?: Listing[]; filters?: SearchFacets }) {
    const listings = featured.length > 0 ? featured : sampleListings;
    const [scrollY, setScrollY] = useState(0);

    useEffect(() => {
        let frame = 0;
        const onScroll = () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => setScrollY(window.scrollY));
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => {
            window.removeEventListener('scroll', onScroll);
            cancelAnimationFrame(frame);
        };
    }, []);

    return (
        <PublicLayout title="Coastal Property Specialists">
            {/* Hero */}
            <section className="relative min-h-[92vh] overflow-hidden">
                <img
                    src="https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=2400&q=80"
                    alt="Modern oceanfront villa with infinity pool overlooking the sea"
                    className="absolute inset-0 h-full w-full object-cover will-change-transform"
                    style={{ transform: `translate3d(0, ${scrollY * 0.1}px, 0) scale(1.25)` }}
                />
                <div className="absolute inset-0 bg-gradient-to-b from-ink/65 via-ink/40 to-ink" />
                <div className="absolute inset-0 bg-gradient-to-r from-ink/80 to-transparent" />

                <div
                    className="relative mx-auto flex min-h-[92vh] max-w-7xl flex-col justify-center px-6 lg:px-8"
                    style={{ opacity: Math.max(0, 1 - scrollY / 600), transform: `translateY(${scrollY * 0.15}px)` }}
                >
                    <h1 className="max-w-4xl text-5xl leading-[1.05] font-light tracking-tight text-white sm:text-6xl lg:text-7xl">
                        Your Journey, Our Expertise
                    </h1>
                    {filters && (
                        <div className="mt-16 w-full max-w-3xl">
                            <PropertySearch filters={filters} mode="sale" variant="hero" />
                        </div>
                    )}
                </div>

                {/* Scroll cue */}
                <div
                    className="pointer-events-none absolute inset-x-0 bottom-8 flex flex-col items-center gap-2 text-white/70"
                    style={{ opacity: Math.max(0, 1 - scrollY / 250) }}
                >
                    <span className="text-[11px] tracking-[0.3em] uppercase">Scroll</span>
                    <ChevronDown className="h-5 w-5 animate-bounce" />
                </div>
            </section>

            {/* Browse by category */}
            <section className="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {categories.map((category) => (
                        <CategoryTile key={category.label} category={category} />
                    ))}
                </div>
            </section>

            {/* Featured residences */}
            <section className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                <Reveal>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-xs tracking-[0.3em] text-marine/80 uppercase">Featured Residences</p>
                        <h2 className="mt-4 text-4xl font-light text-white sm:text-5xl">Currently in our care</h2>
                    </div>
                    <Link
                        href={route('for-sale')}
                        className="group inline-flex items-center gap-2 text-sm tracking-wide text-neutral-300 transition-colors hover:text-marine"
                    >
                        Browse all properties
                        <ArrowUpRight className="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                    </Link>
                </div>

                <div className="mt-14 grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                    {listings.slice(0, 6).map((listing) => (
                        <Link key={listing.id} href={route('property.show', listing.ref ?? listing.id)} className="group block">
                            <div className="relative aspect-[4/5] overflow-hidden bg-ink-soft">
                                <img
                                    src={listing.image}
                                    alt={listing.title}
                                    loading="lazy"
                                    className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent" />
                                <span className="absolute top-4 left-4 rounded-full border border-marine/40 bg-ink/50 px-3 py-1 text-[11px] tracking-[0.2em] text-marine uppercase backdrop-blur">
                                    {listing.location}
                                </span>
                            </div>
                            <div className="mt-5 flex items-start justify-between gap-4">
                                <div>
                                    <h3 className="text-xl font-light text-white transition-colors group-hover:text-marine">
                                        {listing.title}
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-400">{listing.price}</p>
                                </div>
                                <ArrowUpRight className="mt-1 h-5 w-5 shrink-0 text-neutral-500 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-marine" />
                            </div>
                            <div className="mt-4 flex items-center gap-5 border-t border-white/10 pt-4 text-xs tracking-wide text-neutral-400">
                                <span className="flex items-center gap-1.5">
                                    <BedDouble className="h-4 w-4" /> {listing.beds}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <Bath className="h-4 w-4" /> {listing.baths}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <Maximize className="h-4 w-4" /> {listing.area}
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
                </Reveal>
            </section>

            {/* Collections — editorial split rows */}
            <section className="border-t border-white/10 bg-ink-soft/40">
                <Reveal className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                    <h2 className="text-4xl font-light text-white sm:text-5xl">Collections</h2>
                    <div className="mt-14 space-y-px">
                        {collections.map((c) => (
                            <div
                                key={c.index}
                                className="group grid items-center gap-8 border-t border-white/10 py-10 first:border-t-0 md:grid-cols-[8rem_1fr_22rem]"
                            >
                                <span className="text-5xl font-light text-brand-red/80">{c.index}</span>
                                <div>
                                    <h3 className="text-3xl font-light text-white">{c.title}</h3>
                                    <p className="mt-3 max-w-md text-neutral-400">{c.copy}</p>
                                </div>
                                <div className="relative aspect-[16/10] overflow-hidden rounded-sm">
                                    <img
                                        src={c.image}
                                        alt={c.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    />
                                </div>
                            </div>
                        ))}
                    </div>
                </Reveal>
            </section>

            {/* Closing CTA */}
            <section className="relative overflow-hidden">
                <img
                    src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=70"
                    alt="Coastline"
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-ink/85" />
                <Reveal className="relative mx-auto max-w-3xl px-6 py-28 text-center lg:px-8">
                    <h2 className="text-4xl font-light text-white sm:text-5xl">Considering selling a landmark home?</h2>
                    <p className="mx-auto mt-5 max-w-xl text-neutral-300">
                        Our private valuation is discreet, considered, and informed by two decades on this coastline.
                    </p>
                    <Link
                        href={route('contact')}
                        className="mt-9 inline-flex items-center gap-3 rounded-full bg-brand-red px-8 py-3.5 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-brand-red-bright"
                    >
                        Request a Private Valuation
                        <MoveRight className="h-4 w-4" />
                    </Link>
                </Reveal>
            </section>
        </PublicLayout>
    );
}
