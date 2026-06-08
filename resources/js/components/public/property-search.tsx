import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { ChevronDown, Search } from 'lucide-react';
import { type FormEvent, type KeyboardEvent, type PointerEvent, useRef, useState } from 'react';

export interface SearchFacets {
    suburbs: string[];
    propertyTypes: string[];
    maxBeds: number;
    maxBaths: number;
    price: {
        sale: { min: number; max: number };
        rent: { min: number; max: number };
    };
}

export interface SearchValues {
    q?: string;
    suburb?: string;
    type?: string;
    beds?: number;
    baths?: number;
    min_price?: number | null;
    max_price?: number | null;
}

type Mode = 'sale' | 'rent';

interface PropertySearchProps {
    filters: SearchFacets;
    mode?: Mode;
    values?: SearchValues;
    variant?: 'hero' | 'page';
    /** When set, the active branch is preserved in the search so results stay scoped to it. */
    branchId?: number | string | null;
}

const EMPTY_FACETS: SearchFacets = {
    suburbs: [],
    propertyTypes: [],
    maxBeds: 0,
    maxBaths: 0,
    price: { sale: { min: 0, max: 0 }, rent: { min: 0, max: 0 } },
};

export default function PropertySearch({
    filters = EMPTY_FACETS,
    mode: initialMode = 'sale',
    values,
    variant = 'hero',
    branchId = null,
}: PropertySearchProps) {
    const [mode, setMode] = useState<Mode>(initialMode);
    const [suburb, setSuburb] = useState(values?.suburb ?? '');
    const [type, setType] = useState(values?.type ?? '');
    const [beds, setBeds] = useState(values?.beds ?? 0);
    const [baths, setBaths] = useState(values?.baths ?? 0);
    const [q, setQ] = useState(values?.q ?? '');

    const range = mode === 'sale' ? filters.price.sale : filters.price.rent;
    const hasPrice = range.max > range.min;
    const step = mode === 'sale' ? 250000 : 500;

    const [minPrice, setMinPrice] = useState(values?.min_price ?? range.min);
    const [maxPrice, setMaxPrice] = useState(values?.max_price ?? range.max);

    const switchMode = (next: Mode) => {
        if (next === mode) {
            return;
        }
        const nextRange = next === 'sale' ? filters.price.sale : filters.price.rent;
        setMode(next);
        setMinPrice(nextRange.min);
        setMaxPrice(nextRange.max);
    };

    const formatPrice = (value: number): string => {
        const formatted = `R ${value.toLocaleString('en-ZA')}`;
        return mode === 'rent' ? `${formatted} /mo` : formatted;
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        const params: Record<string, string | number> = {};
        if (q.trim()) params.q = q.trim();
        if (suburb) params.suburb = suburb;
        if (type) params.type = type;
        if (beds > 0) params.beds = beds;
        if (baths > 0) params.baths = baths;
        if (hasPrice && minPrice > range.min) params.min_price = minPrice;
        if (hasPrice && maxPrice < range.max) params.max_price = maxPrice;
        if (branchId !== null && branchId !== undefined && branchId !== '') params.branch_id = branchId;

        router.get(route(mode === 'sale' ? 'for-sale' : 'to-rent'), params, { preserveScroll: false });
    };

    // The hero variant floats over the dark photographic hero (white text); the
    // page variant sits on the white page background (dark text).
    const light = variant === 'page';

    const container = light ? 'border-slate-200 bg-white shadow-sm' : 'border-white/15 bg-white/10 backdrop-blur';

    return (
        <form onSubmit={handleSubmit} className={cn('w-full rounded-2xl border p-4 text-left sm:p-6', container)}>
            {/* Buy / Rent */}
            <div className={cn('inline-flex rounded-full border p-1', light ? 'border-slate-200 bg-slate-100' : 'border-white/20 bg-white/15')}>
                {(['sale', 'rent'] as const).map((m) => (
                    <button
                        key={m}
                        type="button"
                        onClick={() => switchMode(m)}
                        className={cn(
                            'rounded-full px-5 py-1.5 text-sm font-medium tracking-wide transition-colors',
                            mode === m ? 'bg-navy text-white' : light ? 'hover:text-navy text-neutral-600' : 'text-neutral-200 hover:text-white',
                        )}
                    >
                        {m === 'sale' ? 'Buy' : 'Rent'}
                    </button>
                ))}
            </div>

            {/* Selects */}
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Field label="Location" light={light}>
                    <SelectControl value={suburb} onChange={setSuburb} light={light}>
                        <option value="">Any area</option>
                        {filters.suburbs.map((s) => (
                            <option key={s} value={s}>
                                {s}
                            </option>
                        ))}
                    </SelectControl>
                </Field>

                <Field label="Property type" light={light}>
                    <SelectControl value={type} onChange={setType} light={light}>
                        <option value="">Any type</option>
                        {filters.propertyTypes.map((t) => (
                            <option key={t} value={t}>
                                {t}
                            </option>
                        ))}
                    </SelectControl>
                </Field>

                <Field label="Bedrooms" light={light}>
                    <SelectControl value={String(beds)} onChange={(v) => setBeds(Number(v))} light={light}>
                        <option value="0">Any</option>
                        {Array.from({ length: filters.maxBeds }, (_, i) => i + 1).map((n) => (
                            <option key={n} value={n}>
                                {n}+
                            </option>
                        ))}
                    </SelectControl>
                </Field>

                <Field label="Bathrooms" light={light}>
                    <SelectControl value={String(baths)} onChange={(v) => setBaths(Number(v))} light={light}>
                        <option value="0">Any</option>
                        {Array.from({ length: filters.maxBaths }, (_, i) => i + 1).map((n) => (
                            <option key={n} value={n}>
                                {n}+
                            </option>
                        ))}
                    </SelectControl>
                </Field>
            </div>

            {/* Price range */}
            {hasPrice && (
                <div className="mt-6">
                    <div
                        className={cn(
                            'flex items-center justify-between text-xs tracking-[0.15em] uppercase',
                            light ? 'text-neutral-700' : 'text-white',
                        )}
                    >
                        <span>Price range</span>
                        <span className={cn('tracking-normal normal-case', light ? 'text-neutral-500' : 'text-neutral-200')}>
                            {formatPrice(minPrice)} — {formatPrice(maxPrice)}
                        </span>
                    </div>
                    <PriceRange
                        light={light}
                        min={range.min}
                        max={range.max}
                        step={step}
                        minValue={minPrice}
                        maxValue={maxPrice}
                        onMinChange={(v) => setMinPrice(Math.min(v, maxPrice - step))}
                        onMaxChange={(v) => setMaxPrice(Math.max(v, minPrice + step))}
                    />
                </div>
            )}

            {/* Keyword + submit */}
            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div
                    className={cn(
                        'flex flex-1 items-center gap-3 rounded-full border px-4',
                        light ? 'border-slate-300 bg-white' : 'bg-ink/30 border-white/15',
                    )}
                >
                    <Search className={cn('h-5 w-5 shrink-0', light ? 'text-neutral-500' : 'text-white')} />
                    <input
                        type="text"
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        placeholder="Keyword — suburb, area or feature…"
                        aria-label="Search keyword"
                        className={cn(
                            'w-full bg-transparent py-2.5 text-sm outline-none placeholder:text-neutral-400',
                            light ? 'text-neutral-800' : 'text-white',
                        )}
                    />
                </div>
                <button
                    type="submit"
                    className="bg-navy hover:bg-navy/90 inline-flex items-center justify-center gap-2 rounded-full px-8 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                >
                    <Search className="h-4 w-4" />
                    Search
                </button>
            </div>
        </form>
    );
}

