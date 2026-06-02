import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Mail, Phone } from 'lucide-react';

interface Agent {
    id: number | string;
    name: string;
    phone: string;
    email: string;
    photo: string;
}

export default function Agents({ agents = [] }: { agents?: Agent[] }) {
    return (
        <PublicLayout title="Agents">
            <PageHero
                eyebrow="Meet the Team"
                title="Our Agents"
                description="Local specialists who live and work along the coast — ready to help you buy, sell, or rent."
            />

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                {agents.length === 0 ? (
                    <p className="rounded-xl border border-dashed border-slate-300 bg-white p-12 text-center text-slate-500">
                        Our team details are being updated. Please check back soon.
                    </p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {agents.map((agent) => (
                            <article key={agent.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                                <div className="aspect-square overflow-hidden bg-slate-100">
                                    <img src={agent.photo} alt={agent.name} loading="lazy" className="h-full w-full object-cover" />
                                </div>
                                <div className="p-5">
                                    <h3 className="font-semibold text-slate-900">{agent.name}</h3>
                                    <div className="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                                        {agent.phone && (
                                            <a
                                                href={`tel:${agent.phone.replace(/\s/g, '')}`}
                                                className="flex items-center gap-2 text-slate-600 hover:text-sky-700"
                                            >
                                                <Phone className="h-4 w-4 text-slate-400" />
                                                {agent.phone}
                                            </a>
                                        )}
                                        {agent.email && (
                                            <a href={`mailto:${agent.email}`} className="flex items-center gap-2 text-slate-600 hover:text-sky-700">
                                                <Mail className="h-4 w-4 text-slate-400" />
                                                <span className="truncate">{agent.email}</span>
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}

                <div className="mt-12 rounded-2xl bg-slate-900 px-8 py-10 text-center">
                    <h3 className="text-xl font-semibold text-white">Want to join the Home Finders Coastal team?</h3>
                    <p className="mx-auto mt-2 max-w-xl text-slate-300">We’re always looking for passionate agents who know the coastline.</p>
                    <Link
                        href={route('contact')}
                        className="mt-6 inline-flex items-center justify-center rounded-lg bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-sky-700"
                    >
                        Get in Touch
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
