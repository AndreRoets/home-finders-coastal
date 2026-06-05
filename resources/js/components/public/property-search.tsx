import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { ChevronDown, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';

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
}

const EMPTY_FACETS: SearchFacets = {
    suburbs: [],
    propertyTypes: [],
    maxBeds: 0,
    maxBaths: 0,
    price: { sale: { min: 0, max: 0 }, rent: { min: 0, max: 0 } },
};

export default function PropertySearch({ filters = EMPTY_FACETS, mode: initialMode = 'sale', values, variant = 'hero' }: PropertySearchProps) {
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

        router.get(route(mode === 'sale' ? 'for-sale' : 'to-rent'), params, { preserveScroll: false });
    };

    const pct = (value: number) => (hasPrice ? ((value - range.min) / (range.max - range.min)) * 100 : 0);

    const container =
        variant === 'hero'
            ? 'border-white/15 bg-white/10 backdrop-blur'
            : 'border-white/10 bg-ink-soft/60';

    return (
        <form onSubmit={handleSubmit} className={cn('w-full rounded-2xl border p-4 text-left sm:p-6', container)}>
            {/* Buy / Rent */}
            <div className="inline-flex rounded-full border border-white/20 bg-white/15 p-1">
                {(['sale', 'rent'] as const).map((m) => (
                    <button
                        key={m}
                        type="button"
                        onClick={() => switchMode(m)}
                        className={cn(
                            'rounded-full px-5 py-1.5 text-sm font-medium tracking-wide transition-colors',
                            mode === m ? 'bg-navy text-white' : 'text-neutral-200 hover:text-white',
                        )}
                    >
                        {m === 'sale' ? 'Buy' : 'Rent'}
                    </button>
                ))}
            </div>

            {/* Selects */}
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Field label="Location">
                    <SelectControl value={suburb} onChange={setSuburb}>
                        <option value="">Any area</option>
                        {filters.suburbs.map((s) => (
                            <option key={s} value={s}>
                                {s}
                            </option>
                        ))}
                    </SelectControl>
                </Field>

                <Field label="Property type">
                    <SelectControl value={type} onChange={setType}>
                        <option value="">Any type</option>
                        {filters.propertyTypes.map((t) => (
                            <option key={t} value={t}>
                                {t}
                            </option>
                        ))}
                    </SelectControl>
                </Field>

                <Field label="Bedrooms">
                    <SelectControl value={String(beds)} onChange={(v) => setBeds(Number(v))}>
                        <option value="0">Any</option>
                        {Array.from({ length: filters.maxBeds }, (_, i) => i + 1).map((n) => (
                            <option key={n} value={n}>
                                {n}+
                            </option>
                        ))}
                    </SelectControl>
                </Field>

                <Field label="Bathrooms">
                    <SelectControl value={String(baths)} onChange={(v) => setBaths(Number(v))}>
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
                    <div className="flex items-center justify-between text-xs tracking-[0.15em] text-white uppercase">
                        <span>Price range</span>
                        <span className="text-neutral-200 normal-case tracking-normal">
                            {formatPrice(minPrice)} — {formatPrice(maxPrice)}
                        </span>
                    </div>
                    <div className="relative mt-3 h-5">
                        <div className="absolute top-1/2 h-1 w-full -translate-y-1/2 rounded-full bg-white/20" />
                        <div
                            className="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-marine"
                            style={{ left: `${pct(minPrice)}%`, width: `${pct(maxPrice) - pct(minPrice)}%` }}
                        />
                        <RangeThumb
                            min={range.min}
                            max={range.max}
                            step={step}
                            value={minPrice}
                            onChange={(v) => setMinPrice(Math.min(v, maxPrice - step))}
                        />
                        <RangeThumb
                            min={range.min}
                            max={range.max}
                            step={step}
                            value={maxPrice}
                            onChange={(v) => setMaxPrice(Math.max(v, minPrice + step))}
                        />
                    </div>
                </div>
            )}

            {/* Keyword + submit */}
            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div className="flex flex-1 items-center gap-3 rounded-full border border-white/15 bg-ink/30 px-4">
                    <Search className="h-5 w-5 shrink-0 text-white" />
                    <input
                        type="text"
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        placeholder="Keyword — suburb, area or feature…"
                        aria-label="Search keyword"
                        className="w-full bg-transparent py-2.5 text-sm text-white outline-none placeholder:text-neutral-400"
                    />
                </div>
                <button
                    type="submit"
                    className="inline-flex items-center justify-center gap-2 rounded-full bg-navy px-8 py-3 text-sm font-semibold tracking-wide text-white transition-colors hover:bg-navy/90"
                >
                    <Search className="h-4 w-4" />
                    Search
                </button>
            </div>
        </form>
    );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-xs tracking-[0.15em] text-white uppercase">{label}</span>
            {children}
        </label>
    );
}

function SelectControl({ value, onChange, children }: { value: string; onChange: (value: string) => void; children: React.ReactNode }) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="w-full appearance-none rounded-lg border border-white/15 bg-ink/40 px-3.5 py-2.5 text-sm text-white outline-none transition-colors focus:border-marine/60"
            >
                {children}
            </select>
            <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
        </div>
    );
}

function RangeThumb({ min, max, step, value, onChange }: { min: number; max: number; step: number; value: number; onChange: (value: number) => void }) {
    return (
        <input
            type="range"
            min={min}
            max={max}
            step={step}
            value={value}
            onChange={(event) => onChange(Number(event.target.value))}
            className={cn(
                'pointer-events-none absolute top-1/2 h-1 w-full -translate-y-1/2 appearance-none bg-transparent',
                '[&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-marine [&::-webkit-slider-thumb]:bg-white',
                '[&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-marine [&::-moz-range-thumb]:bg-white',
            )}
        />
    );
}
