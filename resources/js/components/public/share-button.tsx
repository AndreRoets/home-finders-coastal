import { cn } from '@/lib/utils';
import { Check, Facebook, Link2, Mail, Send, Share2, X as XIcon } from 'lucide-react';
import { useEffect, useRef, useState, type ComponentType } from 'react';

interface ShareTarget {
    label: string;
    icon: ComponentType<{ className?: string }>;
    /** Builds the outbound share URL from the page URL and title. */
    href: (url: string, title: string) => string;
}

/**
 * The platforms offered in the fallback panel, in display order. Each opens the
 * platform's standard web-intent share URL with the listing link pre-filled.
 */
const TARGETS: ShareTarget[] = [
    {
        label: 'WhatsApp',
        icon: Send,
        href: (url, title) => `https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}`,
    },
    {
        label: 'Facebook',
        icon: Facebook,
        href: (url) => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
    },
    {
        label: 'X',
        icon: XIcon,
        href: (url, title) => `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
    },
    {
        label: 'Email',
        icon: Mail,
        href: (url, title) => `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(`${title}\n\n${url}`)}`,
    },
];

/**
 * Share control for a listing. Uses the native Web Share sheet when the browser
 * supports it (typically mobile), otherwise falls back to a popover with the
 * usual platforms plus a copy-link action.
 *
 * `onShare` fires once per share intent (the button press), not once per
 * platform — the native sheet gives no way to tell which target was chosen, so
 * counting the intent is the only figure that means the same thing everywhere.
 */
export default function ShareButton({
    title,
    className,
    fullWidth = false,
    onShare,
}: {
    title: string;
    className?: string;
    fullWidth?: boolean;
    onShare?: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    // Close the popover on an outside click or Escape.
    useEffect(() => {
        if (!open) {
            return;
        }

        function handlePointer(event: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        function handleKey(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handlePointer);
        document.addEventListener('keydown', handleKey);

        return () => {
            document.removeEventListener('mousedown', handlePointer);
            document.removeEventListener('keydown', handleKey);
        };
    }, [open]);

    const currentUrl = typeof window !== 'undefined' ? window.location.href : '';

    async function handleClick() {
        onShare?.();

        if (typeof navigator !== 'undefined' && navigator.share) {
            try {
                await navigator.share({ title, url: currentUrl });
                return;
            } catch {
                // User dismissed the native sheet, or it failed — fall through
                // to the popover so they still have a way to share.
            }
        }

        setOpen((value) => !value);
    }

    async function copyLink() {
        try {
            await navigator.clipboard.writeText(currentUrl);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    }

    return (
        <div ref={containerRef} className={cn('relative', className)}>
            <button
                type="button"
                onClick={handleClick}
                aria-haspopup="menu"
                aria-expanded={open}
                className={cn(
                    'hover:border-marine/50 hover:text-marine inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-neutral-600 transition-colors',
                    fullWidth && 'w-full px-6 py-3 font-semibold tracking-wide',
                )}
            >
                <Share2 className="h-4 w-4" />
                Share
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-sm border border-slate-200 bg-white py-1 shadow-lg"
                >
                    {TARGETS.map((target) => (
                        <a
                            key={target.label}
                            href={target.href(currentUrl, title)}
                            target="_blank"
                            rel="noopener noreferrer"
                            role="menuitem"
                            onClick={() => setOpen(false)}
                            className="hover:text-marine flex items-center gap-3 px-4 py-2.5 text-sm text-neutral-600 transition-colors hover:bg-slate-50"
                        >
                            <target.icon className="h-4 w-4 text-neutral-400" />
                            {target.label}
                        </a>
                    ))}
                    <button
                        type="button"
                        role="menuitem"
                        onClick={copyLink}
                        className="hover:text-marine flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-neutral-600 transition-colors hover:bg-slate-50"
                    >
                        {copied ? <Check className="h-4 w-4 text-emerald-500" /> : <Link2 className="h-4 w-4 text-neutral-400" />}
                        {copied ? 'Link copied' : 'Copy link'}
                    </button>
                </div>
            )}
        </div>
    );
}
