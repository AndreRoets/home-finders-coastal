import { agentUrl } from '@/lib/routes';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, ChevronLeft, ChevronRight, Mail, Phone } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AgentAvatar from './agent-avatar';

export interface Agent {
    id: number | string;
    name: string;
    designation: string | null;
    phone: string;
    email: string;
    photo: string;
}

/**
 * "Meet the team" — a spotlight agent that auto-rotates every 5s. Clicking a
 * thumbnail switches the spotlight and holds it for 20s before resuming.
 */
export default function HomeAgents({ agents, tone = 'dark' }: { agents: Agent[]; tone?: 'dark' | 'light' }) {
    const [active, setActive] = useState(0);
    const holdUntil = useRef(0);
    const stripRef = useRef<HTMLDivElement>(null);
    const secondCopyRef = useRef<HTMLButtonElement>(null);
    const paused = useRef(false);
    const light = tone === 'light';

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

    // Continuously drift the thumbnail strip sideways, looping seamlessly. The
    // list is rendered twice; once scrolled past the first copy we subtract its
    // width so the motion never visibly resets.
    useEffect(() => {
        const strip = stripRef.current;
        if (!strip || agents.length === 0) {
            return;
        }
        let raf = 0;
        const step = () => {
            if (!paused.current) {
                const half = secondCopyRef.current?.offsetLeft ?? strip.scrollWidth / 2;
                strip.scrollLeft += 0.4;
                if (half > 0 && strip.scrollLeft >= half) {
                    strip.scrollLeft -= half;
                }
            }
            raf = requestAnimationFrame(step);
        };
        raf = requestAnimationFrame(step);
        return () => cancelAnimationFrame(raf);
    }, [agents.length]);

    if (agents.length === 0) {
        return null;
    }

    const agent = agents[active];

    const select = (index: number) => {
        setActive(index);
        holdUntil.current = Date.now() + 20000;
    };

    const go = (direction: 1 | -1) => {
        select((active + direction + agents.length) % agents.length);
    };

    return (
        <section className={cn('border-t', light ? 'border-slate-200 bg-slate-50' : 'border-white/10 bg-ink-soft/40')}>
            <div className="mx-auto max-w-7xl px-6 py-20 lg:px-8">
                <p className="text-xs tracking-[0.3em] text-marine/80 uppercase">Our Agents</p>
                <div className="mt-4 flex flex-wrap items-end justify-between gap-4">
                    <h2 className={cn('text-3xl font-light sm:text-4xl', light ? 'text-navy' : 'text-white')}>Meet the team</h2>
                    <Link
                        href={route('agents')}
                        className={cn(
                            'group inline-flex items-center gap-2 text-sm tracking-wide transition-colors hover:text-marine',
                            light ? 'text-neutral-600' : 'text-neutral-300',
                        )}
                    >
                        Meet all agents
                        <ArrowUpRight className="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                    </Link>
                </div>

                <div className="mt-10 grid gap-8 md:grid-cols-[1fr_1.1fr] md:items-center">
                    <Link
                        href={agentUrl(agent.id)}
                        className={cn('group aspect-square overflow-hidden rounded-2xl', light ? 'bg-slate-200' : 'bg-ink-soft')}
                    >
                        <img
                            key={agent.id}
                            src={agent.photo}
                            alt={agent.name}
                            className="h-full w-full animate-in object-cover fade-in zoom-in-105 duration-700 ease-out group-hover:scale-105"
                        />
                    </Link>
                    <div className="min-w-0">
                        <div key={agent.id} className="animate-in fade-in slide-in-from-bottom-3 duration-500 ease-out">
                            <Link href={agentUrl(agent.id)}>
                                <h3 className={cn('text-3xl font-light transition-colors hover:text-marine sm:text-4xl', light ? 'text-navy' : 'text-white')}>
                                    {agent.name}
                                </h3>
                            </Link>
                            {agent.designation && <p className={cn('mt-2', light ? 'text-neutral-500' : 'text-neutral-400')}>{agent.designation}</p>}
                            <div className="mt-5 space-y-2 text-sm">
                                {agent.phone && (
                                    <a
                                        href={`tel:${agent.phone.replace(/\s/g, '')}`}
                                        className={cn(
                                            'flex items-center gap-2 transition-colors hover:text-marine',
                                            light ? 'text-neutral-600' : 'text-neutral-300',
                                        )}
                                    >
                                        <Phone className="h-4 w-4 text-neutral-500" />
                                        {agent.phone}
                                    </a>
                                )}
                                {agent.email && (
                                    <a
                                        href={`mailto:${agent.email}`}
                                        className={cn(
                                            'flex items-center gap-2 transition-colors hover:text-marine',
                                            light ? 'text-neutral-600' : 'text-neutral-300',
                                        )}
                                    >
                                        <Mail className="h-4 w-4 text-neutral-500" />
                                        <span className="truncate">{agent.email}</span>
                                    </a>
                                )}
                            </div>
                            <Link
                                href={agentUrl(agent.id)}
                                className="group mt-5 inline-flex items-center gap-2 text-sm tracking-wide text-marine transition-colors hover:text-marine/80"
                            >
                                View profile
                                <ArrowUpRight className="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
                            </Link>
                        </div>
                        <div className="mt-7 flex items-center gap-2">
                            <button
                                type="button"
                                onClick={() => go(-1)}
                                aria-label="Previous agent"
                                className={cn(
                                    'shrink-0 rounded-full border p-1.5 transition hover:text-marine',
                                    light ? 'border-slate-300 text-neutral-600' : 'border-white/20 text-neutral-300',
                                )}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </button>
                            <div
                                ref={stripRef}
                                onMouseEnter={() => (paused.current = true)}
                                onMouseLeave={() => (paused.current = false)}
                                className="flex min-w-0 flex-1 gap-3 overflow-x-auto pb-2 [mask-image:linear-gradient(to_right,transparent,black_1.5rem,black_calc(100%-1.5rem),transparent)] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                            >
                                {[...agents, ...agents].map((option, i) => {
                                    const index = i % agents.length;
                                    return (
                                        <button
                                            key={i}
                                            ref={i === agents.length ? secondCopyRef : undefined}
                                            type="button"
                                            onClick={() => select(index)}
                                            aria-label={option.name}
                                            aria-pressed={index === active}
                                            className={cn(
                                                'h-12 w-12 shrink-0 rounded-full ring-2 transition',
                                                index === active ? 'ring-marine' : 'opacity-60 ring-transparent hover:opacity-100',
                                            )}
                                        >
                                            <AgentAvatar src={option.photo} alt="" className="h-full w-full" eager />
                                        </button>
                                    );
                                })}
                            </div>
                            <button
                                type="button"
                                onClick={() => go(1)}
                                aria-label="Next agent"
                                className={cn(
                                    'shrink-0 rounded-full border p-1.5 transition hover:text-marine',
                                    light ? 'border-slate-300 text-neutral-600' : 'border-white/20 text-neutral-300',
                                )}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
