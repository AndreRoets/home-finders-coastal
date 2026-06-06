import { articleUrl } from '@/lib/routes';
import { Link } from '@inertiajs/react';
import { Newspaper } from 'lucide-react';
import { type Article } from './articles';

/**
 * An article preview card: cover image (or a gradient placeholder when none),
 * title, excerpt and a "read time • word count" meta line. The whole card links
 * to the full article page.
 */
export default function ArticleCard({ article }: { article: Article }) {
    return (
        <Link href={articleUrl(article.slug)} className="group block">
            <div className="relative aspect-[8/5] overflow-hidden bg-slate-100">
                {article.coverImage ? (
                    <img
                        src={article.coverImage}
                        alt={article.title}
                        loading="lazy"
                        className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                    />
                ) : (
                    <div className="from-marine/15 flex h-full w-full items-center justify-center bg-gradient-to-br via-slate-100 to-slate-200">
                        <Newspaper className="text-marine/40 h-10 w-10" />
                    </div>
                )}
            </div>
            <h3 className="text-navy group-hover:text-marine mt-5 line-clamp-2 text-xl font-light transition-colors">{article.title}</h3>
            {article.excerpt && <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-neutral-600">{article.excerpt}</p>}
            <p className="mt-4 border-t border-slate-200 pt-4 text-xs tracking-wide text-neutral-500 uppercase">
                {article.readMinutes} Min &bull; {article.wordCount} Words
            </p>
        </Link>
    );
}
