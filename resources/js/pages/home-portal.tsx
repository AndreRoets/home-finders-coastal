import { sampleListings, type Listing } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Bath, BedDouble, Heart, Home as HomeIcon, MapPin, Maximize, Search, Star, TrendingUp } from 'lucide-react';
import { useState } from 'react';

const searchTabs = [
    { key: 'buy', label: 'Buy', route: 'for-sale' },
    { key: 'rent', label: 'Rent', route: 'to-rent' },
    { key: 'sold', label: 'Sold', route: 'sold' },
] as const;

const areas = [
    { name: 'Camps Bay', count: 42, image: 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=800&q=60' },
    { name: 'Sea Point', count: 68, image: 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=60' },
    { name: 'Hout Bay', count: 31, image: 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=800&q=60' },
    { name: 'Llandudno', count: 17, image: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=800&q=60' },
];

const stats = [
    { value: '1,200+', label: 'Active listings' },
    { value: '4.9/5', label: 'Average client rating' },
    { value: '18 days', label: 'Average time to offer' },
    { value: '32', label: 'Local specialist agents' },
];

const testimonials = [
    { quote: 'They sold our home in under three weeks, above asking. The whole process felt effortless.', name: 'Megan & Paul R.', area: 'Sold in Hout Bay' },
    { quote: 'The search tools made it so easy to find exactly what we wanted on the coast. Brilliant team.', name: 'Sipho M.', area: 'Bought in Sea Point' },
    { quote: 'Honest, responsive and genuinely local. We never felt like just another sale.', name: 'The Bekker Family', area: 'Bought in Kommetjie' },
];

export default function HomePortal({ featured = [] }: { featured?: Listing[] }) {
    const listings = featured.length > 0 ? featured : sampleListings;
    const [activeTab, setActiveTab] = useState<(typeof searchTabs)[number]['key']>('buy');
    const activeRoute = searchTabs.find((t) => t.key === activeTab)?.route ?? 'for-sale';

    return (
        <PublicLayout title="Search Coastal Property">
            {/* Hero with tabbed search */}
            <section className="relative">
                <img
                    src="https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=2200&q=70"
                    alt="Coastal homes"
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-b from-slate-900/70 to-slate-900/85" />
                <div className="relative mx-auto max-w-4xl px-4 py-24 text-center sm:px-6 lg:py-32">
                    <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Search every coastal home in one place
                    </h1>
                    <p className="mx-auto mt-4 max-w-xl text-lg text-slate-200">
                        Live listings, instant valuations, and local experts — all on one trusted platform.
                    </p>

                    <div className="mx-auto mt-10 max-w-2xl">
                        <div className="flex justify-center gap-1 rounded-t-xl">
                            {searchTabs.map((tab) => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`rounded-t-lg px-6 py-2.5 text-sm font-semibold transition-colors ${
                                        activeTab === tab.key ? 'bg-white text-slate-900' : 'bg-white/10 text-white hover:bg-white/20'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                        <div className="flex flex-col gap-2 rounded-xl rounded-tl-none bg-white p-2.5 shadow-2xl sm:flex-row">
                            <div className="flex flex-1 items-center gap-2 px-3">
                                <Search className="h-5 w-5 text-slate-400" />
                                <input
                                    type="text"
                                    placeholder="Suburb, town, or postal code…"
                                    className="w-full bg-transparent py-2.5 text-slate-900 outline-none placeholder:text-slate-400"
                                />
                            </div>
                            <Link
                                href={route(activeRoute)}
                                className="inline-flex items-center justify-center rounded-lg bg-teal-600 px-7 py-2.5 font-semibold text-white transition-colors hover:bg-teal-700"
                            >
                                Search {searchTabs.find((t) => t.key === activeTab)?.label}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Instant estimate widget (CMA lead capture) */}
            <section className="relative z-10 mx-auto -mt-10 max-w-5xl px-4 sm:px-6 lg:px-8">
                <div className="flex flex-col items-center gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:flex-row sm:p-8">
                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                        <TrendingUp className="h-7 w-7" />
                    </div>
                    <div className="flex-1 text-center sm:text-left">
                        <h2 className="text-lg font-bold text-slate-900">What’s your home worth?</h2>
                        <p className="mt-1 text-sm text-slate-500">Get a free, instant estimate based on recent coastal sales.</p>
                    </div>
                    <div className="flex w-full max-w-sm gap-2">
                        <input
                            type="text"
                            placeholder="Enter your address"
                            className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30"
                        />
                        <Link
                            href={route('contact')}
                            className="inline-flex shrink-0 items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-800"
                        >
                            Estimate
                        </Link>
                    </div>
                </div>
            </section>

            {/* Browse by area */}
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="flex items-end justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Browse by area</h2>
                        <p className="mt-2 text-slate-600">Explore the coast’s most popular neighbourhoods.</p>
                    </div>
                </div>
                <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {areas.map((area) => (
                        <Link
                            key={area.name}
                            href={route('for-sale')}
                            className="group relative overflow-hidden rounded-xl"
                        >
                            <div className="aspect-[4/3] overflow-hidden">
                                <img
                                    src={area.image}
                                    alt={area.name}
                                    loading="lazy"
                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                />
                            </div>
                            <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent" />
                            <div className="absolute right-0 bottom-0 left-0 p-4">
                                <p className="font-semibold text-white">{area.name}</p>
                                <p className="text-sm text-slate-300">{area.count} listings</p>
                            </div>
                        </Link>
                    ))}
                </div>
            </section>

            {/* Featured listings with save */}
            <section className="border-y border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                    <div className="flex items-end justify-between">
                        <h2 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Featured listings</h2>
                        <Link href={route('for-sale')} className="text-sm font-semibold text-teal-700 hover:text-teal-800">
                            View all &rarr;
                        </Link>
                    </div>
                    <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {listings.slice(0, 6).map((listing) => (
                            <Link
                                key={listing.id}
                                href={route('property.show', listing.ref ?? listing.id)}
                                className="group block overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-lg"
                            >
                                <div className="relative aspect-[4/3] overflow-hidden bg-slate-100">
                                    <img
                                        src={listing.image}
                                        alt={listing.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    />
                                    <span className="absolute top-3 left-3 rounded-md bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                                        Featured
                                    </span>
                                    <span className="absolute top-3 right-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-slate-400 shadow-sm transition-colors hover:text-rose-500">
                                        <Heart className="h-4 w-4" />
                                    </span>
                                </div>
                                <div className="p-5">
                                    <p className="text-lg font-bold text-slate-900">{listing.price}</p>
                                    <h3 className="mt-1 line-clamp-1 font-medium text-slate-700">{listing.title}</h3>
                                    <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                                        <MapPin className="h-4 w-4 text-slate-400" />
                                        {listing.location}
                                    </p>
                                    <div className="mt-4 flex items-center gap-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
                                        <span className="flex items-center gap-1.5">
                                            <BedDouble className="h-4 w-4 text-slate-400" /> {listing.beds}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <Bath className="h-4 w-4 text-slate-400" /> {listing.baths}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <Maximize className="h-4 w-4 text-slate-400" /> {listing.area}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* Trust stats */}
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="grid grid-cols-2 gap-8 rounded-2xl bg-slate-900 px-8 py-12 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <div key={stat.label} className="text-center">
                            <p className="text-3xl font-bold text-teal-400 sm:text-4xl">{stat.value}</p>
                            <p className="mt-2 text-sm text-slate-400">{stat.label}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Testimonials */}
            <section className="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                <h2 className="text-center text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Loved by coastal families</h2>
                <div className="mt-10 grid gap-6 md:grid-cols-3">
                    {testimonials.map((t) => (
                        <figure key={t.name} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div className="flex gap-0.5 text-amber-400">
                                {Array.from({ length: 5 }).map((_, i) => (
                                    <Star key={i} className="h-4 w-4 fill-current" />
                                ))}
                            </div>
                            <blockquote className="mt-4 text-slate-700">“{t.quote}”</blockquote>
                            <figcaption className="mt-4 border-t border-slate-100 pt-4">
                                <p className="font-semibold text-slate-900">{t.name}</p>
                                <p className="text-sm text-slate-500">{t.area}</p>
                            </figcaption>
                        </figure>
                    ))}
                </div>
            </section>

            {/* Agent CTA */}
            <section className="border-t border-slate-200 bg-white">
                <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 py-14 sm:px-6 lg:flex-row lg:px-8">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                            <HomeIcon className="h-6 w-6" />
                        </div>
                        <div>
                            <h2 className="text-xl font-bold text-slate-900">Work with a local specialist</h2>
                            <p className="text-slate-500">Meet the agents who know your stretch of coast best.</p>
                        </div>
                    </div>
                    <Link
                        href={route('agents')}
                        className="inline-flex items-center rounded-lg bg-teal-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-teal-700"
                    >
                        Meet our agents
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
