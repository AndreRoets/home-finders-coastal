import { Link } from '@inertiajs/react';
import { Facebook, Instagram, Linkedin, Mail, MapPin, Phone } from 'lucide-react';
import { siteNavItems } from './site-nav';

export default function SiteFooter() {
    return (
        <footer className="border-t border-slate-200 bg-slate-900 text-slate-300">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div className="lg:col-span-2">
                    <div className="flex items-center gap-2.5">
                        <span className="flex h-9 w-9 items-center justify-center rounded-md bg-sky-600 font-bold text-white">HFC</span>
                        <span className="text-sm font-semibold tracking-tight text-white">Home Finders Coastal</span>
                    </div>
                    <p className="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
                        Your trusted partner for buying, selling, and renting along the coast. Browse exclusive listings and connect with
                        local specialists who know the area best.
                    </p>
                    <div className="mt-5 flex gap-3">
                        {[Facebook, Instagram, Linkedin].map((Icon, index) => (
                            <a
                                key={index}
                                href="#"
                                className="flex h-9 w-9 items-center justify-center rounded-md bg-slate-800 text-slate-300 transition-colors hover:bg-sky-600 hover:text-white"
                            >
                                <Icon className="h-4 w-4" />
                            </a>
                        ))}
                    </div>
                </div>

                <div>
                    <h3 className="text-sm font-semibold text-white">Explore</h3>
                    <ul className="mt-4 space-y-2 text-sm">
                        {siteNavItems.map((item) => (
                            <li key={item.routeName}>
                                <Link href={route(item.routeName)} className="text-slate-400 transition-colors hover:text-white">
                                    {item.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>

                <div>
                    <h3 className="text-sm font-semibold text-white">Get in touch</h3>
                    <ul className="mt-4 space-y-3 text-sm text-slate-400">
                        <li className="flex items-start gap-2.5">
                            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-sky-500" />
                            12 Beachfront Road, Coastal Bay
                        </li>
                        <li className="flex items-center gap-2.5">
                            <Phone className="h-4 w-4 shrink-0 text-sky-500" />
                            +27 21 000 0000
                        </li>
                        <li className="flex items-center gap-2.5">
                            <Mail className="h-4 w-4 shrink-0 text-sky-500" />
                            hello@homefinderscoastal.com
                        </li>
                    </ul>
                </div>
            </div>

            <div className="border-t border-slate-800">
                <div className="mx-auto max-w-7xl px-4 py-5 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
                    &copy; {new Date().getFullYear()} Home Finders Coastal. All rights reserved.
                </div>
            </div>
        </footer>
    );
}
