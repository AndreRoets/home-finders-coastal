import FlashMessages from '@/components/admin/flash-messages';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

interface UserRow {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

export default function UsersIndex({ users }: { users: UserRow[] }) {
    const currentUserId = usePage<SharedData>().props.auth.user.id;

    const deleteUser = (id: number) => {
        router.delete(`/admin/users/${id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-4">
                <FlashMessages />

                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Users</h1>
                        <p className="text-sm text-muted-foreground">Accounts that can sign in to this panel.</p>
                    </div>
                    <Link href="/admin/users/create">
                        <Button>
                            <Plus className="h-4 w-4" />
                            Add user
                        </Button>
                    </Link>
                </div>

                <Card className="divide-y p-0">
                    {users.map((user) => (
                        <div key={user.id} className="flex items-center justify-between gap-4 p-4">
                            <div className="min-w-0">
                                <div className="flex items-center gap-2 font-medium">
                                    {user.name}
                                    {user.id === currentUserId && <span className="text-xs text-muted-foreground">(you)</span>}
                                </div>
                                <div className="truncate text-sm text-muted-foreground">{user.email}</div>
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <Link href={`/admin/users/${user.id}/edit`}>
                                    <Button variant="ghost" size="sm">
                                        <Pencil className="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                {user.id !== currentUserId && (
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button variant="ghost" size="sm" className="text-destructive hover:text-destructive">
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Delete user</DialogTitle>
                                                <DialogDescription>
                                                    Remove {user.name} ({user.email})? They will no longer be able to log in. This cannot be undone.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter className="gap-2">
                                                <DialogClose asChild>
                                                    <Button variant="secondary">Cancel</Button>
                                                </DialogClose>
                                                <Button variant="destructive" onClick={() => deleteUser(user.id)}>
                                                    Delete user
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                )}
                            </div>
                        </div>
                    ))}
                </Card>
            </div>
        </AppLayout>
    );
}
