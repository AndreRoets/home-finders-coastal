import { cn } from '@/lib/utils';
import { type PropsWithChildren } from 'react';

interface PageHeroProps {
    eyebrow?: string;
    title: string;
    description?: string;
    /** Optional background photo. When set, the hero darkens for white text instead of the light gradient. */
    image?: string;
}

export default function PageHero({ eyebrow, title, description, image, children }: PropsWithChildren<PageHeroProps>) {
    const hasImage = Boolean(image);

    return (
        <section className={cn('relative overflow-hidden border-b', hasImage ? 'border-white/10' : 'border-slate-200 bg-white')}>
            {hasImage ? (
                <>
                    <img src={image} alt="" loading="lazy" className="absolute inset-0 h-full w-full object-cover" />
                    <div className="absolute inset-0 bg-gradient-to-br from-ink/85 via-ink/65 to-ink/45" />
                </>
            ) : (
                <div className="absolute inset-0 bg-gradient-to-br from-slate-100 via-white to-white" />
            )}
            <div className="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                {eyebrow && (
                    <p className={cn('flex items-center gap-3 text-xs font-medium tracking-[0.3em] uppercase', hasImage ? 'text-marine/90' : 'text-marine')}>
                        <span className="h-px w-10 bg-marine/60" />
                        {eyebrow}
                    </p>
                )}
                <h1 className={cn('mt-5 text-4xl font-light tracking-tight sm:text-5xl lg:text-6xl', hasImage ? 'text-white' : 'text-navy')}>{title}</h1>
                {description && (
                    <p className={cn('mt-5 max-w-2xl text-base leading-relaxed', hasImage ? 'text-neutral-200' : 'text-neutral-600')}>{description}</p>
                )}
                {children && <div className="mt-8">{children}</div>}
            </div>
        </section>
    );
}
