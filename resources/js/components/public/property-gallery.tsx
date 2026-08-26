import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight, Expand, X } from 'lucide-react';
import { type ReactNode, useCallback, useEffect, useState } from 'react';

/**
 * Property photo gallery: a large cover image with up to six thumbnails beneath
 * it. When there are more photos than fit, the last thumbnail shows a "+N"
 * overlay. Clicking the cover or any thumbnail opens a full-screen lightbox that
 * steps through every photo (arrows, keyboard, and a thumbnail strip).
 *
 * `onOpen` fires each time the lightbox is opened, so the listing's photo
 * engagement can be counted; stepping between photos is not reported.
 */
export default function PropertyGallery({
    images,
    title,
    badges,
    onOpen,
}: {
    images: string[];
    title: string;
    badges?: ReactNode;
    onOpen?: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(0);

    const cover = images[0];
    const thumbs = images.slice(1, 7);
    // Photos beyond the cover + six thumbnails, surfaced as a "+N" overlay.
    const hiddenCount = Math.max(0, images.length - 7);
    const hasMany = images.length > 1;

    const show = useCallback(
        (index: number) => {
            setActive(index);
            setOpen(true);
            onOpen?.();
        },
        [onOpen],
    );
    const close = useCallback(() => setOpen(false), []);
    const next = useCallback(() => setActive((i) => (i + 1) % images.length), [images.length]);
    const prev = useCallback(() => setActive((i) => (i - 1 + images.length) % images.length), [images.length]);

    useEffect(() => {
        if (!open) {
            return;
        }
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowRight') next();
            if (e.key === 'ArrowLeft') prev();
        };
        window.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        return () => {
            window.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [open, close, next, prev]);

    return (
        <div>
            {/* Cover */}
            <button
                type="button"
                onClick={() => show(0)}
                aria-label="View photos"
                className="group relative block aspect-[4/3] w-full cursor-zoom-in overflow-hidden bg-slate-100 md:aspect-[16/9]"
            >
                <img src={cover} alt={title} className="h-full w-full object-cover" />
                {badges}
                {hasMany && (
                    <span className="bg-ink/50 group-hover:bg-ink/70 absolute right-4 bottom-4 inline-flex items-center gap-1.5 rounded-full border border-white/40 px-3 py-1.5 text-xs font-medium tracking-wide text-white backdrop-blur transition-colors">
                        <Expand className="h-3.5 w-3.5" />
                        View all {images.length} photos
                    </span>
                )}
            </button>

            {/* Thumbnails */}
            {thumbs.length > 0 && (
                <div className="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    {thumbs.map((src, i) => {
                        const index = i + 1;
                        const isLast = i === thumbs.length - 1;
                        const showOverlay = isLast && hiddenCount > 0;

                        return (
                            <button
                                key={index}
                                type="button"
                                onClick={() => show(index)}
                                aria-label={`View image ${index + 1} of ${images.length}`}
                                className="hover:ring-marine relative aspect-[4/3] overflow-hidden rounded-sm bg-slate-100 ring-2 ring-transparent transition"
                            >
                                <img src={src} alt={`${title} ${index + 1}`} loading="lazy" className="h-full w-full object-cover" />
                                {showOverlay && (
                                    <span className="bg-ink/60 absolute inset-0 flex items-center justify-center text-lg font-light text-white backdrop-blur-[1px]">
                                        +{hiddenCount}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>
            )}

            {/* Lightbox */}
            {open && (
                <div className="bg-ink/95 fixed inset-0 z-50 flex flex-col" role="dialog" aria-modal="true" aria-label={`${title} photos`}>
                    <div className="flex items-center justify-between px-4 py-3 text-white sm:px-6">
                        <span className="text-sm tracking-wide text-white/80">
                            {active + 1} / {images.length}
                        </span>
                        <button
                            type="button"
                            onClick={close}
                            aria-label="Close gallery"
                            className="rounded-full p-2 transition-colors hover:bg-white/10"
                        >
                            <X className="h-6 w-6" />
                        </button>
                    </div>

                    <div className="relative flex flex-1 items-center justify-center overflow-hidden px-4 sm:px-16">
                        <img src={images[active]} alt={`${title} ${active + 1}`} className="max-h-full max-w-full object-contain" />

                        {hasMany && (
                            <>
                                <button
                                    type="button"
                                    onClick={prev}
                                    aria-label="Previous photo"
                                    className="absolute left-2 rounded-full bg-white/10 p-2.5 text-white transition-colors hover:bg-white/20 sm:left-5"
                                >
                                    <ChevronLeft className="h-6 w-6" />
                                </button>
                                <button
                                    type="button"
                                    onClick={next}
                                    aria-label="Next photo"
                                    className="absolute right-2 rounded-full bg-white/10 p-2.5 text-white transition-colors hover:bg-white/20 sm:right-5"
                                >
                                    <ChevronRight className="h-6 w-6" />
                                </button>
                            </>
                        )}
                    </div>

                    {hasMany && (
                        <div className="flex gap-2 overflow-x-auto px-4 py-4 sm:px-6">
                            {images.map((src, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    onClick={() => setActive(i)}
                                    aria-label={`View image ${i + 1}`}
                                    aria-current={i === active}
                                    className={cn(
                                        'h-16 w-20 shrink-0 overflow-hidden rounded-sm ring-2 transition',
                                        i === active ? 'ring-marine' : 'opacity-60 ring-transparent hover:opacity-100',
                                    )}
                                >
                                    <img src={src} alt={`${title} ${i + 1}`} className="h-full w-full object-cover" />
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
