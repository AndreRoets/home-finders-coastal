import { type Branch } from '@/components/public/branches';
import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, Building2, Mail, MapPin, Phone } from 'lucide-react';

export default function Offices({ branches = [] }: { branches?: Branch[] }) {
    return (
        <PublicLayout title="Our Offices" tone="light">
            <PageHero
                eyebrow="Find Us"
                title="Our Offices"
                description="Local teams along the coast — choose a branch to meet its agents and browse the homes on its books."
                image="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                {branches.length === 0 ? (
                    <p className="rounded-sm border border-dashed border-slate-300 bg-slate-50 p-12 text-center text-neutral-500">
                        Our office details are being updated. Please check back soon.
                    </p>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-3">
                        {branches.map((office) => (
                            <article key={office.id} className="group flex flex-col overflow-hidden rounded-sm border border-slate-200 bg-white transition-shadow hover:shadow-lg">
                                <Link href={route('offices.show', office.id)} className="relative block aspect-[8/5] overflow-hidden bg-slate-100">
                                    {office.logo ? (
                                        <img
                                            src={office.logo}
                                            alt={office.tradingName}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                        />
                                    ) : (
                                        <div className="flex h-full w-full items-center justify-center bg-navy/5 text-navy/40">
                                            <Building2 className="h-10 w-10" />
                                        </div>
                                    )}
                                    <div className="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent" />
                                    <span className="absolute bottom-4 left-4 rounded-full border border-white/40 bg-ink/50 px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                                        {office.agentCount} {office.agentCount === 1 ? 'agent' : 'agents'} · {office.listingCount}{' '}
                                        {office.listingCount === 1 ? 'property' : 'properties'}
                                    </span>
                                </Link>

                                <div className="flex flex-1 flex-col p-6">
                                    <Link href={route('offices.show', office.id)}>
                                        <h2 className="text-2xl font-light text-navy transition-colors group-hover:text-marine">{office.tradingName}</h2>
                                    </Link>
                                    {office.tagline && <p className="mt-1.5 text-sm text-marine">{office.tagline}</p>}

                                    <dl className="mt-6 space-y-2.5 text-sm text-neutral-600">
                                        {office.address && (
                                            <div className="flex items-start gap-2.5">
                                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                                <dd>{office.address}</dd>
                                            </div>
                                        )}
                                        {office.phone && (
                                            <div className="flex items-center gap-2.5">
                                                <Phone className="h-4 w-4 shrink-0 text-marine" />
                                                <dd>
                                                    <a href={`tel:${office.phoneHref}`} className="transition-colors hover:text-marine">
                                                        {office.phone}
                                                    </a>
                                                    {office.phoneLabel && <span className="text-neutral-400"> · {office.phoneLabel}</span>}
                                                </dd>
                                            </div>
                                        )}
                                        {office.email && (
                                            <div className="flex items-center gap-2.5">
                                                <Mail className="h-4 w-4 shrink-0 text-marine" />
                                                <dd>
                                                    <a href={`mailto:${office.email}`} className="transition-colors hover:text-marine">
                                                        {office.email}
                                                    </a>
                                                </dd>
                                            </div>
                                        )}
                                    </dl>

                                    <Link
                                        href={route('offices.show', office.id)}
                                        className="group/btn mt-6 inline-flex items-center gap-2 self-start border-b border-navy/30 pb-1 text-sm font-medium tracking-wide text-navy transition-colors hover:border-marine hover:text-marine"
                                    >
                                        Visit this office
                                        <ArrowUpRight className="h-4 w-4 transition-transform group-hover/btn:-translate-y-0.5 group-hover/btn:translate-x-0.5" />
                                    </Link>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
