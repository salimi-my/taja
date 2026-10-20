import { Plus } from 'lucide-react';
import { useState } from 'react';
import { UserForm } from '@/components/users/user-form';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RoleOption } from '@/types';

type UserCreateProps = {
    roles: RoleOption[];
    /** Render an icon-only button, used in the mobile toolbar. */
    compact?: boolean;
};

export function UserCreate({ roles, compact = false }: UserCreateProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {compact ? (
                    <Button size="icon-sm" aria-label="Create user">
                        <Plus />
                    </Button>
                ) : (
                    <Button size="sm">
                        <Plus />
                        Create New
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create User</DialogTitle>
                    <DialogDescription>
                        Create a new user here and click create when you're
                        done.
                    </DialogDescription>
                </DialogHeader>
                <UserForm roles={roles} onSuccess={() => setOpen(false)} />
            </DialogContent>
        </Dialog>
    );
}
