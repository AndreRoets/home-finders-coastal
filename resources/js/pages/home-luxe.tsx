import { sampleListings, type Listing } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, Bath, BedDouble, Maximize, MoveRight } from 'lucide-react';

const stats = [
    { value: '480+', label: 'Coastal homes placed' },
    { value: 'R6.8B', label: 'In property transacted' },
    { value: '21 yrs', label: 'On the coastline' },
    { value: '98%', label: 'Client referral rate' },
];

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

export default function HomeLuxe({ featured = [] }: { featured?: Listing[] }) {
    const listings = featured.length > 0 ? featured : sampleListings;

    return (
        <PublicLayout title="Luxe Living">
            <div className="bg-neutral-950 text-neutral-100">
                {/* Hero */}
                <section className="relative min-h-[92vh] overflow-hidden">
                    <img
                        src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=2400&q=75"
                        alt="Luxury coastal estate at dusk"
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-b from-neutral-950/70 via-neutral-950/40 to-neutral-950" />
                    <div className="absolute inset-0 bg-gradient-to-r from-neutral-950/80 to-transparent" />

                    <div className="relative mx-auto flex min-h-[92vh] max-w-7xl flex-col justify-center px-6 lg:px-8">
                        <p className="flex items-center gap-3 text-xs font-medium tracking-[0.35em] text-amber-200/90 uppercase">
                            <span className="h-px w-10 bg-amber-200/60" />
                            Home Finders Coastal — Private Collection
                        </p>
                        <h1 className="mt-6 max-w-4xl font-serif text-5xl leading-[1.05] font-light tracking-tight text-white sm:text-6xl lg:text-7xl">
                            A rare address on the world’s most coveted coastline.
                        </h1>
                        <p className="mt-7 max-w-xl text-lg leading-relaxed text-neutral-300">
                            We represent a quietly curated portfolio of the coast’s finest residences — many never publicly listed.
                        </p>
                        <div className="mt-10 flex flex-wrap items-center gap-5">
                            <Link
                                href={route('hfc-exclusive')}
                                className="group inline-flex items-center gap-3 rounded-full bg-amber-200 px-7 py-3.5 text-sm font-semibold tracking-wide text-neutral-950 transition-colors hover:bg-amber-100"
                            >
                                View the Collection
                                <MoveRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                            <Link
                                href={route('contact')}
                                className="inline-flex items-center gap-2 text-sm font-medium tracking-wide text-neutral-200 underline-offset-8 transition-colors hover:text-amber-200 hover:underline"
                            >
                                Arrange a private viewing
                            </Link>
                        </div>
                    </div>
                </section>

                {/* Stats band */}
                <section className="border-y border-white/10">
                    <div className="mx-auto grid max-w-7xl grid-cols-2 divide-x divide-white/10 px-6 lg:grid-cols-4 lg:px-8">
                        {stats.map((stat) => (
                            <div key={stat.label} className="px-4 py-10 text-center first:pl-0">
                                <p className="font-serif text-4xl font-light text-amber-200">{stat.value}</p>
                                <p className="mt-2 text-xs tracking-[0.2em] text-neutral-400 uppercase">{stat.label}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Featured residences */}
                <section className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-xs tracking-[0.3em] text-amber-200/80 uppercase">Featured Residences</p>
                            <h2 className="mt-4 font-serif text-4xl font-light text-white sm:text-5xl">Currently in our care</h2>
                        </div>
                        <Link
                            href={route('for-sale')}
                            className="group inline-flex items-center gap-2 text-sm tracking-wide text-neutral-300 transition-colors hover:text-amber-200"
                        >
                            Browse all properties
                            <ArrowUpRight className="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                        </Link>
                    </div>

                    <div className="mt-14 grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                        {listings.slice(0, 6).map((listing) => (
                            <Link key={listing.id} href={route('property.show', listing.ref ?? listing.id)} className="group block">
                                <div className="relative aspect-[4/5] overflow-hidden bg-neutral-900">
                                    <img
                                        src={listing.image}
                                        alt={listing.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                    />
                                    <div className="absolute inset-0 bg-gradient-to-t from-neutral-950/70 via-transparent to-transparent" />
                                    <span className="absolute top-4 left-4 rounded-full border border-amber-200/40 bg-neutral-950/40 px-3 py-1 text-[11px] tracking-[0.2em] text-amber-200 uppercase backdrop-blur">
                                        {listing.location}
                                    </span>
                                </div>
                                <div className="mt-5 flex items-start justify-between gap-4">
                                    <div>
                                        <h3 className="font-serif text-xl font-light text-white transition-colors group-hover:text-amber-200">
                                            {listing.title}
                                        </h3>
                                        <p className="mt-1 text-sm text-neutral-400">{listing.price}</p>
                                    </div>
                                    <ArrowUpRight className="mt-1 h-5 w-5 shrink-0 text-neutral-500 transition-all group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-amber-200" />
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
                </section>

                {/* Collections — editorial split rows */}
                <section className="border-t border-white/10 bg-neutral-900/40">
                    <div className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                        <h2 className="font-serif text-4xl font-light text-white sm:text-5xl">Collections</h2>
                        <div className="mt-14 space-y-px">
                            {collections.map((c) => (
                                <div
                                    key={c.index}
                                    className="group grid items-center gap-8 border-t border-white/10 py-10 first:border-t-0 md:grid-cols-[8rem_1fr_22rem]"
                                >
                                    <span className="font-serif text-5xl font-light text-amber-200/70">{c.index}</span>
                                    <div>
                                        <h3 className="font-serif text-3xl font-light text-white">{c.title}</h3>
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
                    </div>
                </section>

                {/* Closing CTA */}
                <section className="relative overflow-hidden">
                    <img
                        src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=70"
                        alt="Coastline"
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-neutral-950/80" />
                    <div className="relative mx-auto max-w-3xl px-6 py-28 text-center lg:px-8">
                        <h2 className="font-serif text-4xl font-light text-white sm:text-5xl">
                            Considering selling a landmark home?
                        </h2>
                        <p className="mx-auto mt-5 max-w-xl text-neutral-300">
                            Our private valuation is discreet, considered, and informed by two decades on this coastline.
                        </p>
                        <Link
                            href={route('contact')}
                            className="mt-9 inline-flex items-center gap-3 rounded-full border border-amber-200/50 px-8 py-3.5 text-sm font-semibold tracking-wide text-amber-100 transition-colors hover:bg-amber-200 hover:text-neutral-950"
                        >
                            Request a Private Valuation
                            <MoveRight className="h-4 w-4" />
                        </Link>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
