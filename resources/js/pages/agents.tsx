import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { agentUrl } from '@/lib/routes';
import { Link } from '@inertiajs/react';
import { Mail, Phone } from 'lucide-react';

interface Agent {
    id: number | string;
    name: string;
    designation: string | null;
    phone: string;
    email: string;
    photo: string;
}

export default function Agents({ agents = [] }: { agents?: Agent[] }) {
    return (
        <PublicLayout title="Agents" tone="light">
            <PageHero
                eyebrow="Meet the Team"
                title="Our Agents"
                description="Local specialists who live and work along the coast — ready to help you buy, sell, or rent."
            />

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                {agents.length === 0 ? (
                    <p className="rounded-sm border border-dashed border-slate-300 bg-slate-50 p-12 text-center text-neutral-500">
                        Our team details are being updated. Please check back soon.
                    </p>
                ) : (
                    <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                        {agents.map((agent) => (
                            <article key={agent.id} className="group">
                                <Link href={agentUrl(agent.id)} className="block aspect-square overflow-hidden bg-slate-100">
                                    <img
                                        src={agent.photo}
                                        alt={agent.name}
                                        loading="lazy"
                                        className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                    />
                                </Link>
                                <div className="mt-5">
                                    <Link href={agentUrl(agent.id)}>
                                        <h3 className="text-lg font-light text-navy transition-colors group-hover:text-marine">{agent.name}</h3>
                                    </Link>
                                    {agent.designation && <p className="mt-1 text-sm text-neutral-500">{agent.designation}</p>}
                                    <div className="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                                        {agent.phone && (
                                            <a
                                                href={`tel:${agent.phone.replace(/\s/g, '')}`}
                                                className="flex items-center gap-2 text-neutral-600 transition-colors hover:text-marine"
                                            >
                                                <Phone className="h-4 w-4 text-neutral-400" />
                                                {agent.phone}
                                            </a>
                                        )}
                                        {agent.email && (
                                            <a
                                                href={`mailto:${agent.email}`}
                                                className="flex items-center gap-2 text-neutral-600 transition-colors hover:text-marine"
                                            >
                                                <Mail className="h-4 w-4 text-neutral-400" />
                                                <span className="truncate">{agent.email}</span>
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                <div className="mt-16 rounded-sm border border-slate-200 bg-slate-50 px-8 py-12 text-center">
                    <h3 className="text-2xl font-light text-navy">Want to join the Home Finders Coastal team?</h3>
                    <p className="mx-auto mt-3 max-w-xl text-neutral-600">We’re always looking for passionate agents who know the coastline.</p>
                    <Link
                        href={route('contact')}
                        className="mt-7 inline-flex items-center justify-center rounded-full bg-brand-red px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-brand-red-bright"
                    >
                        Get in Touch
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
