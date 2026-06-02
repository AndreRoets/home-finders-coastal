import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { Mail, Phone } from 'lucide-react';

interface Agent {
    id: number;
    name: string;
    title: string;
    area: string;
    phone: string;
    email: string;
    photo: string;
}

const agents: Agent[] = [
    {
        id: 1,
        name: 'Sarah Daniels',
        title: 'Principal Agent',
        area: 'Camps Bay & Clifton',
        phone: '+27 21 000 0001',
        email: 'sarah@homefinderscoastal.com',
        photo: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=60',
    },
    {
        id: 2,
        name: 'James Okafor',
        title: 'Senior Property Consultant',
        area: 'Sea Point & Mouille Point',
        phone: '+27 21 000 0002',
        email: 'james@homefinderscoastal.com',
        photo: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&q=60',
    },
    {
        id: 3,
        name: 'Lerato Mokoena',
        title: 'Rentals Specialist',
        area: 'Hout Bay & Llandudno',
        phone: '+27 21 000 0003',
        email: 'lerato@homefinderscoastal.com',
        photo: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&q=60',
    },
    {
        id: 4,
        name: 'David Pretorius',
        title: 'Exclusive Listings Agent',
        area: 'Kommetjie & Noordhoek',
        phone: '+27 21 000 0004',
        email: 'david@homefinderscoastal.com',
        photo: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=60',
    },
];

export default function Agents() {
    return (
        <PublicLayout title="Agents">
            <PageHero
                eyebrow="Meet the Team"
                title="Our Agents"
                description="Local specialists who live and work along the coast — ready to help you buy, sell, or rent."
            />

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {agents.map((agent) => (
                        <article key={agent.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div className="aspect-square overflow-hidden bg-slate-100">
                                <img src={agent.photo} alt={agent.name} loading="lazy" className="h-full w-full object-cover" />
                            </div>
                            <div className="p-5">
                                <h3 className="font-semibold text-slate-900">{agent.name}</h3>
                                <p className="text-sm text-sky-700">{agent.title}</p>
                                <p className="mt-1 text-sm text-slate-500">{agent.area}</p>
                                <div className="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                                    <a href={`tel:${agent.phone.replace(/\s/g, '')}`} className="flex items-center gap-2 text-slate-600 hover:text-sky-700">
                                        <Phone className="h-4 w-4 text-slate-400" />
                                        {agent.phone}
                                    </a>
                                    <a href={`mailto:${agent.email}`} className="flex items-center gap-2 text-slate-600 hover:text-sky-700">
                                        <Mail className="h-4 w-4 text-slate-400" />
                                        <span className="truncate">{agent.email}</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>

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
