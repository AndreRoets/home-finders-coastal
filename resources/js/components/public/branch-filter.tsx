import { type BranchOption } from '@/components/public/branches';
import { cn } from '@/lib/utils';
import { router, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';

/**
 * "All + branches" filter for the listing and agent index pages. Selecting a
 * branch re-scopes the page to it via a `?branch_id={id}` query parameter
 * (shareable, back-button friendly); "All" clears it. Other query params on the
 * page (search filters, etc.) are preserved.
 *
 * Renders nothing when there are no branches (feature off / none configured),
 * so it's safe to drop onto any page. Degrades from chips to a dropdown once
 * there are more than five branches; chips wrap on small screens below that.
 */
export default function BranchFilter({
    branches,
    active,
    className,
}: {
    branches: BranchOption[];
    active: number | string | null;
    className?: string;
}) {
    const { url } = usePage();

    if (!branches || branches.length === 0) {
        return null;
    }

    const [path, query = ''] = url.split('?');

    const go = (id: number | string | null) => {
        const params = new URLSearchParams(query);
        if (id !== null) {
            params.set('branch_id', String(id));
        } else {
            params.delete('branch_id');
        }
        const qs = params.toString();
        router.get(qs ? `${path}?${qs}` : path, {}, { preserveScroll: true, preserveState: false });
    };

    const isActive = (id: number | string | null): boolean =>
        id === null ? active === null || active === undefined : String(active) === String(id);

    const asDropdown = branches.length > 5;

    return (
        <div className={className}>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <span className="text-xs font-medium tracking-[0.2em] text-neutral-500 uppercase">Branch</span>

                {asDropdown ? (
                    <div className="relative inline-block">
                        <select
                            value={active === null || active === undefined ? '' : String(active)}
                            onChange={(event) => go(event.target.value || null)}
                            aria-label="Filter by branch"
                            className="w-full appearance-none rounded-full border border-slate-300 bg-white py-2 pr-9 pl-4 text-sm font-medium text-navy outline-none transition-colors focus:border-marine sm:w-64"
                        >
                            <option value="">All branches</option>
                            {branches.map((branch) => (
                                <option key={branch.id} value={String(branch.id)}>
                                    {branch.name}
                                </option>
                            ))}
                        </select>
                        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                    </div>
                ) : (
                    <div className="inline-flex flex-wrap gap-1.5 rounded-full border border-slate-200 bg-slate-100 p-1">
                        <Chip label="All" isActive={isActive(null)} onClick={() => go(null)} />
                        {branches.map((branch) => (
                            <Chip key={branch.id} label={branch.name} isActive={isActive(branch.id)} onClick={() => go(branch.id)} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

function Chip({ label, isActive, onClick }: { label: string; isActive: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={isActive}
            className={cn(
                'rounded-full px-4 py-1.5 text-sm font-medium tracking-wide transition-colors',
                isActive ? 'bg-navy text-white' : 'text-neutral-600 hover:text-navy',
            )}
        >
            {label}
        </button>
    );
}
