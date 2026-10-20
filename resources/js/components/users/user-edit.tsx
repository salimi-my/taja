import { Edit } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { UserForm } from '@/components/users/user-form';
import type { ManagedUser, RoleOption } from '@/types';

type UserEditProps = {
    user: ManagedUser;
    roles: RoleOption[];
};

export function UserEdit({ user, roles }: UserEditProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" className="w-full justify-start px-2">
                    <Edit className="mr-2 size-4" aria-hidden="true" />
                    Edit
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit User</DialogTitle>
                    <DialogDescription>
                        Make changes to user here. Click save when you're done.
                    </DialogDescription>
                </DialogHeader>
                <UserForm
                    user={user}
                    roles={roles}
                    onSuccess={() => setOpen(false)}
                />
            </DialogContent>
        </Dialog>
    );
}
