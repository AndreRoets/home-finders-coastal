import { cn } from '@/lib/utils';
import { Facebook, Globe, Instagram, Linkedin, type LucideIcon, Youtube } from 'lucide-react';

/**
 * Known social platforms → their lucide icon. Anything CoreX sends that isn't
 * mapped here falls back to a generic globe so a new platform never breaks the
 * row. Labels drive the accessible link text.
 */
const PLATFORMS: Record<string, { icon: LucideIcon; label: string }> = {
    facebook: { icon: Facebook, label: 'Facebook' },
    instagram: { icon: Instagram, label: 'Instagram' },
    linkedin: { icon: Linkedin, label: 'LinkedIn' },
    youtube: { icon: Youtube, label: 'YouTube' },
};

/**
 * Renders an agent's social links as outbound platform icons. The server only
 * sends the platforms the agent filled in (already normalised to absolute
 * URLs), so an empty object renders nothing.
 */
export default function AgentSocials({ socials, className }: { socials: Record<string, string>; className?: string }) {
    const entries = Object.entries(socials).filter(([, url]) => url);

    if (entries.length === 0) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap gap-3', className)}>
            {entries.map(([platform, url]) => {
                const { icon: Icon, label } = PLATFORMS[platform] ?? { icon: Globe, label: platform };

                return (
                    <a
                        key={platform}
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={label}
                        title={label}
                        className="hover:border-marine/50 hover:text-marine flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-neutral-500 transition-colors"
                    >
                        <Icon className="h-5 w-5" />
                    </a>
                );
            })}
        </div>
    );
}
