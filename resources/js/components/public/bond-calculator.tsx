import { cn } from '@/lib/utils';
import { useState } from 'react';

/**
 * The South African prime lending rate, used as the interest-rate default and
 * in the "Current prime" helper text. Kept in one place so it's trivial to
 * update when the Reserve Bank changes the repo rate.
 */
export const PRIME_RATE = 11.75;

const TERM_OPTIONS = [10, 15, 20, 25, 30] as const;

/**
 * Parse a loosely-typed loan amount into a number of Rand. Tolerates a leading
 * "R", whitespace, comma thousands separators and a shorthand millions suffix
 * ("2.5m" → 2 500 000). Falls back to 0 for anything non-numeric.
 */
function parseAmount(val: string): number {
    const cleaned = val.replace(/[Rr\s,]/g, '');
    const millions = cleaned.match(/^([\d.]+)[mM]$/);

    if (millions) {
        return parseFloat(millions[1]) * 1_000_000;
    }

    const parsed = parseFloat(cleaned);

    return Number.isNaN(parsed) ? 0 : parsed;
}

/**
 * Standard amortisation: the level monthly repayment that pays off `principal`
 * over `years` at `annualRatePercent`. Returns 0 for any non-positive input so
 * the UI never shows NaN/∞.
 */
export function calcMonthlyRepayment(principal: number, annualRatePercent: number, years: number): number {
    if (principal <= 0 || annualRatePercent <= 0 || years <= 0) {
        return 0;
    }

    const r = annualRatePercent / 100 / 12;
    const n = years * 12;
    const factor = Math.pow(1 + r, n);

    return (principal * (r * factor)) / (factor - 1);
}

