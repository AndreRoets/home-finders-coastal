import FlashMessages from '@/components/admin/flash-messages';
import { TextField } from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FormEventHandler } from 'react';

interface EditUser {
    id: number;
    name: string;
    email: string;
}

export default function UserEdit({ user }: { user: EditUser }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
        { title: user.name, href: `/admin/users/${user.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit · ${user.name}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-xl flex-col gap-6 p-4">
                <div>
                    <Link href="/admin/users" className="mb-1 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-3.5 w-3.5" /> Back to users
                    </Link>
                    <h1 className="text-2xl font-semibold tracking-tight">{user.name}</h1>
                </div>

                <FlashMessages />

                <Card>
                    <CardHeader>
                        <CardTitle>Account details</CardTitle>
                        <CardDescription>Leave the password fields blank to keep the current password.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextField id="name" label="Name" value={data.name} onChange={(v) => setData('name', v)} error={errors.name} required />
                        <TextField id="email" label="Email" type="email" value={data.email} onChange={(v) => setData('email', v)} error={errors.email} required />
                        <TextField id="password" label="New password" type="password" value={data.password} onChange={(v) => setData('password', v)} error={errors.password} />
                        <TextField
                            id="password_confirmation"
                            label="Confirm new password"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(v) => setData('password_confirmation', v)}
                            error={errors.password_confirmation}
                        />
                    </CardContent>
                </Card>

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                    <Link href="/admin/users">
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
