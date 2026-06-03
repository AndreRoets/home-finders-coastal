import { sampleListings, type Listing } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowRight, Bath, BedDouble, Heart, Maximize, MapPin, Search, Sparkles, TrendingUp } from 'lucide-react';

const quickFilters = ['Beachfront', 'Sea View', 'New Builds', 'Pet Friendly', 'Under R5M'];

const perks = [
    { icon: Sparkles, title: 'Hand-picked', copy: 'Every listing vetted by a local agent before it reaches you.', tint: 'from-fuchsia-500 to-pink-500' },
    { icon: TrendingUp, title: 'Live market data', copy: 'Real pricing trends so you move with total confidence.', tint: 'from-cyan-500 to-blue-500' },
    { icon: Heart, title: 'Made for you', copy: 'Save searches and get matched the moment a home drops.', tint: 'from-violet-500 to-indigo-500' },
];

export default function HomeVibrant({ featured = [] }: { featured?: Listing[] }) {
    const listings = featured.length > 0 ? featured : sampleListings;

    return (
        <PublicLayout title="Find Your Vibe">
            <style>{`
                @keyframes hfc-float {
                    0%, 100% { transform: translate(0, 0) scale(1); }
                    33% { transform: translate(30px, -40px) scale(1.1); }
                    66% { transform: translate(-20px, 20px) scale(0.95); }
                }
                .hfc-blob { animation: hfc-float 14s ease-in-out infinite; }
            `}</style>

            <div className="relative overflow-hidden bg-slate-950 text-white">
                {/* Animated background blobs */}
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="hfc-blob absolute -top-32 -left-24 h-96 w-96 rounded-full bg-fuchsia-600/40 blur-3xl" />
                    <div className="hfc-blob absolute top-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-cyan-500/30 blur-3xl" style={{ animationDelay: '-5s' }} />
                    <div className="hfc-blob absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-violet-600/30 blur-3xl" style={{ animationDelay: '-9s' }} />
                </div>

                {/* Hero */}
                <section className="relative mx-auto max-w-7xl px-6 pt-24 pb-16 lg:px-8 lg:pt-32">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-sm font-medium text-white/80 backdrop-blur">
                            <Sparkles className="h-4 w-4 text-fuchsia-300" />
                            The coast’s freshest listings, daily
                        </span>
                        <h1 className="mt-7 text-5xl font-extrabold tracking-tight sm:text-6xl lg:text-7xl">
                            Find a home that
                            <span className="block bg-gradient-to-r from-fuchsia-400 via-pink-400 to-cyan-300 bg-clip-text text-transparent">
                                matches your vibe.
                            </span>
                        </h1>
                        <p className="mx-auto mt-6 max-w-xl text-lg text-white/70">
                            Beachfront apartments, family homes, clifftop escapes — discover where the coast comes alive.
                        </p>
                    </div>

                    {/* Glass search */}
                    <div className="mx-auto mt-10 max-w-2xl rounded-3xl border border-white/15 bg-white/10 p-2.5 shadow-2xl shadow-fuchsia-900/30 backdrop-blur-xl">
                        <div className="flex flex-col gap-2.5 sm:flex-row">
                            <div className="flex flex-1 items-center gap-2.5 rounded-2xl bg-white/10 px-4">
                                <MapPin className="h-5 w-5 text-fuchsia-300" />
                                <input
                                    type="text"
                                    placeholder="Where do you want to live?"
                                    className="w-full bg-transparent py-3.5 text-white outline-none placeholder:text-white/50"
                                />
                            </div>
                            <Link
                                href={route('for-sale')}
                                className="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-fuchsia-500 to-cyan-500 px-7 py-3.5 font-semibold text-white shadow-lg transition-transform hover:scale-[1.02]"
                            >
                                <Search className="h-5 w-5" />
                                Search
                            </Link>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-wrap justify-center gap-2.5">
                        {quickFilters.map((f) => (
                            <Link
                                key={f}
                                href={route('for-sale')}
                                className="rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-white/80 backdrop-blur transition-colors hover:border-fuchsia-400/50 hover:bg-white/10 hover:text-white"
                            >
                                {f}
                            </Link>
                        ))}
                    </div>
                </section>

                {/* Featured listings */}
                <section className="relative mx-auto max-w-7xl px-6 py-16 lg:px-8">
                    <div className="flex items-end justify-between">
                        <h2 className="text-3xl font-extrabold tracking-tight sm:text-4xl">🔥 Trending now</h2>
                        <Link href={route('for-sale')} className="group hidden items-center gap-1.5 text-sm font-semibold text-cyan-300 sm:inline-flex">
                            See everything
                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>

                    <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {listings.slice(0, 6).map((listing) => (
                            <Link
                                key={listing.id}
                                href={route('property.show', listing.ref ?? listing.id)}
                                className="group relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 backdrop-blur-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-fuchsia-400/40 hover:shadow-2xl hover:shadow-fuchsia-900/30"
                            >
                                <div className="relative aspect-[4/3] overflow-hidden">
                                    <img
                                        src={listing.image}
                                        alt={listing.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                    />
                                    <div className="absolute inset-0 bg-gradient-to-t from-slate-950/60 to-transparent" />
                                    <span className="absolute top-3 left-3 rounded-full bg-gradient-to-r from-fuchsia-500 to-cyan-500 px-3 py-1 text-xs font-bold text-white shadow-lg">
                                        {listing.price}
                                    </span>
                                </div>
                                <div className="p-5">
                                    <h3 className="line-clamp-1 text-lg font-bold text-white">{listing.title}</h3>
                                    <p className="mt-1 flex items-center gap-1.5 text-sm text-white/60">
                                        <MapPin className="h-4 w-4 text-fuchsia-300" />
                                        {listing.location}
                                    </p>
                                    <div className="mt-4 flex items-center gap-4 border-t border-white/10 pt-4 text-sm text-white/70">
                                        <span className="flex items-center gap-1.5">
                                            <BedDouble className="h-4 w-4 text-cyan-300" /> {listing.beds}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <Bath className="h-4 w-4 text-cyan-300" /> {listing.baths}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <Maximize className="h-4 w-4 text-cyan-300" /> {listing.area}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                {/* Perks */}
                <section className="relative mx-auto max-w-7xl px-6 py-16 lg:px-8">
                    <div className="grid gap-6 md:grid-cols-3">
                        {perks.map((perk) => (
                            <div
                                key={perk.title}
                                className="rounded-3xl border border-white/10 bg-white/5 p-7 backdrop-blur-sm transition-colors hover:bg-white/10"
                            >
                                <div className={`inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br ${perk.tint} shadow-lg`}>
                                    <perk.icon className="h-6 w-6 text-white" />
                                </div>
                                <h3 className="mt-5 text-xl font-bold">{perk.title}</h3>
                                <p className="mt-2 text-white/65">{perk.copy}</p>
                            </div>
                        ))}
                    </div>
                </section>

                {/* CTA */}
                <section className="relative mx-auto max-w-7xl px-6 pb-24 lg:px-8">
                    <div className="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-fuchsia-600 via-pink-600 to-cyan-500 px-8 py-14 text-center shadow-2xl">
                        <div className="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-white/20 blur-2xl" />
                        <h2 className="relative text-3xl font-extrabold sm:text-4xl">Ready to make your move?</h2>
                        <p className="relative mx-auto mt-3 max-w-lg text-white/90">
                            Tell us what you’re after and we’ll match you with homes the moment they hit the market.
                        </p>
                        <Link
                            href={route('contact')}
                            className="relative mt-8 inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 font-bold text-fuchsia-700 transition-transform hover:scale-105"
                        >
                            Get matched
                            <ArrowRight className="h-5 w-5" />
                        </Link>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
