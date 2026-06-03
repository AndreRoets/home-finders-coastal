import { sampleListings, type Listing } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

const principles = [
    { no: '01', title: 'Considered selection', copy: 'A deliberately small list. We show fewer homes, chosen with care.' },
    { no: '02', title: 'Honest guidance', copy: 'Clear, unhurried advice — no pressure, no noise, no jargon.' },
    { no: '03', title: 'Local knowledge', copy: 'Agents who actually live on the streets they represent.' },
];

export default function HomeMinimal({ featured = [] }: { featured?: Listing[] }) {
    const listings = featured.length > 0 ? featured : sampleListings;
    const hero = listings[0];

    return (
        <PublicLayout title="Coastal Homes">
            <div className="bg-stone-50 text-stone-900">
                {/* Hero — type-led, split */}
                <section className="mx-auto max-w-7xl px-6 lg:px-8">
                    <div className="grid items-end gap-12 border-b border-stone-200 py-20 lg:grid-cols-12 lg:py-28">
                        <div className="lg:col-span-7">
                            <p className="text-xs font-medium tracking-[0.3em] text-stone-400 uppercase">Home Finders Coastal</p>
                            <h1 className="mt-8 text-5xl leading-[1.02] font-light tracking-tight text-stone-900 sm:text-6xl lg:text-7xl">
                                Coastal homes,
                                <br />
                                <span className="text-stone-400">simply found.</span>
                            </h1>
                            <p className="mt-8 max-w-md text-lg leading-relaxed text-stone-500">
                                A quiet, considered approach to buying and selling along the coast. Less noise. Better homes.
                            </p>
                            <div className="mt-10 flex items-center gap-8">
                                <Link
                                    href={route('for-sale')}
                                    className="group inline-flex items-center gap-3 border-b border-stone-900 pb-1 text-sm font-medium tracking-wide text-stone-900"
                                >
                                    Browse homes
                                    <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                                <Link href={route('contact')} className="text-sm tracking-wide text-stone-400 transition-colors hover:text-stone-900">
                                    Talk to us
                                </Link>
                            </div>
                        </div>
                        <div className="lg:col-span-5">
                            <div className="relative aspect-[3/4] overflow-hidden">
                                <img
                                    src={hero?.image ?? 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=70'}
                                    alt={hero?.title ?? 'Coastal home'}
                                    className="h-full w-full object-cover grayscale transition-all duration-700 hover:grayscale-0"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                {/* Selected homes */}
                <section className="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
                    <div className="flex items-baseline justify-between">
                        <h2 className="text-2xl font-light tracking-tight text-stone-900 sm:text-3xl">Selected homes</h2>
                        <span className="text-xs tracking-[0.2em] text-stone-400 uppercase">{listings.length} listings</span>
                    </div>

                    <div className="mt-12 grid gap-x-10 gap-y-16 sm:grid-cols-2 lg:grid-cols-3">
                        {listings.slice(0, 6).map((listing, i) => (
                            <Link key={listing.id} href={route('property.show', listing.ref ?? listing.id)} className="group block">
                                <div className="relative aspect-[4/5] overflow-hidden bg-stone-100">
                                    <img
                                        src={listing.image}
                                        alt={listing.title}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-all duration-700 group-hover:scale-[1.03]"
                                    />
                                </div>
                                <div className="mt-5 flex items-baseline justify-between border-t border-stone-200 pt-4">
                                    <span className="text-xs tracking-[0.2em] text-stone-400 uppercase">
                                        {String(i + 1).padStart(2, '0')}
                                    </span>
                                    <span className="text-xs tracking-[0.2em] text-stone-400 uppercase">{listing.location}</span>
                                </div>
                                <h3 className="mt-3 text-lg font-light text-stone-900 transition-colors group-hover:text-stone-500">
                                    {listing.title}
                                </h3>
                                <p className="mt-1 text-sm text-stone-500">{listing.price}</p>
                                <p className="mt-3 text-xs tracking-wide text-stone-400">
                                    {listing.beds} bed · {listing.baths} bath · {listing.area}
                                </p>
                            </Link>
                        ))}
                    </div>
                </section>

                {/* Principles */}
                <section className="border-y border-stone-200">
                    <div className="mx-auto max-w-7xl px-6 lg:px-8">
                        <div className="grid divide-stone-200 lg:grid-cols-3 lg:divide-x">
                            {principles.map((p) => (
                                <div key={p.no} className="py-14 lg:px-10 lg:first:pl-0">
                                    <span className="text-sm tracking-[0.2em] text-stone-300">{p.no}</span>
                                    <h3 className="mt-5 text-xl font-light text-stone-900">{p.title}</h3>
                                    <p className="mt-3 text-stone-500">{p.copy}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="mx-auto max-w-7xl px-6 py-24 lg:px-8 lg:py-32">
                    <div className="max-w-2xl">
                        <h2 className="text-3xl font-light leading-tight tracking-tight text-stone-900 sm:text-4xl">
                            Thinking of selling? Let’s have a quiet conversation about your home.
                        </h2>
                        <Link
                            href={route('contact')}
                            className="group mt-10 inline-flex items-center gap-3 border-b border-stone-900 pb-1 text-sm font-medium tracking-wide text-stone-900"
                        >
                            Request a valuation
                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
