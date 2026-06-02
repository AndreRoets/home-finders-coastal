import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Menu, Phone, X } from 'lucide-react';
import { useState } from 'react';
import { siteNavItems } from './site-nav';

export default function SiteHeader() {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);

    const isActive = (routeName: string): boolean => {
        // `route(name, params, false)` returns a relative path (SSR-safe — no window access).
        const target = route(routeName, undefined, false);

        return target === '/' ? url === '/' : url.startsWith(target);
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
                    {siteNavItems.map((item) => (
                        <Link
                            key={item.routeName}
                            href={route(item.routeName)}
                            className={cn(
                                'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                isActive(item.routeName) ? 'bg-sky-50 text-sky-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            )}
                        >
                            {item.title}
                        </Link>
                    ))}
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
                        {siteNavItems.map((item) => (
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
                        ))}
                    </div>
                </nav>
            )}
        </header>
    );
}
