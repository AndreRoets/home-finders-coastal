import AgentListings from '@/components/public/agent-listings';
import AgentSocials from '@/components/public/agent-socials';
import ArticleCard from '@/components/public/article-card';
import { type Article } from '@/components/public/articles';
import { type Listing } from '@/components/public/listings';
import { type Testimonial } from '@/components/public/testimonials';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ArrowLeft, ChevronLeft, ChevronRight, Mail, Phone, Quote, Star } from 'lucide-react';
import { useState } from 'react';

interface Agent {
    id: number | string;
    name: string;
    designation: string | null;
    phone: string;
    email: string;
    photo: string;
    about: string | null;
    socials: Record<string, string>;
}

/**
 * Graceful fallback when /agents/{id} (and the listing fallback) yield nothing —
 * an unpublished or unknown agent. Served with a 404 status from the controller.
 */
function AgentNotFound() {
    return (
        <PublicLayout title="Agent not found" tone="light">
            <div className="mx-auto max-w-2xl px-4 py-28 text-center sm:px-6 lg:px-8">
                <p className="text-marine text-xs tracking-[0.3em] uppercase">404</p>
                <h1 className="text-navy mt-4 text-3xl font-light sm:text-4xl">We couldn’t find that agent</h1>
                <p className="mx-auto mt-4 max-w-md text-neutral-600">
                    This agent’s profile may no longer be available. Browse the rest of our team instead.
                </p>
                <Link
                    href={route('agents')}
                    className="bg-brand-red hover:bg-brand-red-bright mt-8 inline-flex items-center justify-center rounded-full px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                >
                    Meet our agents
                </Link>
            </div>
        </PublicLayout>
    );
}

/** Testimonials shown per carousel page on an agent profile. */
const TESTIMONIALS_PER_PAGE = 2;

/**
 * Client reviews in pages of two. Agents can accumulate a long tail of reviews,
 * so the profile shows one page at a time with prev/next controls that wrap;
 * with two or fewer reviews the controls are omitted entirely.
 */
