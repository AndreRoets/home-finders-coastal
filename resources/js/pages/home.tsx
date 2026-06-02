import { sampleListings, type Listing } from '@/components/public/listings';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Award, Home as HomeIcon, Search, ShieldCheck, Users } from 'lucide-react';

const valueProps = [
    { icon: HomeIcon, title: 'Curated Coastal Listings', description: 'Hand-picked homes along the most sought-after stretches of coastline.' },
    { icon: ShieldCheck, title: 'Trusted Guidance', description: 'Transparent advice through every step of buying, selling, or renting.' },
    { icon: Award, title: 'HFC Exclusive Access', description: 'Off-market and premium properties you won’t find anywhere else.' },
    { icon: Users, title: 'Local Specialists', description: 'Agents who live and work in the neighbourhoods they sell.' },
];

export default function Home({ featured = [] }: { featured?: Listing[] }) {
    const featuredListings = featured.length > 0 ? featured : sampleListings.slice(0, 3);

    return (
        <PublicLayout title="Home">
            {/* Hero */}
            <section className="relative overflow-hidden">
                <img
                    src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2000&q=60"
                    alt="Coastal shoreline"
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-slate-900/70 to-slate-900/40" />
                <div className="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
                    <p className="text-sm font-semibold tracking-wide text-sky-400 uppercase">Coastal Property Specialists</p>
                    <h1 className="mt-3 max-w-3xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Find your place on the coast
                    </h1>
                    <p className="mt-5 max-w-xl text-lg leading-relaxed text-slate-200">
                        Browse homes for sale and to rent, discover HFC Exclusive listings, and work with agents who know the coastline inside out.
                    </p>

                    {/* Search bar */}
                    <div className="mt-8 flex max-w-2xl flex-col gap-3 rounded-xl bg-white/95 p-3 shadow-lg backdrop-blur sm:flex-row">
                        <div className="flex flex-1 items-center gap-2 px-3">
                            <Search className="h-5 w-5 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search by suburb, town, or area…"
                                className="w-full bg-transparent py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400"
                            />
                        </div>
                        <Link
                            href={route('for-sale')}
                            className="inline-flex items-center justify-center rounded-lg bg-sky-600 px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-sky-700"
                        >
                            Search Properties
                        </Link>
                    </div>

                    <div className="mt-6 flex flex-wrap gap-3 text-sm">
                        <Link href={route('for-sale')} className="rounded-full bg-white/10 px-4 py-1.5 text-white hover:bg-white/20">
                            For Sale
                        </Link>
                        <Link href={route('to-rent')} className="rounded-full bg-white/10 px-4 py-1.5 text-white hover:bg-white/20">
                            To Rent
                        </Link>
                        <Link href={route('hfc-exclusive')} className="rounded-full bg-white/10 px-4 py-1.5 text-white hover:bg-white/20">
                            HFC Exclusive
                        </Link>
                    </div>
                </div>
            </section>

            {/* Featured listings */}
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="flex items-end justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Featured Properties</h2>
                        <p className="mt-2 text-slate-600">A selection of homes our agents are excited about right now.</p>
                    </div>
                    <Link href={route('for-sale')} className="hidden text-sm font-medium text-sky-700 hover:text-sky-800 sm:block">
                        View all &rarr;
                    </Link>
                </div>
                <div className="mt-8">
                    <PropertyGrid listings={featuredListings} />
                </div>
            </section>

            {/* Value props */}
            <section className="border-y border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                    <h2 className="text-center text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Why Home Finders Coastal</h2>
                    <div className="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {valueProps.map((prop) => (
                            <div key={prop.title} className="text-center">
                                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                                    <prop.icon className="h-6 w-6" />
                                </div>
                                <h3 className="mt-4 font-semibold text-slate-900">{prop.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">{prop.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="flex flex-col items-center justify-between gap-6 rounded-2xl bg-gradient-to-r from-sky-600 to-sky-700 px-8 py-12 text-center lg:flex-row lg:text-left">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight text-white sm:text-3xl">Thinking of selling your coastal home?</h2>
                        <p className="mt-2 max-w-xl text-sky-100">Get a free, no-obligation valuation from a local specialist.</p>
                    </div>
                    <Link
                        href={route('contact')}
                        className="inline-flex shrink-0 items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-sky-700 transition-colors hover:bg-sky-50"
                    >
                        Request a Valuation
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
