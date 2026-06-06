import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface BaseProps {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
}

export function TextField({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    required,
    type = 'text',
    placeholder,
    prefix,
}: BaseProps & {
    value: string;
    onChange: (value: string) => void;
    type?: string;
    placeholder?: string;
    prefix?: string;
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>
                {label}
                {required && <span className="text-destructive"> *</span>}
            </Label>
            <div className={cn(prefix && 'flex items-stretch')}>
                {prefix && (
                    <span className="inline-flex items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                        {prefix}
                    </span>
                )}
                <Input
                    id={id}
                    type={type}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    className={cn(prefix && 'rounded-l-none')}
                />
            </div>
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export function TextareaField({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    rows = 3,
    placeholder,
    mono,
}: BaseProps & {
    value: string;
    onChange: (value: string) => void;
    rows?: number;
    placeholder?: string;
    mono?: boolean;
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Textarea
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                rows={rows}
                placeholder={placeholder}
                className={cn(mono && 'font-mono text-xs')}
            />
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export function SelectField({
    id,
    label,
    value,
    onChange,
    options,
    error,
    hint,
}: BaseProps & {
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <select
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export function CheckboxField({
    id,
    label,
    checked,
    onChange,
    hint,
}: {
    id: string;
    label: string;
    checked: boolean;
    onChange: (value: boolean) => void;
    hint?: string;
}) {
    return (
        <label htmlFor={id} className="flex cursor-pointer items-start gap-3">
            <Checkbox id={id} checked={checked} onCheckedChange={(value) => onChange(value === true)} className="mt-0.5" />
            <span className="grid gap-0.5">
                <span className="text-sm font-medium leading-none">{label}</span>
                {hint && <span className="text-xs text-muted-foreground">{hint}</span>}
            </span>
        </label>
    );
}

export function FieldGroup({ children, columns = 1 }: { children: ReactNode; columns?: 1 | 2 }) {
    return <div className={cn('grid gap-5', columns === 2 && 'sm:grid-cols-2')}>{children}</div>;
}
