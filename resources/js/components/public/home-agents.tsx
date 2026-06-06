import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, Mail, Phone } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export interface Agent {
    id: number | string;
    name: string;
    phone: string;
    email: string;
    photo: string;
}

/**
 * "Meet the team" — a spotlight agent that auto-rotates every 5s. Clicking a
 * thumbnail switches the spotlight and holds it for 20s before resuming.
 */
export default function HomeAgents({ agents }: { agents: Agent[] }) {
    const [active, setActive] = useState(0);
    const holdUntil = useRef(0);

    useEffect(() => {
        if (agents.length === 0) {
            return;
        }
        const id = setInterval(() => {
            if (Date.now() < holdUntil.current) {
                return;
            }
            setActive((index) => (index + 1) % agents.length);
        }, 5000);
        return () => clearInterval(id);
    }, [agents.length]);

    if (agents.length === 0) {
        return null;
    }

    const agent = agents[active];

    const select = (index: number) => {
        setActive(index);
        holdUntil.current = Date.now() + 20000;
    };

    return (
        <section className="border-t border-white/10 bg-ink-soft/40">
            <div className="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <p className="text-xs tracking-[0.3em] text-marine/80 uppercase">Our Agents</p>
                <div className="mt-4 flex flex-wrap items-end justify-between gap-4">
                    <h2 className="text-3xl font-light text-white sm:text-4xl">Meet the team</h2>
                    <Link
                        href={route('agents')}
                        className="group inline-flex items-center gap-2 text-sm tracking-wide text-neutral-300 transition-colors hover:text-marine"
                    >
                        Meet all agents
                        <ArrowUpRight className="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                    </Link>
                </div>

                <div className="mt-10 grid gap-8 md:grid-cols-[1fr_1.1fr] md:items-center">
                    <div className="aspect-[4/3] overflow-hidden rounded-sm bg-ink-soft">
                        <img
                            key={agent.id}
                            src={agent.photo}
                            alt={agent.name}
                            className="h-full w-full animate-in object-cover fade-in zoom-in-105 duration-700 ease-out"
                        />
                    </div>
                    <div>
                        <div key={agent.id} className="animate-in fade-in slide-in-from-bottom-3 duration-500 ease-out">
                            <h3 className="text-3xl font-light text-white sm:text-4xl">{agent.name}</h3>
                            <p className="mt-2 text-neutral-400">Coastal property specialist</p>
                            <div className="mt-5 space-y-2 text-sm">
                                {agent.phone && (
                                    <a
                                        href={`tel:${agent.phone.replace(/\s/g, '')}`}
                                        className="flex items-center gap-2 text-neutral-300 transition-colors hover:text-marine"
                                    >
                                        <Phone className="h-4 w-4 text-neutral-500" />
                                        {agent.phone}
                                    </a>
                                )}
                                {agent.email && (
                                    <a
                                        href={`mailto:${agent.email}`}
                                        className="flex items-center gap-2 text-neutral-300 transition-colors hover:text-marine"
                                    >
                                        <Mail className="h-4 w-4 text-neutral-500" />
                                        <span className="truncate">{agent.email}</span>
                                    </a>
                                )}
                            </div>
                        </div>
                        <div className="mt-7 flex flex-wrap gap-3">
                            {agents.map((option, index) => (
                                <button
                                    key={option.id}
                                    type="button"
                                    onClick={() => select(index)}
                                    aria-label={option.name}
                                    aria-pressed={index === active}
                                    className={cn(
                                        'h-12 w-12 overflow-hidden rounded-full ring-2 transition',
                                        index === active ? 'ring-marine' : 'opacity-60 ring-transparent hover:opacity-100',
                                    )}
                                >
                                    <img src={option.photo} alt="" className="h-full w-full object-cover" />
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
