import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

/**
 * The subset of Laravel's LengthAwarePaginator payload the public listing pages
 * render. `data` carries the current page's items; the rest drive the controls.
 */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export default function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav className="mt-12 flex items-center justify-between border-t border-slate-200 pt-6" aria-label="Pagination">
            <PageLink href={page.prev_page_url} rel="prev">
                <ChevronLeft className="h-4 w-4" />
                Previous
            </PageLink>

            <span className="text-sm text-neutral-600">
                Page <span className="font-medium text-navy">{page.current_page}</span> of {page.last_page}
            </span>

            <PageLink href={page.next_page_url} rel="next">
                Next
                <ChevronRight className="h-4 w-4" />
            </PageLink>
        </nav>
    );
}

function PageLink({ href, rel, children }: { href: string | null; rel: 'prev' | 'next'; children: React.ReactNode }) {
    const className =
        'inline-flex items-center gap-1.5 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-medium text-navy transition-colors hover:border-marine hover:text-marine';

    if (href === null) {
        return (
            <span className={`${className} cursor-not-allowed opacity-40 hover:border-slate-300 hover:text-navy`} aria-disabled>
                {children}
            </span>
        );
    }

    return (
        <Link href={href} rel={rel} className={className}>
            {children}
        </Link>
    );
}
