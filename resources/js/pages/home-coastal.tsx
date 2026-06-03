import { sampleListings, type Listing } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Anchor, Bath, BedDouble, Maximize, Search, Sailboat, Shell, Sun, Waves } from 'lucide-react';

const highlights = [
    { icon: Waves, title: 'Steps from the sand', copy: 'Homes where the tide is your timekeeper and the beach is your backyard.' },
    { icon: Sun, title: 'Endless golden hours', copy: 'West-facing decks and sunset views that never get old.' },
    { icon: Anchor, title: 'Harbour living', copy: 'Moor the boat, walk to the village, live the slow coastal life.' },
];

export default function HomeCoastal({ featured = [] }: { featured?: Listing[] }) {
    const listings = featured.length > 0 ? featured : sampleListings;

    return (
        <PublicLayout title="Life by the Sea">
            <style>{`
                @keyframes hfc-wave {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
                @keyframes hfc-drift {
                    0%, 100% { transform: translateY(0) rotate(0deg); }
                    50% { transform: translateY(-14px) rotate(6deg); }
                }
                @keyframes hfc-bob {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                .hfc-wave-slow { animation: hfc-wave 18s linear infinite; }
                .hfc-wave-fast { animation: hfc-wave 11s linear infinite; }
                .hfc-drift { animation: hfc-drift 7s ease-in-out infinite; }
                .hfc-bob { animation: hfc-bob 5s ease-in-out infinite; }
                @media (prefers-reduced-motion: reduce) {
                    .hfc-wave-slow, .hfc-wave-fast, .hfc-drift, .hfc-bob { animation: none; }
                }
            `}</style>

            {/* Hero — sky to sea gradient with animated waves */}
            <section className="relative overflow-hidden bg-gradient-to-b from-sky-300 via-cyan-300 to-cyan-500">
                {/* Sun */}
                <div className="hfc-bob absolute top-16 right-12 h-24 w-24 rounded-full bg-amber-200 shadow-[0_0_80px_30px_rgba(254,240,138,0.7)] sm:right-24 sm:h-32 sm:w-32" />
                {/* Drifting sailboat */}
                <Sailboat className="hfc-drift absolute top-28 left-[8%] h-10 w-10 text-white/80" strokeWidth={1.5} />

                <div className="relative mx-auto max-w-7xl px-4 pt-24 pb-44 sm:px-6 lg:px-8 lg:pt-32 lg:pb-56">
                    <p className="inline-flex items-center gap-2 rounded-full bg-white/30 px-4 py-1.5 text-sm font-semibold text-sky-900 backdrop-blur">
                        <Shell className="h-4 w-4" />
                        Coastal Property Specialists
                    </p>
                    <h1 className="mt-6 max-w-3xl text-4xl font-extrabold tracking-tight text-white drop-shadow-sm sm:text-5xl lg:text-6xl">
                        Wake up to the sound of the waves.
                    </h1>
                    <p className="mt-5 max-w-xl text-lg text-sky-50">
                        Beach cottages, sea-view apartments and harbourside homes — find your slice of the coast.
                    </p>

                    {/* Search */}
                    <div className="mt-8 flex max-w-2xl flex-col gap-3 rounded-2xl bg-white/95 p-3 shadow-xl backdrop-blur sm:flex-row">
                        <div className="flex flex-1 items-center gap-2 px-3">
                            <Search className="h-5 w-5 text-cyan-500" />
                            <input
                                type="text"
                                placeholder="Find your beach town…"
                                className="w-full bg-transparent py-2.5 text-slate-900 outline-none placeholder:text-slate-400"
                            />
                        </div>
                        <Link
                            href={route('for-sale')}
                            className="inline-flex items-center justify-center rounded-xl bg-cyan-600 px-6 py-2.5 font-semibold text-white transition-colors hover:bg-cyan-700"
                        >
                            Search the coast
                        </Link>
                    </div>
                </div>

                {/* Animated layered waves */}
                <div className="absolute right-0 bottom-0 left-0 leading-[0]">
                    <div className="hfc-wave-slow w-[200%]">
                        <svg viewBox="0 0 1440 120" preserveAspectRatio="none" className="h-20 w-1/2 fill-cyan-50/40 sm:h-28">
                            <path d="M0,64 C240,96 480,32 720,48 C960,64 1200,112 1440,80 L1440,120 L0,120 Z" />
                        </svg>
                    </div>
                    <div className="hfc-wave-fast absolute bottom-0 w-[200%]">
                        <svg viewBox="0 0 1440 120" preserveAspectRatio="none" className="h-16 w-1/2 fill-slate-50 sm:h-24">
                            <path d="M0,80 C240,40 480,104 720,88 C960,72 1200,24 1440,56 L1440,120 L0,120 Z" />
                        </svg>
                    </div>
                </div>
            </section>

            {/* Highlights */}
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="grid gap-6 md:grid-cols-3">
                    {highlights.map((h) => (
                        <div key={h.title} className="rounded-2xl border border-cyan-100 bg-white p-7 shadow-sm transition-shadow hover:shadow-md">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600">
                                <h.icon className="h-6 w-6" />
                            </div>
                            <h3 className="mt-5 text-lg font-bold text-slate-900">{h.title}</h3>
                            <p className="mt-2 text-slate-600">{h.copy}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Featured beachside homes */}
            <section className="bg-gradient-to-b from-slate-50 to-cyan-50/60">
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                    <div className="flex items-end justify-between">
                        <div>
                            <h2 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Homes by the water</h2>
                            <p className="mt-2 text-slate-600">Hand-picked listings where the sea is always in view.</p>
                        </div>
                        <Link href={route('for-sale')} className="hidden text-sm font-semibold text-cyan-700 hover:text-cyan-800 sm:block">
                            View all &rarr;
                        </Link>
                    </div>
                    <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {listings.slice(0, 6).map((listing) => (
                            <Link
                                key={listing.id}
                                href={route('property.show', listing.ref ?? listing.id)}
                                className="group block overflow-hidden rounded-2xl border border-cyan-100 bg-white shadow-sm transition-shadow hover:shadow-lg"
                            >
                                <div className="relative aspect-[4/3] overflow-hidden">
                                    <img
                                        src={listing.image}
                                        alt={listing.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    />
                                    <span className="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-cyan-600/95 px-3 py-1 text-xs font-semibold text-white shadow-sm">
                                        <Waves className="h-3.5 w-3.5" /> Sea view
                                    </span>
                                </div>
                                <div className="p-5">
                                    <p className="text-lg font-bold text-cyan-700">{listing.price}</p>
                                    <h3 className="mt-1 line-clamp-1 font-semibold text-slate-900">{listing.title}</h3>
                                    <p className="mt-1 text-sm text-slate-500">{listing.location}</p>
                                    <div className="mt-4 flex items-center gap-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
                                        <span className="flex items-center gap-1.5">
                                            <BedDouble className="h-4 w-4 text-cyan-500" /> {listing.beds}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <Bath className="h-4 w-4 text-cyan-500" /> {listing.baths}
                                        </span>
                                        <span className="flex items-center gap-1.5">
                                            <Maximize className="h-4 w-4 text-cyan-500" /> {listing.area}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA — wave-topped band */}
            <section className="relative overflow-hidden bg-cyan-600">
                <div className="absolute top-0 right-0 left-0 rotate-180 leading-[0]">
                    <div className="hfc-wave-slow w-[200%]">
                        <svg viewBox="0 0 1440 120" preserveAspectRatio="none" className="h-14 w-1/2 fill-cyan-50/50">
                            <path d="M0,64 C240,96 480,32 720,48 C960,64 1200,112 1440,80 L1440,120 L0,120 Z" />
                        </svg>
                    </div>
                </div>
                <div className="relative mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-20 text-center sm:px-6 lg:px-8">
                    <Shell className="hfc-bob h-10 w-10 text-cyan-100" />
                    <h2 className="text-3xl font-extrabold text-white sm:text-4xl">Ready to live the coastal life?</h2>
                    <p className="max-w-xl text-cyan-50">
                        Whether you’re buying your forever beach home or selling a sea-view gem, we’ll guide you through the tides.
                    </p>
                    <Link
                        href={route('contact')}
                        className="inline-flex items-center rounded-full bg-white px-8 py-3.5 font-bold text-cyan-700 transition-transform hover:scale-105"
                    >
                        Start your coastal search
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