function Field({ label, light, children }: { label: string; light: boolean; children: React.ReactNode }) {
    return (
        <label className="block">
            <span className={cn('mb-1.5 block text-xs tracking-[0.15em] uppercase', light ? 'text-neutral-700' : 'text-white')}>{label}</span>
            {children}
        </label>
    );
}

function SelectControl({
    value,
    onChange,
    light,
    children,
}: {
    value: string;
    onChange: (value: string) => void;
    light: boolean;
    children: React.ReactNode;
}) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className={cn(
                    'focus:border-marine w-full appearance-none rounded-lg border px-3.5 py-2.5 text-sm transition-colors outline-none',
                    light ? 'border-slate-300 bg-white text-neutral-800' : 'bg-ink/40 border-white/15 text-white',
                )}
            >
                {children}
            </select>
            <ChevronDown
                className={cn(
                    'pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2',
                    light ? 'text-neutral-500' : 'text-neutral-400',
                )}
            />
        </div>
    );
}

/**
 * A dual-handle price range slider driven by pointer events rather than two
 * overlapping native `<input type="range">` elements. Native overlapping ranges
 * are unreliable to drag (the invisible top input swallows the lower thumb), so
 * we render our own thumbs and translate pointer/keyboard input into snapped
 * values. Each thumb is a `role="slider"` button so keyboard and screen-reader
 * users keep full control.
 */
