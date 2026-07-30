import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { uploadImage } from '@/lib/upload';
import { cn } from '@/lib/utils';
import { ImageIcon, Trash2, Upload } from 'lucide-react';
import { useRef, useState, type ReactNode } from 'react';

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
                    <span className="border-input bg-muted text-muted-foreground inline-flex items-center rounded-l-md border border-r-0 px-3 text-sm">
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
            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
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
            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
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
                className="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
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
                <span className="text-sm leading-none font-medium">{label}</span>
                {hint && <span className="text-muted-foreground text-xs">{hint}</span>}
            </span>
        </label>
    );
}

/**
 * An image chooser for the social-sharing fields: upload a file from the
 * computer (stored on the site and pasted back in as a URL) or type an
 * existing image URL. The preview is what social networks show for the link.
 */
export function ImageField({
    id,
    label,
    value,
    onChange,
    error,
    hint,
    fallback,
}: BaseProps & {
    value: string;
    onChange: (value: string) => void;
    fallback?: string | null;
}) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);

    const preview = value || fallback || '';

    const upload = async (file: File | undefined) => {
        if (!file) {
            return;
        }

        setUploading(true);
        setUploadError(null);

        try {
            onChange(await uploadImage(file));
        } catch (exception) {
            setUploadError(exception instanceof Error ? exception.message : 'The image could not be uploaded.');
        } finally {
            setUploading(false);

            // Clear the input so re-picking the same file fires onChange again.
            if (fileInput.current) {
                fileInput.current.value = '';
            }
        }
    };

    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>

            <div className="flex items-start gap-3">
                <div className="border-input bg-muted text-muted-foreground flex aspect-[1.91/1] w-40 shrink-0 items-center justify-center overflow-hidden rounded-md border">
                    {preview ? <img src={preview} alt="" className="h-full w-full object-cover" /> : <ImageIcon className="h-5 w-5" />}
                </div>

                <div className="grid flex-1 gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <Button type="button" variant="outline" size="sm" disabled={uploading} onClick={() => fileInput.current?.click()}>
                            <Upload className="h-3.5 w-3.5" />
                            {uploading ? 'Uploading…' : value ? 'Replace image' : 'Upload image'}
                        </Button>
                        {value && (
                            <Button type="button" variant="ghost" size="sm" disabled={uploading} onClick={() => onChange('')}>
                                <Trash2 className="h-3.5 w-3.5" />
                                Remove
                            </Button>
                        )}
                    </div>

                    <input
                        ref={fileInput}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        className="hidden"
                        onChange={(e) => upload(e.target.files?.[0])}
                    />

                    <Input id={id} type="text" value={value} onChange={(e) => onChange(e.target.value)} placeholder="https://… or upload an image" />
                </div>
            </div>

            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={uploadError ?? error} />
        </div>
    );
}

export function FieldGroup({ children, columns = 1 }: { children: ReactNode; columns?: 1 | 2 }) {
    return <div className={cn('grid gap-5', columns === 2 && 'sm:grid-cols-2')}>{children}</div>;
}
