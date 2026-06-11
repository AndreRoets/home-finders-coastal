import { cn } from '@/lib/utils';

/**
 * A small circular agent avatar. The agency's agent photos are full studio
 * portraits (head-and-torso with whitespace around the subject), which look
 * tiny and distant when shown small with a plain centre crop. This zooms in on
 * the upper body — image at 150% width, centred, pulled up so the crop sits
 * just above the head — so small avatars read as proper face-focused headshots.
 *
 * `className` sizes the circle (e.g. "h-8 w-8"); the zoom is intentionally
 * defined once here so every small avatar stays consistent.
 *
 * `eager` opts out of lazy loading. Inside a masked, auto-scrolling strip the
 * browser's lazy heuristic often never marks these as visible, so they stay
 * blank until something forces a reflow — pass `eager` there to load up front.
 */
export default function AgentAvatar({ src, alt, className, eager = false }: { src: string; alt: string; className?: string; eager?: boolean }) {
    return (
        <span className={cn('relative block shrink-0 overflow-hidden rounded-full bg-slate-100', className)}>
            <img src={src} alt={alt} loading={eager ? 'eager' : 'lazy'} className="absolute top-[-35%] left-1/2 w-[150%] max-w-none -translate-x-1/2" />
        </span>
    );
}
