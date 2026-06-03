import { sampleListings, type Listing } from '@/components/public/listings';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { ArrowRight, Bath, BedDouble, ChevronDown, Maximize } from 'lucide-react';
import { useEffect, useRef, useState, type PropsWithChildren } from 'react';

/** Reveals children with a fade-and-rise the first time they scroll into view. */
function Reveal({ children, delay = 0 }: PropsWithChildren<{ delay?: number }>) {
    const ref = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const node = ref.current;
        if (!node) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15 },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, []);

    return (
        <div
            ref={ref}
            style={{ transitionDelay: `${delay}ms` }}
            className={`transition-all duration-700 ease-out ${visible ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'}`}
        >
            {children}
        </div>
    );
}

/** Counts up to `target` once scrolled into view. */
function Counter({ target, suffix = '', prefix = '' }: { target: number; suffix?: string; prefix?: string }) {
    const ref = useRef<HTMLSpanElement>(null);
    const [value, setValue] = useState(0);

    useEffect(() => {
        const node = ref.current;
        if (!node) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting) {
                    return;
                }

                observer.disconnect();
                const duration = 1400;
                let start: number | null = null;

                const tick = (timestamp: number) => {
                    if (start === null) {
                        start = timestamp;
                    }
                    const progress = Math.min((timestamp - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    setValue(Math.round(eased * target));
                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            },
            { threshold: 0.5 },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [target]);

    return (
        <span ref={ref}>
            {prefix}
            {value.toLocaleString()}
            {suffix}
        </span>
    );
}

const stats = [
    { target: 480, suffix: '+', label: 'Homes placed' },
    { target: 21, suffix: ' yrs', label: 'On the coast' },
    { target: 32, suffix: '', label: 'Local agents' },
    { target: 98, suffix: '%', label: 'Would refer us' },
];

const steps = [
    { no: '01', title: 'Tell us your dream', copy: 'Share what matters — the view, the schools, the morning swim.' },
    { no: '02', title: 'We curate matches', copy: 'Local agents hand-pick homes and alert you the moment they list.' },
    { no: '03', title: 'Move with confidence', copy: 'From first viewing to keys in hand, we’re beside you all the way.' },
];

export default function HomeMotion({ featured = [] }: { featured?: Listing[] }) {
    const listings = featured.length > 0 ? featured : sampleListings;
    const [scrollProgress, setScrollProgress] = useState(0);
    const [heroOffset, setHeroOffset] = useState(0);

    useEffect(() => {
        const onScroll = () => {
            const scrollTop = window.scrollY;
            const height = document.documentElement.scrollHeight - window.innerHeight;
            setScrollProgress(height > 0 ? (scrollTop / height) * 100 : 0);
            setHeroOffset(scrollTop * 0.4);
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <PublicLayout title="Your Coastal Journey">
            {/* Scroll progress bar */}
            <div className="fixed top-0 left-0 z-[60] h-1 bg-gradient-to-r from-sky-400 to-indigo-500" style={{ width: `${scrollProgress}%` }} />

            <div className="bg-slate-950 text-white">
                {/* Parallax hero */}
                <section className="relative flex h-screen items-center justify-center overflow-hidden">
                    <img
                        src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=75"
                        alt="Coastline"
                        className="absolute inset-0 h-[120%] w-full object-cover will-change-transform"
                        style={{ transform: `translateY(${heroOffset}px)` }}
                    />
                    <div className="absolute inset-0 bg-gradient-to-b from-slate-950/50 via-slate-950/40 to-slate-950" />
                    <div className="relative mx-auto max-w-3xl px-6 text-center">
                        <Reveal>
                            <p className="text-sm font-medium tracking-[0.3em] text-sky-300 uppercase">Home Finders Coastal</p>
                        </Reveal>
                        <Reveal delay={120}>
                            <h1 className="mt-6 text-5xl font-bold tracking-tight sm:text-6xl lg:text-7xl">
                                Scroll into your
                                <span className="block bg-gradient-to-r from-sky-300 to-indigo-300 bg-clip-text text-transparent">
                                    coastal story.
                                </span>
                            </h1>
                        </Reveal>
                        <Reveal delay={240}>
                            <p className="mx-auto mt-6 max-w-lg text-lg text-slate-300">
                                A guided journey to the home that’s been waiting for you by the sea.
                            </p>
                        </Reveal>
                        <Reveal delay={360}>
                            <Link
                                href={route('for-sale')}
                                className="group mt-9 inline-flex items-center gap-2 rounded-full bg-white px-7 py-3.5 font-semibold text-slate-900 transition-transform hover:scale-105"
                            >
                                Begin exploring
                                <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </Reveal>
                    </div>

                    {/* Bouncing scroll cue */}
                    <div className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce text-white/70">
                        <ChevronDown className="h-7 w-7" />
                    </div>
                </section>

                {/* Animated counters */}
                <section className="border-y border-white/10 bg-slate-900">
                    <div className="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-6 py-16 lg:grid-cols-4 lg:px-8">
                        {stats.map((stat, i) => (
                            <Reveal key={stat.label} delay={i * 100}>
                                <div className="text-center">
                                    <p className="text-4xl font-bold text-sky-400 sm:text-5xl">
                                        <Counter target={stat.target} suffix={stat.suffix} />
                                    </p>
                                    <p className="mt-2 text-sm text-slate-400">{stat.label}</p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* Steps timeline */}
                <section className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                    <Reveal>
                        <h2 className="text-center text-3xl font-bold tracking-tight sm:text-4xl">How it works</h2>
                    </Reveal>
                    <div className="mt-16 grid gap-10 md:grid-cols-3">
                        {steps.map((step, i) => (
                            <Reveal key={step.no} delay={i * 150}>
                                <div className="relative rounded-3xl border border-white/10 bg-white/5 p-8 transition-transform duration-300 hover:-translate-y-2">
                                    <span className="text-5xl font-bold text-white/10">{step.no}</span>
                                    <h3 className="mt-4 text-xl font-bold">{step.title}</h3>
                                    <p className="mt-2 text-slate-400">{step.copy}</p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* Featured listings — staggered reveal */}
                <section className="mx-auto max-w-7xl px-6 pb-24 lg:px-8">
                    <Reveal>
                        <div className="flex items-end justify-between">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Homes worth the scroll</h2>
                            <Link href={route('for-sale')} className="hidden text-sm font-semibold text-sky-400 sm:block">
                                View all &rarr;
                            </Link>
                        </div>
                    </Reveal>
                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {listings.slice(0, 6).map((listing, i) => (
                            <Reveal key={listing.id} delay={(i % 3) * 120}>
                                <Link
                                    href={route('property.show', listing.ref ?? listing.id)}
                                    className="group block overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-indigo-900/30"
                                >
                                    <div className="relative aspect-[4/3] overflow-hidden">
                                        <img
                                            src={listing.image}
                                            alt={listing.title}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        />
                                    </div>
                                    <div className="p-5">
                                        <p className="text-lg font-bold text-sky-400">{listing.price}</p>
                                        <h3 className="mt-1 line-clamp-1 font-semibold">{listing.title}</h3>
                                        <p className="mt-1 text-sm text-slate-400">{listing.location}</p>
                                        <div className="mt-4 flex items-center gap-4 border-t border-white/10 pt-4 text-sm text-slate-400">
                                            <span className="flex items-center gap-1.5">
                                                <BedDouble className="h-4 w-4" /> {listing.beds}
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <Bath className="h-4 w-4" /> {listing.baths}
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <Maximize className="h-4 w-4" /> {listing.area}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* CTA */}
                <section className="relative overflow-hidden border-t border-white/10">
                    <div className="mx-auto max-w-3xl px-6 py-24 text-center lg:px-8">
                        <Reveal>
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Your next chapter is by the sea</h2>
                            <p className="mx-auto mt-4 max-w-lg text-slate-400">
                                Let’s find the home where your story continues. It starts with a conversation.
                            </p>
                            <Link
                                href={route('contact')}
                                className="group mt-9 inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-sky-500 to-indigo-500 px-8 py-3.5 font-semibold text-white transition-transform hover:scale-105"
                            >
                                Get in touch
                                <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </Reveal>
                    </div>
                </section>
            </div>
        </PublicLayout>
    );
}
