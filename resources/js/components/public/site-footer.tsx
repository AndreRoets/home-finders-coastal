import { Link } from '@inertiajs/react';
import { Facebook, Instagram, Linkedin, Mail, MapPin, Phone } from 'lucide-react';
import { siteNavItems } from './site-nav';

export default function SiteFooter() {
    return (
        <footer className="border-t border-white/10 bg-ink text-neutral-400">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div className="lg:col-span-2">
                    <div className="flex justify-center gap-3">
                        {[Facebook, Instagram, Linkedin].map((Icon, index) => (
                            <a
                                key={index}
                                href="#"
                                className="flex h-[62px] w-[62px] items-center justify-center rounded-full border border-white/10 text-neutral-400 transition-colors hover:border-marine/50 hover:text-marine"
                            >
                                <Icon className="h-[31px] w-[31px]" />
                            </a>
                        ))}
                    </div>
                </div>

                <div>
                    <h3 className="text-xs font-medium tracking-[0.25em] text-marine/80 uppercase">Explore</h3>
                    <ul className="mt-5 space-y-2.5 text-sm">
                        {siteNavItems.map((item) => (
                            <li key={item.routeName}>
                                <Link href={route(item.routeName)} className="text-neutral-400 transition-colors hover:text-marine">
                                    {item.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>

                <div>
                    <h3 className="text-xs font-medium tracking-[0.25em] text-marine/80 uppercase">Get in touch</h3>
                    <ul className="mt-5 space-y-3 text-sm text-neutral-400">
                        <li className="flex items-start gap-2.5">
                            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-marine/70" />
                            12 Beachfront Road, Coastal Bay
                        </li>
                        <li className="flex items-center gap-2.5">
                            <Phone className="h-4 w-4 shrink-0 text-marine/70" />
                            +27 21 000 0000
                        </li>
                        <li className="flex items-center gap-2.5">
                            <Mail className="h-4 w-4 shrink-0 text-marine/70" />
                            hello@homefinderscoastal.com
                        </li>
                    </ul>
                </div>
            </div>

            <div className="border-t border-white/10">
                <div className="mx-auto max-w-7xl px-4 py-6 text-center text-xs tracking-wide text-neutral-500 sm:px-6 lg:px-8">
                    &copy; {new Date().getFullYear()} Home Finders Coastal. All rights reserved.
                </div>
            </div>
        </footer>
    );
}
