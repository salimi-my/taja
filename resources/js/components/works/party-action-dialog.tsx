import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { TextAreaField } from '@/components/form-fields';
import type { Errors } from '@/components/form-fields';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { FieldError } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';

type PartyActionDialogProps = {
    title: string;
    description: string;
    /** The button that opens the dialog. */
    trigger: ReactNode;
    /** The Wayfinder form definition of the request the dialog sends. */
    form: Pick<ComponentProps<typeof Form>, 'action' | 'method'>;
    submitLabel: string;
    processingLabel: string;
    destructive?: boolean;
    /** Lays out the fields in two columns on wide screens. */
    children: (errors: Errors) => ReactNode;
};

/**
 * The shell shared by the add, change, correct and remove party dialogs: a
 * dialog around an Inertia form with a remarks field, the errors that belong
 * to the whole row (`party`, `shares`) and the submit button. It closes when
 * the request succeeds.
 */
export function PartyActionDialog({
    title,
    description,
    trigger,
    form,
    submitLabel,
    processingLabel,
    destructive = false,
    children,
}: PartyActionDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <Form
                    {...form}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    disableWhileProcessing
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-3 sm:grid-cols-2 [&>[data-slot=field]]:gap-2">
                                {children(errors)}
                                <TextAreaField
                                    name="remarks"
                                    label="Remarks"
                                    errors={errors}
                                />
                            </div>
                            <FieldError>{errors.party}</FieldError>
                            <FieldError>{errors.shares}</FieldError>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && (
                                        <Spinner className="size-4!" />
                                    )}
                                    {processing ? processingLabel : submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