function PriceRange({
    light,
    min,
    max,
    step,
    minValue,
    maxValue,
    onMinChange,
    onMaxChange,
}: {
    light: boolean;
    min: number;
    max: number;
    step: number;
    minValue: number;
    maxValue: number;
    onMinChange: (value: number) => void;
    onMaxChange: (value: number) => void;
}) {
    const trackRef = useRef<HTMLDivElement>(null);
    // Which handle is being dragged. A ref (not state) so the pointermove handler
    // always reads the current value synchronously — state would lag a render
    // behind the first move and drop the drag.
    const draggingRef = useRef<'min' | 'max' | null>(null);

    const pct = (value: number) => ((value - min) / (max - min)) * 100;

    const valueFromClientX = (clientX: number): number => {
        const track = trackRef.current;
        if (!track) {
            return min;
        }
        const rect = track.getBoundingClientRect();
        const ratio = rect.width > 0 ? (clientX - rect.left) / rect.width : 0;
        const raw = min + Math.min(1, Math.max(0, ratio)) * (max - min);
        const snapped = Math.round(raw / step) * step;
        return Math.min(max, Math.max(min, snapped));
    };

    const apply = (thumb: 'min' | 'max', value: number) => {
        if (thumb === 'min') {
            onMinChange(value);
        } else {
            onMaxChange(value);
        }
    };

    // Pointer interaction lives on the whole track: a press grabs the nearest
    // handle (so clicking the bar jumps it there, like a native slider) and the
    // track captures the pointer so the drag survives the cursor leaving the bar.
    const handlePointerDown = (event: PointerEvent<HTMLDivElement>) => {
        event.preventDefault();
        const value = valueFromClientX(event.clientX);
        const thumb: 'min' | 'max' = Math.abs(value - minValue) <= Math.abs(value - maxValue) ? 'min' : 'max';
        draggingRef.current = thumb;
        trackRef.current?.setPointerCapture(event.pointerId);
        apply(thumb, value);
    };

    const handlePointerMove = (event: PointerEvent<HTMLDivElement>) => {
        if (!draggingRef.current) {
            return;
        }
        apply(draggingRef.current, valueFromClientX(event.clientX));
    };

    const stopDragging = () => {
        draggingRef.current = null;
    };

    const handleKeyDown = (thumb: 'min' | 'max', value: number) => (event: KeyboardEvent<HTMLButtonElement>) => {
        let next = value;
        if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
            next = value - step;
        } else if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
            next = value + step;
        } else if (event.key === 'Home') {
            next = min;
        } else if (event.key === 'End') {
            next = max;
        } else {
            return;
        }
        event.preventDefault();
        apply(thumb, Math.min(max, Math.max(min, next)));
    };

    // Handles sit above the track but don't intercept the pointer themselves —
    // presses bubble to the track so it can grab whichever handle is nearest.
    const thumbClass = cn(
        'border-marine pointer-events-none absolute top-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 bg-white shadow outline-none',
        'focus-visible:ring-marine focus-visible:pointer-events-auto focus-visible:ring-2 focus-visible:ring-offset-2',
        light ? 'focus-visible:ring-offset-white' : 'focus-visible:ring-offset-transparent',
    );

    return (
        <div
            ref={trackRef}
            className="relative mt-3 h-6 cursor-pointer touch-none"
            onPointerDown={handlePointerDown}
            onPointerMove={handlePointerMove}
            onPointerUp={stopDragging}
            onPointerCancel={stopDragging}
        >
            <div className={cn('absolute top-1/2 h-1 w-full -translate-y-1/2 rounded-full', light ? 'bg-slate-200' : 'bg-white/20')} />
            <div
                className="bg-marine absolute top-1/2 h-1 -translate-y-1/2 rounded-full"
                style={{ left: `${pct(minValue)}%`, width: `${pct(maxValue) - pct(minValue)}%` }}
            />
            <button
                type="button"
                role="slider"
                aria-label="Minimum price"
                aria-valuemin={min}
                aria-valuemax={max}
                aria-valuenow={minValue}
                className={thumbClass}
                style={{ left: `${pct(minValue)}%` }}
                onKeyDown={handleKeyDown('min', minValue)}
            />
            <button
                type="button"
                role="slider"
                aria-label="Maximum price"
                aria-valuemin={min}
                aria-valuemax={max}
                aria-valuenow={maxValue}
                className={thumbClass}
                style={{ left: `${pct(maxValue)}%` }}
                onKeyDown={handleKeyDown('max', maxValue)}
            />
        </div>
    );
}