/** Format a value as South African Rand with thousands separators and 2 decimals. */
function fmt(val: number | null | undefined): string {
    if (val == null) {
        return '0';
    }

    return Number(val).toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const rand = (val: number): string => `R ${fmt(val)}`;

interface BondResults {
    monthly: number;
    totalRepaid: number;
    totalInterest: number;
    comparisons: { rate: number; monthly: number }[];
}

interface BondCalculatorProps {
    /** Pre-fills the loan amount — e.g. a property's listed price ("R 1,500,000"). */
    initialAmount?: string;
    /** Tighten spacing/typography for the property-sidebar placement. */
    compact?: boolean;
    className?: string;
}

/**
 * Client-side bond repayment calculator — replicates the CoreX calculator.
 * All maths runs in the browser; there is no API call.
 */
export default function BondCalculator({ initialAmount, compact = false, className }: BondCalculatorProps) {
    const initialParsed = initialAmount ? parseAmount(initialAmount) : 0;

    const [amount, setAmount] = useState(initialParsed > 0 ? initialParsed.toLocaleString('en-ZA') : '');
    const [rate, setRate] = useState(String(PRIME_RATE));
    const [years, setYears] = useState(20);
    const [results, setResults] = useState<BondResults | null>(null);

    const handleAmountChange = (val: string) => {
        // Preserve shorthand ("2.5m") and decimals as typed; otherwise reformat
        // plain digits live so the field shows thousands separators.
        if (/[mM]/.test(val) || val.includes('.')) {
            setAmount(val);
            return;
        }

        const rawDigits = val.replace(/\D/g, '');
        setAmount(rawDigits ? Number(rawDigits).toLocaleString('en-ZA') : '');
    };

    const calculate = () => {
        const principal = parseAmount(amount);
        const annualRate = parseFloat(rate) || 0;
        const monthly = calcMonthlyRepayment(principal, annualRate, years);
        const totalRepaid = monthly * years * 12;

        setResults({
            monthly,
            totalRepaid,
            totalInterest: totalRepaid - principal,
            comparisons: [1, 2].map((bump) => ({
                rate: annualRate + bump,
                monthly: calcMonthlyRepayment(principal, annualRate + bump, years),
            })),
        });
    };

    const labelClass = 'mb-1.5 block text-xs tracking-[0.15em] text-neutral-600 uppercase';
    const fieldClass =
        'focus:border-marine w-full rounded-sm border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-neutral-800 transition-colors outline-none placeholder:text-neutral-400';

    return (
        <div className={cn('rounded-sm border border-slate-200 bg-slate-50', compact ? 'p-5' : 'p-6 sm:p-8', className)}>
            <h2 className={cn('text-navy font-light', compact ? 'text-xl' : 'text-2xl')}>Bond Repayment</h2>

            <div className="mt-5 space-y-4">
                <div>
                    <label htmlFor="bond-amount" className={labelClass}>
                        Loan Amount (Rand)
                    </label>
                    <div className="relative">
                        <span className="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-sm text-neutral-500">R</span>
                        <input
                            id="bond-amount"
                            type="text"
                            inputMode="numeric"
                            value={amount}
                            onChange={(event) => handleAmountChange(event.target.value)}
                            placeholder="1,500,000"
                            className={cn(fieldClass, 'pl-7')}
                        />
                    </div>
                </div>

                <div>
                    <label htmlFor="bond-rate" className={labelClass}>
                        Interest Rate (%)
                    </label>
                    <input
                        id="bond-rate"
                        type="text"
                        inputMode="decimal"
                        value={rate}
                        onChange={(event) => setRate(event.target.value)}
                        className={fieldClass}
                    />
                    <p className="mt-1.5 text-xs text-neutral-500">Current prime: {PRIME_RATE}%</p>
                </div>

                <div>
                    <label htmlFor="bond-term" className={labelClass}>
                        Loan Term
                    </label>
                    <select id="bond-term" value={years} onChange={(event) => setYears(Number(event.target.value))} className={fieldClass}>
                        {TERM_OPTIONS.map((option) => (
                            <option key={option} value={option}>
                                {option} years
                            </option>
                        ))}
                    </select>
                </div>

                <button
                    type="button"
                    onClick={calculate}
                    className="bg-navy hover:bg-navy/90 mt-1 flex w-full items-center justify-center rounded-full px-6 py-3 text-sm font-semibold tracking-wide text-white transition-colors"
                >
                    Calculate
                </button>
            </div>

            {results && (
                <div className="mt-6 border-t border-slate-200 pt-6">
                    <p className="text-xs tracking-[0.2em] text-neutral-500 uppercase">Monthly repayment</p>
                    <p className={cn('text-brand-red font-semibold', compact ? 'text-3xl' : 'text-4xl')}>{rand(results.monthly)}</p>

                    <dl className="mt-5 divide-y divide-slate-200 rounded-sm border border-slate-200 bg-white">
                        <div className="flex justify-between px-4 py-3 text-sm">
                            <dt className="text-neutral-600">Total repaid over term</dt>
                            <dd className="text-navy font-medium">{rand(results.totalRepaid)}</dd>
                        </div>
                        <div className="flex justify-between px-4 py-3 text-sm">
                            <dt className="text-neutral-600">Total interest</dt>
                            <dd className="text-navy font-medium">{rand(results.totalInterest)}</dd>
                        </div>
                    </dl>

                    <div className="mt-5">
                        <p className="text-marine text-xs font-semibold tracking-[0.2em] uppercase">Comparison at higher rates</p>
                        <dl className="mt-3 divide-y divide-slate-200 rounded-sm border border-slate-200 bg-white">
                            {results.comparisons.map((comparison) => (
                                <div key={comparison.rate} className="flex justify-between px-4 py-3 text-sm">
                                    <dt className="text-neutral-600">At {comparison.rate.toFixed(2)}%</dt>
                                    <dd className="text-navy font-medium">{rand(comparison.monthly)}/mo</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    <p className="mt-4 text-xs leading-relaxed text-neutral-400">
                        Estimate only. Excludes deposit, fees and insurance — confirm with your bank or bond originator.
                    </p>
                </div>
            )}
        </div>
    );
}
