import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, Phone, X } from 'lucide-react';
import { useState } from 'react';
import { siteNavItems, type SiteNavItem } from './site-nav';

export default function SiteHeader() {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [mobileSubmenu, setMobileSubmenu] = useState<string | null>(null);

    const isActive = (routeName: string): boolean => {
        // `route(name, params, false)` returns a relative path (SSR-safe — no window access).
        const target = route(routeName, undefined, false);

        return target === '/' ? url === '/' : url.startsWith(target);
    };

    const isParentActive = (item: SiteNavItem): boolean => {
        if (item.children) {
            return item.children.some((child) => isActive(child.routeName));
        }

        return isActive(item.routeName);
    };

    return (
        <header className="sticky top-0 z-50 border-b border-slate-200/70 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/70">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <Link href={route('home')} className="flex items-center gap-2.5" aria-label="Home Finders Coastal home">
                    <span className="flex h-9 w-9 items-center justify-center rounded-md bg-sky-600 font-bold text-white">HFC</span>
                    <span className="hidden flex-col leading-tight sm:flex">
                        <span className="text-sm font-semibold tracking-tight text-slate-900">Home Finders Coastal</span>
                        <span className="text-[11px] tracking-wide text-slate-500 uppercase">Coastal Property Specialists</span>
                    </span>
                </Link>

                <nav className="hidden items-center gap-1 lg:flex">
                    {siteNavItems.map((item) =>
                        item.children ? (
                            <div key={item.title} className="group relative">
                                <button
                                    type="button"
                                    className={cn(
                                        'inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                        isParentActive(item)
                                            ? 'bg-sky-50 text-sky-700'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                    )}
                                >
                                    {item.title}
                                    <ChevronDown className="h-4 w-4 transition-transform group-hover:rotate-180" />
                                </button>
                                <div className="invisible absolute left-0 top-full z-50 w-72 pt-2 opacity-0 transition-all duration-150 group-hover:visible group-hover:opacity-100">
                                    <div className="overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl ring-1 ring-slate-900/5">
                                        {item.children.map((child) => (
                                            <Link
                                                key={child.title}
                                                href={route(child.routeName)}
                                                className={cn(
                                                    'block rounded-lg px-3 py-2.5 transition-colors',
                                                    isActive(child.routeName) ? 'bg-sky-50' : 'hover:bg-slate-50',
                                                )}
                                            >
                                                <span
                                                    className={cn(
                                                        'block text-sm font-semibold',
                                                        isActive(child.routeName) ? 'text-sky-700' : 'text-slate-900',
                                                    )}
                                                >
                                                    {child.title}
                                                </span>
                                                {child.description && (
                                                    <span className="mt-0.5 block text-xs text-slate-500">{child.description}</span>
                                                )}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <Link
                                key={item.routeName}
                                href={route(item.routeName)}
                                className={cn(
                                    'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    isActive(item.routeName)
                                        ? 'bg-sky-50 text-sky-700'
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                )}
                            >
                                {item.title}
                            </Link>
                        ),
                    )}
                </nav>

                <div className="flex items-center gap-2">
                    <a
                        href="tel:+27210000000"
                        className="hidden items-center gap-2 rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-sky-700 sm:inline-flex"
                    >
                        <Phone className="h-4 w-4" />
                        Call us
                    </a>
                    <button
                        type="button"
                        onClick={() => setMobileOpen((open) => !open)}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100 lg:hidden"
                        aria-label="Toggle navigation menu"
                        aria-expanded={mobileOpen}
                    >
                        {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                    </button>
                </div>
            </div>

            {mobileOpen && (
                <nav className="border-t border-slate-200 bg-white lg:hidden">
                    <div className="mx-auto flex max-w-7xl flex-col px-4 py-2 sm:px-6">
                        {siteNavItems.map((item) =>
                            item.children ? (
                                <div key={item.title}>
                                    <button
                                        type="button"
                                        onClick={() => setMobileSubmenu((open) => (open === item.title ? null : item.title))}
                                        className={cn(
                                            'flex w-full items-center justify-between rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                                            isParentActive(item) ? 'bg-sky-50 text-sky-700' : 'text-slate-700 hover:bg-slate-100',
                                        )}
                                        aria-expanded={mobileSubmenu === item.title}
                                    >
                                        {item.title}
                                        <ChevronDown
                                            className={cn('h-4 w-4 transition-transform', mobileSubmenu === item.title && 'rotate-180')}
                                        />
                                    </button>
                                    {mobileSubmenu === item.title && (
                                        <div className="mb-1 ml-3 flex flex-col border-l border-slate-200 pl-3">
                                            {item.children.map((child) => (
                                                <Link
                                                    key={child.title}
                                                    href={route(child.routeName)}
                                                    onClick={() => setMobileOpen(false)}
                                                    className={cn(
                                                        'rounded-md px-3 py-2 text-sm transition-colors',
                                                        isActive(child.routeName)
                                                            ? 'text-sky-700'
                                                            : 'text-slate-600 hover:bg-slate-100',
                                                    )}
                                                >
                                                    {child.title}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <Link
                                    key={item.routeName}
                                    href={route(item.routeName)}
                                    onClick={() => setMobileOpen(false)}
                                    className={cn(
                                        'rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                                        isActive(item.routeName) ? 'bg-sky-50 text-sky-700' : 'text-slate-700 hover:bg-slate-100',
                                    )}
                                >
                                    {item.title}
                                </Link>
                            ),
                        )}
                    </div>
                </nav>
            )}
        </header>
    );
}