function TestimonialsCarousel({ items }: { items: Testimonial[] }) {
    const [page, setPage] = useState(0);
    const pageCount = Math.ceil(items.length / TESTIMONIALS_PER_PAGE);
    const hasControls = pageCount > 1;
    const visible = items.slice(page * TESTIMONIALS_PER_PAGE, page * TESTIMONIALS_PER_PAGE + TESTIMONIALS_PER_PAGE);

    const go = (index: number) => setPage((index + pageCount) % pageCount);

    return (
        <>
            <div className="flex items-center justify-between gap-4">
                <h2 className="text-navy text-2xl font-light sm:text-3xl">What clients say</h2>
                {hasControls && (
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => go(page - 1)}
                            aria-label="Previous testimonials"
                            className="text-navy hover:border-marine hover:text-marine flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white transition-colors"
                        >
                            <ChevronLeft className="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            onClick={() => go(page + 1)}
                            aria-label="Next testimonials"
                            className="text-navy hover:border-marine hover:text-marine flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white transition-colors"
                        >
                            <ChevronRight className="h-5 w-5" />
                        </button>
                    </div>
                )}
            </div>

            <div className="mt-8 grid items-stretch gap-6 md:grid-cols-2">
                {visible.map((testimonial) => (
                    <figure key={testimonial.id} className="h-full rounded-sm border border-slate-200 bg-slate-50 p-6">
                        <Quote className="text-marine/60 h-8 w-8" />
                        {testimonial.rating !== null && (
                            <div className="mt-4 flex gap-0.5">
                                {Array.from({ length: testimonial.rating }).map((_, i) => (
                                    <Star key={i} className="fill-marine text-marine h-4 w-4" />
                                ))}
                            </div>
                        )}
                        <blockquote className="mt-4 leading-relaxed text-pretty text-neutral-600">“{testimonial.body}”</blockquote>
                        <figcaption className="text-navy mt-5 text-sm">— {testimonial.author}</figcaption>
                    </figure>
                ))}
            </div>

            {hasControls && (
                <div className="mt-8 flex justify-center gap-2">
                    {Array.from({ length: pageCount }).map((_, index) => (
                        <button
                            key={index}
                            type="button"
                            onClick={() => go(index)}
                            aria-label={`Show testimonials page ${index + 1}`}
                            aria-current={index === page}
                            className={cn(
                                'h-2 rounded-full transition-all',
                                index === page ? 'bg-marine w-6' : 'w-2 bg-slate-300 hover:bg-slate-400',
                            )}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

export default function AgentDetail({
    agent = null,
    listings = [],
    testimonials = [],
    articles = [],
}: {
    agent?: Agent | null;
    listings?: Listing[];
    testimonials?: Testimonial[];
    articles?: Article[];
}) {
    if (!agent) {
        return <AgentNotFound />;
    }

    return (
        <PublicLayout title={agent.name} tone="light">
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    href={route('agents')}
                    className="hover:text-marine inline-flex items-center gap-2 text-sm font-medium tracking-wide text-neutral-600 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Back to all agents
                </Link>

                {/* Profile */}
                <div className="mt-6 grid gap-8 sm:grid-cols-[auto_1fr] sm:items-center">
                    <div className="h-40 w-40 overflow-hidden rounded-2xl bg-slate-100 sm:h-48 sm:w-48">
                        <img src={agent.photo} alt={agent.name} className="h-full w-full object-cover" />
                    </div>
                    <div>
                        <p className="text-marine text-xs tracking-[0.3em] uppercase">Our Agents</p>
                        <h1 className="text-navy mt-3 text-4xl font-light tracking-tight sm:text-5xl">{agent.name}</h1>
                        {agent.designation && <p className="mt-2 text-lg text-neutral-500">{agent.designation}</p>}
                        <div className="mt-5 flex flex-wrap gap-x-8 gap-y-2 text-sm">
                            {agent.phone && (
                                <a
                                    href={`tel:${agent.phone.replace(/\s/g, '')}`}
                                    className="hover:text-marine flex items-center gap-2 text-neutral-600 transition-colors"
                                >
                                    <Phone className="h-4 w-4 text-neutral-400" />
                                    {agent.phone}
                                </a>
                            )}
                            {agent.email && (
                                <a
                                    href={`mailto:${agent.email}`}
                                    className="hover:text-marine flex items-center gap-2 text-neutral-600 transition-colors"
                                >
                                    <Mail className="h-4 w-4 text-neutral-400" />
                                    <span className="truncate">{agent.email}</span>
                                </a>
                            )}
                        </div>
                        <AgentSocials socials={agent.socials} className="mt-5" />
                    </div>
                </div>

                {/* About */}
                {agent.about && (
                    <section className="mt-16">
                        <h2 className="text-navy text-2xl font-light sm:text-3xl">Get to Know Me</h2>
                        <p className="mt-6 max-w-3xl leading-relaxed whitespace-pre-line text-neutral-600">{agent.about}</p>
                    </section>
                )}

                {/* Listings — always first on the profile */}
                <section className="mt-16">
                    <h2 className="text-navy text-2xl font-light sm:text-3xl">{agent.name.split(' ')[0]}’s listings</h2>
                    <div className="mt-8">
                        <AgentListings listings={listings} agentName={agent.name} />
                    </div>
                </section>

                {/* Articles */}
                {articles.length > 0 && (
                    <section className="mt-16">
                        <h2 className="text-navy text-2xl font-light sm:text-3xl">{agent.name.split(' ')[0]}’s articles</h2>
                        <div className="mt-8 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                            {articles.map((article) => (
                                <ArticleCard key={article.id} article={article} />
                            ))}
                        </div>
                    </section>
                )}

                {/* Testimonials */}
                {testimonials.length > 0 && (
                    <section className="mt-16">
                        <TestimonialsCarousel items={testimonials} />
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
