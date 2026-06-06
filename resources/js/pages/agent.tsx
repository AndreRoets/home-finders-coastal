import AgentSocials from '@/components/public/agent-socials';
import ArticleCard from '@/components/public/article-card';
import { type Article } from '@/components/public/articles';
import { type Listing } from '@/components/public/listings';
import PropertyGrid from '@/components/public/property-grid';
import { type Testimonial } from '@/components/public/testimonials';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Mail, Phone, Quote, Star } from 'lucide-react';

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
                        <PropertyGrid listings={listings} emptyMessage={`${agent.name} has no active listings right now.`} />
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
                        <h2 className="text-navy text-2xl font-light sm:text-3xl">What clients say</h2>
                        <div className="mt-8 grid gap-6 md:grid-cols-2">
                            {testimonials.map((testimonial) => (
                                <figure key={testimonial.id} className="rounded-sm border border-slate-200 bg-slate-50 p-6">
                                    <Quote className="text-marine/60 h-8 w-8" />
                                    {testimonial.rating !== null && (
                                        <div className="mt-4 flex gap-0.5">
                                            {Array.from({ length: testimonial.rating }).map((_, i) => (
                                                <Star key={i} className="fill-marine text-marine h-4 w-4" />
                                            ))}
                                        </div>
                                    )}
                                    <blockquote className="mt-4 leading-relaxed text-neutral-600">“{testimonial.body}”</blockquote>
                                    <figcaption className="text-navy mt-5 text-sm">— {testimonial.author}</figcaption>
                                </figure>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
