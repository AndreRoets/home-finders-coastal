import { type Branch, DEFAULT_BRANCH_IMAGE } from '@/components/public/branches';
import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, Mail, MapPin, Phone } from 'lucide-react';

export default function Branches({ branches = [] }: { branches?: Branch[] }) {
    return (
        <PublicLayout title="Our Branches" tone="light">
            <PageHero
                eyebrow="Find Us"
                title="Our Branches"
                description="Local teams along the coast — choose a branch to meet its agents and browse the homes on its books."
                image="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                {branches.length === 0 ? (
                    <p className="rounded-sm border border-dashed border-slate-300 bg-slate-50 p-12 text-center text-neutral-500">
                        Our branch details are being updated. Please check back soon.
                    </p>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-3">
                        {branches.map((branch) => (
                            <article key={branch.id} className="group flex flex-col overflow-hidden rounded-sm border border-slate-200 bg-white transition-shadow hover:shadow-lg">
                                <Link href={route('branches.show', branch.id)} className="relative block aspect-[8/5] overflow-hidden bg-slate-100">
                                    <img
                                        src={branch.logo ?? DEFAULT_BRANCH_IMAGE}
                                        alt={branch.tradingName}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                    />
                                    <div className="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent" />
                                    <span className="absolute bottom-4 left-4 rounded-full border border-white/40 bg-ink/50 px-3 py-1 text-[11px] tracking-[0.2em] text-white uppercase backdrop-blur">
                                        {branch.agentCount} {branch.agentCount === 1 ? 'agent' : 'agents'} · {branch.listingCount}{' '}
                                        {branch.listingCount === 1 ? 'property' : 'properties'}
                                    </span>
                                </Link>

                                <div className="flex flex-1 flex-col p-6">
                                    <Link href={route('branches.show', branch.id)}>
                                        <h2 className="text-2xl font-light text-navy transition-colors group-hover:text-marine">{branch.tradingName}</h2>
                                    </Link>
                                    {branch.tagline && <p className="mt-1.5 text-sm text-marine">{branch.tagline}</p>}

                                    <dl className="mt-6 space-y-2.5 text-sm text-neutral-600">
                                        {branch.address && (
                                            <div className="flex items-start gap-2.5">
                                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                                <dd>{branch.address}</dd>
                                            </div>
                                        )}
                                        {branch.phone && (
                                            <div className="flex items-center gap-2.5">
                                                <Phone className="h-4 w-4 shrink-0 text-marine" />
                                                <dd>
                                                    <a href={`tel:${branch.phoneHref}`} className="transition-colors hover:text-marine">
                                                        {branch.phone}
                                                    </a>
                                                    {branch.phoneLabel && <span className="text-neutral-400"> · {branch.phoneLabel}</span>}
                                                </dd>
                                            </div>
                                        )}
                                        {branch.email && (
                                            <div className="flex items-center gap-2.5">
                                                <Mail className="h-4 w-4 shrink-0 text-marine" />
                                                <dd>
                                                    <a href={`mailto:${branch.email}`} className="transition-colors hover:text-marine">
                                                        {branch.email}
                                                    </a>
                                                </dd>
                                            </div>
                                        )}
                                    </dl>

                                    <Link
                                        href={route('branches.show', branch.id)}
                                        className="group/btn mt-6 inline-flex items-center gap-2 self-start border-b border-navy/30 pb-1 text-sm font-medium tracking-wide text-navy transition-colors hover:border-marine hover:text-marine"
                                    >
                                        Visit this branch
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
