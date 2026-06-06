import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu, Phone, X } from 'lucide-react';
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
        <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85">
            <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <Link href={route('home')} className="flex items-center" aria-label="Home Finders Coastal — The Mandate Company">
                    <img
                        src="/images/hfc-logo.png"
                        alt="Home Finders Coastal — The Mandate Company"
                        className="h-12 w-auto sm:h-14"
                    />
                </Link>

                <nav className="hidden items-center gap-1 lg:flex">
                    {siteNavItems.map((item) => {
                        const link = (
                            <Link
                                href={route(item.routeName)}
                                className={cn(
                                    'relative px-3 py-2 text-[1.05rem] font-medium tracking-wide text-navy transition-colors',
                                    'after:absolute after:inset-x-3 after:bottom-1 after:h-0.5 after:origin-left after:bg-marine after:transition-transform after:duration-300 after:ease-out',
                                    isActive(item.routeName) ? 'after:scale-x-100' : 'after:scale-x-0 hover:after:scale-x-100',
                                )}
                            >
                                {item.title}
                            </Link>
                        );

                        if (!item.children?.length) {
                            return <div key={item.routeName}>{link}</div>;
                        }

                        return (
                            <div key={item.routeName} className="group relative">
                                <div className="flex items-center gap-1">
                                    {link}
                                    <ChevronDown className="pointer-events-none h-4 w-4 text-navy/70 transition-transform group-hover:rotate-180" />
                                </div>
                                <div className="invisible absolute left-0 top-full z-50 min-w-52 translate-y-1 rounded-lg border border-slate-200 bg-white p-1.5 opacity-0 shadow-lg transition-all duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">
                                    {item.children.map((child) => (
                                        <Link
                                            key={child.routeName}
                                            href={route(child.routeName)}
                                            className={cn(
                                                'block rounded-md px-3 py-2 transition-colors',
                                                isActive(child.routeName) ? 'bg-slate-100 text-marine' : 'text-navy hover:bg-slate-100 hover:text-marine',
                                            )}
                                        >
                                            <span className="block text-sm font-medium tracking-wide">{child.title}</span>
                                            {child.description && <span className="mt-0.5 block text-xs text-neutral-500">{child.description}</span>}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </nav>

                <div className="flex items-center gap-2">
                    <a
                        href="tel:+27210000000"
                        className="hidden items-center gap-2 rounded-full bg-navy px-5 py-2 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-navy/90 sm:inline-flex"
                    >
                        <Phone className="h-4 w-4" />
                        Call us
                    </a>
                    <button
                        type="button"
                        onClick={() => setMobileOpen((open) => !open)}
                        className="inline-flex h-10 w-10 items-center justify-center rounded-md text-navy hover:bg-slate-100 hover:text-marine lg:hidden"
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
                            <div key={item.routeName}>
                                <Link
                                    href={route(item.routeName)}
                                    onClick={() => setMobileOpen(false)}
                                    className={cn(
                                        'block rounded-md px-3 py-2.5 text-sm font-medium tracking-wide transition-colors',
                                        isActive(item.routeName) ? 'text-marine' : 'text-navy hover:bg-slate-100 hover:text-marine',
                                    )}
                                >
                                    {item.title}
                                </Link>
                                {item.children?.map((child) => (
                                    <Link
                                        key={child.routeName}
                                        href={route(child.routeName)}
                                        onClick={() => setMobileOpen(false)}
                                        className={cn(
                                            'block rounded-md py-2 pr-3 pl-7 text-sm tracking-wide transition-colors',
                                            isActive(child.routeName) ? 'text-marine' : 'text-navy/80 hover:bg-slate-100 hover:text-marine',
                                        )}
                                    >
                                        {child.title}
                                    </Link>
                                ))}
                            </div>
                        ))}
                    </div>
                </nav>
            )}
        </header>
    );
}
