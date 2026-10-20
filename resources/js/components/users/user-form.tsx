import { Form } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { DialogClose } from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { ManagedUser, RoleOption } from '@/types';

type UserFormProps = {
    /** The user being edited. Leave empty to create a new user. */
    user?: ManagedUser;
    roles: RoleOption[];
    onSuccess: () => void;
};

export function UserForm({ user, roles, onSuccess }: UserFormProps) {
    const [role, setRole] = useState<string>(
        user?.role ?? roles[0]?.value ?? '',
    );
    const isEditing = user !== undefined;

    return (
        <Form
            {...(isEditing
                ? UserController.update.form(user.id)
                : UserController.store.form())}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            disableWhileProcessing
            className="flex flex-col gap-3"
        >
            {({ processing, errors }) => (
                <>
                    <FieldGroup className="gap-4 [&>[data-slot=field]]:gap-2">
                        <Field data-invalid={!!errors.name}>
                            <FieldLabel htmlFor="user-name">Name</FieldLabel>
                            <Input
                                id="user-name"
                                name="name"
                                defaultValue={user?.name}
                                placeholder="Enter name"
                                autoComplete="off"
                                aria-invalid={!!errors.name}
                            />
                            <FieldError>{errors.name}</FieldError>
                        </Field>

                        <Field data-invalid={!!errors.email}>
                            <FieldLabel htmlFor="user-email">Email</FieldLabel>
                            <Input
                                id="user-email"
                                name="email"
                                type="email"
                                defaultValue={user?.email}
                                placeholder="Enter email"
                                autoComplete="off"
                                aria-invalid={!!errors.email}
                            />
                            <FieldError>{errors.email}</FieldError>
                        </Field>

                        <Field data-invalid={!!errors.role}>
                            <FieldLabel>Role</FieldLabel>
                            <input type="hidden" name="role" value={role} />
                            <Combobox
                                options={roles.map((option) => ({
                                    value: option.value,
                                    label: option.label,
                                }))}
                                value={role}
                                onValueChange={(value) =>
                                    setRole(String(value))
                                }
                                aria-invalid={!!errors.role}
                                placeholder="Select role"
                                searchPlaceholder="Search role..."
                                emptyText="No role found."
                                disabled={user?.is_current_user}
                            />
                            <FieldError>{errors.role}</FieldError>
                        </Field>

                        <Field data-invalid={!!errors.password}>
                            <FieldLabel htmlFor="user-password">
                                Password
                            </FieldLabel>
                            <Input
                                id="user-password"
                                name="password"
                                type="password"
                                placeholder={
                                    isEditing
                                        ? 'Leave blank to keep current'
                                        : 'Enter password'
                                }
                                autoComplete="new-password"
                                aria-invalid={!!errors.password}
                            />
                            {isEditing && (
                                <FieldDescription>
                                    Only fill this in to change the password.
                                </FieldDescription>
                            )}
                            <FieldError>{errors.password}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="user-password-confirmation">
                                Confirm password
                            </FieldLabel>
                            <Input
                                id="user-password-confirmation"
                                name="password_confirmation"
                                type="password"
                                placeholder="Confirm password"
                                autoComplete="new-password"
                            />
                        </Field>
                    </FieldGroup>

                    <div className="mt-4 flex justify-end gap-2">
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button disabled={processing} type="submit">
                            {processing && <Spinner className="size-4!" />}
                            {isEditing
                                ? processing
                                    ? 'Saving...'
                                    : 'Save Changes'
                                : processing
                                  ? 'Creating...'
                                  : 'Create'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
