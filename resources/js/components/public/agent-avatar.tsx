import { cn } from '@/lib/utils';

/**
 * A small circular agent avatar. The agency's agent photos are full studio
 * portraits (head-and-torso with whitespace around the subject). We show them
 * with a centred cover crop anchored to the top so the head and upper body are
 * visible — zoomed out enough to read as a proper headshot rather than just a
 * tight face crop.
 *
 * `className` sizes the circle (e.g. "h-8 w-8"); the crop is intentionally
 * defined once here so every small avatar stays consistent.
 *
 * `eager` opts out of lazy loading. Inside a masked, auto-scrolling strip the
 * browser's lazy heuristic often never marks these as visible, so they stay
 * blank until something forces a reflow — pass `eager` there to load up front.
 */
export default function AgentAvatar({ src, alt, className, eager = false }: { src: string; alt: string; className?: string; eager?: boolean }) {
    return (
        <span className={cn('relative block shrink-0 overflow-hidden rounded-full bg-slate-100', className)}>
            <img src={src} alt={alt} loading={eager ? 'eager' : 'lazy'} className="absolute inset-0 h-full w-full object-cover object-top" />
        </span>
    );
}
