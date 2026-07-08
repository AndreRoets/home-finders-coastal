import { type Agency, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Facebook, Instagram, Linkedin, type LucideIcon, Mail, MapPin, Phone, Youtube } from 'lucide-react';
import { visibleNavItems } from './site-nav';

/** Social networks we render, in display order, paired with their icon. */
const socialIcons: { key: keyof NonNullable<Agency['socials']>; label: string; Icon: LucideIcon }[] = [
    { key: 'facebook', label: 'Facebook', Icon: Facebook },
    { key: 'instagram', label: 'Instagram', Icon: Instagram },
    { key: 'linkedin', label: 'LinkedIn', Icon: Linkedin },
    { key: 'youtube', label: 'YouTube', Icon: Youtube },
];

export default function SiteFooter() {
    const { agency } = usePage<SharedData>().props;

    const socials = agency?.socials ?? {};
    const presentSocials = socialIcons.filter(({ key }) => socials[key]);
    const contact = agency?.contact;

    return (
        <footer className="bg-ink border-t border-white/10 text-neutral-400">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-4 lg:px-8">
                {/* Social icons — only the networks CoreX returns. */}
                {presentSocials.length > 0 && (
                    <div className="lg:col-span-2">
                        <div className="flex justify-center gap-3">
                            {presentSocials.map(({ key, label, Icon }) => (
                                <a
                                    key={key}
                                    href={socials[key]}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label={label}
                                    className="hover:border-marine/50 hover:text-marine flex h-[62px] w-[62px] items-center justify-center rounded-full border border-white/10 text-neutral-400 transition-colors"
                                >
                                    <Icon className="h-[31px] w-[31px]" />
                                </a>
                            ))}
                        </div>
                    </div>
                )}

                <div>
                    <h3 className="text-marine/80 text-xs font-medium tracking-[0.25em] uppercase">Explore</h3>
                    <ul className="mt-5 space-y-2.5 text-sm">
                        {visibleNavItems(agency).map((item) => (
                            <li key={item.routeName}>
                                <Link href={route(item.routeName)} className="hover:text-marine text-neutral-400 transition-colors">
                                    {item.title}
                                </Link>
                            </li>
                        ))}
                        {/* Privacy Policy is linked from the footer only — never the main menu. */}
                        <li>
                            <Link href={route('privacy-policy')} className="hover:text-marine text-neutral-400 transition-colors">
                                Privacy Policy
                            </Link>
                        </li>
                    </ul>
                </div>

                {/* Contact — render the heading only when at least one detail exists. */}
                {contact && (
                    <div>
                        <h3 className="text-marine/80 text-xs font-medium tracking-[0.25em] uppercase">Get in touch</h3>
                        <ul className="mt-5 space-y-3 text-sm text-neutral-400">
                            {contact.address && (
                                <li className="flex items-start gap-2.5">
                                    <MapPin className="text-marine/70 mt-0.5 h-4 w-4 shrink-0" />
                                    {contact.address}
                                </li>
                            )}
                            {contact.phone && (
                                <li className="flex items-center gap-2.5">
                                    <Phone className="text-marine/70 h-4 w-4 shrink-0" />
                                    <a href={`tel:${contact.phoneHref}`} className="hover:text-marine transition-colors">
                                        {contact.phone}
                                    </a>
                                </li>
                            )}
                            {contact.email && (
                                <li className="flex items-center gap-2.5">
                                    <Mail className="text-marine/70 h-4 w-4 shrink-0" />
                                    <a href={`mailto:${contact.email}`} className="hover:text-marine transition-colors">
                                        {contact.email}
                                    </a>
                                </li>
                            )}
                        </ul>
                    </div>
                )}
            </div>

            <div className="border-t border-white/10">
                <div className="mx-auto max-w-7xl px-4 py-6 text-center text-xs tracking-wide text-neutral-500 sm:px-6 lg:px-8">
                    <p>Registered with the PPRA</p>
                    <p className="mt-1">&copy; {new Date().getFullYear()} CorexOS. All rights reserved.</p>
                    <p className="mt-1 text-neutral-600">Operated by R R Technologies</p>
                </div>
            </div>
        </footer>
    );
}
