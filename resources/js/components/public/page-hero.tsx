import { type PropsWithChildren } from 'react';

interface PageHeroProps {
    eyebrow?: string;
    title: string;
    description?: string;
}

export default function PageHero({ eyebrow, title, description, children }: PropsWithChildren<PageHeroProps>) {
    return (
        <section className="border-b border-slate-200 bg-gradient-to-br from-slate-900 via-slate-800 to-sky-900">
            <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20">
                {eyebrow && <p className="text-sm font-semibold tracking-wide text-sky-400 uppercase">{eyebrow}</p>}
                <h1 className="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">{title}</h1>
                {description && <p className="mt-4 max-w-2xl text-base leading-relaxed text-slate-300">{description}</p>}
                {children && <div className="mt-8">{children}</div>}
            </div>
        </section>
    );
}
