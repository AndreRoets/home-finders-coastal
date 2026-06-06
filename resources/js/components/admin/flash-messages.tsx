import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';

export default function FlashMessages() {
    const { flash } = usePage<SharedData>().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className="space-y-2">
            {flash.success && (
                <div className="flex items-center gap-2 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    <CheckCircle2 className="h-4 w-4 shrink-0" />
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <XCircle className="h-4 w-4 shrink-0" />
                    {flash.error}
                </div>
            )}
        </div>
    );
}
