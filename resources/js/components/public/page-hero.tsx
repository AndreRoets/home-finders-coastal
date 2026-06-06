import { type PropsWithChildren } from 'react';

interface PageHeroProps {
    eyebrow?: string;
    title: string;
    description?: string;
}

export default function PageHero({ eyebrow, title, description, children }: PropsWithChildren<PageHeroProps>) {
    return (
        <section className="relative overflow-hidden border-b border-slate-200 bg-white">
            <div className="absolute inset-0 bg-gradient-to-br from-slate-100 via-white to-white" />
            <div className="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                {eyebrow && (
                    <p className="flex items-center gap-3 text-xs font-medium tracking-[0.3em] text-marine uppercase">
                        <span className="h-px w-10 bg-marine/60" />
                        {eyebrow}
                    </p>
                )}
                <h1 className="mt-5 text-4xl font-light tracking-tight text-navy sm:text-5xl lg:text-6xl">{title}</h1>
                {description && <p className="mt-5 max-w-2xl text-base leading-relaxed text-neutral-600">{description}</p>}
                {children && <div className="mt-8">{children}</div>}
            </div>
        </section>
    );
}
