import AgentSocials from '@/components/public/agent-socials';
import { type Article } from '@/components/public/articles';
import PublicLayout from '@/layouts/public-layout';
import { agentUrl, articleUrl } from '@/lib/routes';
import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, Facebook, Linkedin, MessageCircle, Newspaper } from 'lucide-react';

interface ArticleAgent {
    id: number | string;
    name: string;
    designation: string | null;
    photo: string;
    about: string | null;
    socials: Record<string, string>;
}

/**
 * Graceful fallback when a slug matches no published article. Served with a 404
 * status from the controller.
 */
function ArticleNotFound() {
    return (
        <PublicLayout title="Article not found" tone="light">
            <div className="mx-auto max-w-2xl px-4 py-28 text-center sm:px-6 lg:px-8">
                <p className="text-marine text-xs tracking-[0.3em] uppercase">404</p>
                <h1 className="text-navy mt-4 text-3xl font-light sm:text-4xl">We couldn’t find that article</h1>
                <p className="mx-auto mt-4 max-w-md text-neutral-600">This article may no longer be published. Browse our team instead.</p>
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

export default function ArticleDetail({ article = null, agent = null }: { article?: Article | null; agent?: ArticleAgent | null }) {
    if (!article) {
        return <ArticleNotFound />;
    }

    const shareUrl = articleUrl(article.slug);
    const shareText = `${article.title}${agent ? ` — by ${agent.name}` : ''}`;
    const shares = [
        { label: 'Share on Facebook', icon: Facebook, href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}` },
        { label: 'Share on WhatsApp', icon: MessageCircle, href: `https://wa.me/?text=${encodeURIComponent(`${shareText} ${shareUrl}`)}` },
        { label: 'Share on LinkedIn', icon: Linkedin, href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}` },
    ];

    const byline = [agent ? `by ${agent.name}` : null, `${article.readMinutes} MIN`, `${article.wordCount} Words`].filter(Boolean).join(' • ');

    return (
        <PublicLayout title={article.title} tone="light">
            <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
                <Link
                    href={agent ? agentUrl(agent.id) : route('agents')}
                    className="hover:text-marine inline-flex items-center gap-2 text-sm font-medium tracking-wide text-neutral-600 transition-colors"
                >
                    <ArrowLeft className="h-4 w-4" />
                    {agent ? `Back to ${agent.name.split(' ')[0]}’s profile` : 'Back to our agents'}
                </Link>

                <header className="mt-6">
                    <h1 className="text-navy text-3xl font-light tracking-tight sm:text-4xl">{article.title}</h1>
                    <p className="mt-3 text-sm tracking-wide text-neutral-500">{byline}</p>
                </header>

                <div className="mt-8 aspect-[16/9] overflow-hidden bg-slate-100">
                    {article.coverImage ? (
                        <img src={article.coverImage} alt={article.title} className="h-full w-full object-cover" />
                    ) : (
                        <div className="from-marine/15 flex h-full w-full items-center justify-center bg-gradient-to-br via-slate-100 to-slate-200">
                            <Newspaper className="text-marine/40 h-12 w-12" />
                        </div>
                    )}
                </div>

                {article.excerpt && <p className="mt-8 text-lg leading-relaxed text-neutral-700">{article.excerpt}</p>}

                <div className="mt-6 leading-relaxed whitespace-pre-line text-neutral-600">{article.body}</div>

                {article.linkUrl && (
                    <a
                        href={article.linkUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="group bg-brand-red hover:bg-brand-red-bright mt-8 inline-flex items-center gap-2 rounded-full px-7 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                    >
                        Read more
                        <ArrowUpRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                    </a>
                )}

                {article.tags.length > 0 && (
                    <div className="mt-10 flex flex-wrap gap-2">
                        {article.tags.map((tag) => (
                            <span key={tag} className="text-marine rounded-full bg-slate-100 px-3 py-1 text-sm">
                                #{tag}
                            </span>
                        ))}
                    </div>
                )}

                {/* Share */}
                <div className="mt-10 flex items-center gap-3 border-t border-slate-200 pt-8">
                    <span className="text-xs tracking-[0.2em] text-neutral-500 uppercase">Share</span>
                    {shares.map((share) => (
                        <a
                            key={share.label}
                            href={share.href}
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label={share.label}
                            title={share.label}
                            className="hover:border-marine/50 hover:text-marine flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-neutral-500 transition-colors"
                        >
                            <share.icon className="h-5 w-5" />
                        </a>
                    ))}
                </div>

                {/* Author card */}
                {agent && (
                    <aside className="mt-12 rounded-sm border border-slate-200 bg-slate-50 p-6 sm:flex sm:items-center sm:gap-6">
                        <Link href={agentUrl(agent.id)} className="block shrink-0">
                            <img src={agent.photo} alt={agent.name} className="h-20 w-20 rounded-full object-cover" />
                        </Link>
                        <div className="mt-4 sm:mt-0">
                            <p className="text-marine text-xs tracking-[0.2em] uppercase">Written by</p>
                            <Link href={agentUrl(agent.id)}>
                                <p className="text-navy hover:text-marine text-xl font-light transition-colors">{agent.name}</p>
                            </Link>
                            {agent.designation && <p className="text-sm text-neutral-500">{agent.designation}</p>}
                            {agent.about && <p className="mt-3 line-clamp-3 text-sm leading-relaxed text-neutral-600">{agent.about}</p>}
                            <div className="mt-4 flex flex-wrap items-center gap-4">
                                <Link
                                    href={agentUrl(agent.id)}
                                    className="group text-marine hover:text-marine/80 inline-flex items-center gap-2 text-sm tracking-wide transition-colors"
                                >
                                    View My Profile
                                    <ArrowUpRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                </Link>
                                <AgentSocials socials={agent.socials} />
                            </div>
                        </div>
                    </aside>
                )}
            </article>
        </PublicLayout>
    );
}
