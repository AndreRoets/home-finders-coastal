import AgentCard from '@/components/public/agent-card';
import { type Branch } from '@/components/public/branches';
import { type Listing } from '@/components/public/listings';
import PageHero from '@/components/public/page-hero';
import PropertyGrid from '@/components/public/property-grid';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowLeft, BadgeCheck, Building2, Mail, MapPin, Phone } from 'lucide-react';

export default function Office({ branch, listings = [] }: { branch: Branch; listings?: Listing[] }) {
    return (
        <PublicLayout title={branch.tradingName} tone="light">
            <PageHero
                eyebrow="Our Offices"
                title={branch.tradingName}
                description={branch.tagline ?? undefined}
                image="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                <Link href={route('offices')} className="inline-flex items-center gap-2 text-sm text-neutral-500 transition-colors hover:text-marine">
                    <ArrowLeft className="h-4 w-4" />
                    All offices
                </Link>
            </section>

            {/* Contact block */}
            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-8 rounded-sm border border-slate-200 bg-slate-50 p-8 sm:flex-row sm:items-center">
                    <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        {branch.logo ? (
                            <img src={branch.logo} alt={branch.tradingName} className="h-full w-full object-cover" />
                        ) : (
                            <Building2 className="h-10 w-10 text-navy/40" />
                        )}
                    </div>
                    <dl className="grid flex-1 gap-x-10 gap-y-4 text-sm text-neutral-700 sm:grid-cols-2">
                        {branch.address && (
                            <div className="flex items-start gap-2.5">
                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                <dd>{branch.address}</dd>
                            </div>
                        )}
                        {branch.phone && (
                            <div className="flex items-start gap-2.5">
                                <Phone className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                <dd>
                                    <a href={`tel:${branch.phoneHref}`} className="transition-colors hover:text-marine">
                                        {branch.phone}
                                    </a>
                                    {branch.phoneLabel && <span className="text-neutral-400"> · {branch.phoneLabel}</span>}
                                </dd>
                            </div>
                        )}
                        {branch.phoneSecondary && (
                            <div className="flex items-start gap-2.5">
                                <Phone className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                <dd>
                                    <a href={`tel:${branch.phoneSecondaryHref}`} className="transition-colors hover:text-marine">
                                        {branch.phoneSecondary}
                                    </a>
                                    {branch.phoneSecondaryLabel && <span className="text-neutral-400"> · {branch.phoneSecondaryLabel}</span>}
                                </dd>
                            </div>
                        )}
                        {branch.email && (
                            <div className="flex items-start gap-2.5">
                                <Mail className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                <dd>
                                    <a href={`mailto:${branch.email}`} className="transition-colors hover:text-marine">
                                        {branch.email}
                                    </a>
                                </dd>
                            </div>
                        )}
                        {branch.ppraNumber && (
                            <div className="flex items-start gap-2.5">
                                <BadgeCheck className="mt-0.5 h-4 w-4 shrink-0 text-marine" />
                                <dd>PPRA {branch.ppraNumber}</dd>
                            </div>
                        )}
                    </dl>
                </div>
            </section>

            {/* Agents in this office */}
            {branch.agents.length > 0 && (
                <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-light text-navy">Meet the team</h2>
                    <div className="mt-10 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                        {branch.agents.map((agent) => (
                            <AgentCard key={agent.id} agent={agent} />
                        ))}
                    </div>
                </section>
            )}

            {/* Properties under this office */}
            <section className="mx-auto max-w-7xl px-4 py-10 pb-16 sm:px-6 lg:px-8">
                <div className="flex items-end justify-between gap-4">
                    <h2 className="text-3xl font-light text-navy">Properties</h2>
                    <p className="text-xs tracking-[0.2em] text-marine/80 uppercase">
                        {listings.length} {listings.length === 1 ? 'listing' : 'listings'}
                    </p>
                </div>
                <div className="mt-10">
                    <PropertyGrid listings={listings} emptyMessage="This office has no listings on the market right now." />
                </div>
            </section>
        </PublicLayout>
    );
}
