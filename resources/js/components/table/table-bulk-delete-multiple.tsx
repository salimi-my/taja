import { Trash2 } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState, type Dispatch, type SetStateAction } from 'react';
import { createPortal } from 'react-dom';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { getSelectAllCheckedState } from '@/hooks/use-table-selection';

const SAFE_AREA_SPACER_STYLE = {
    height: 'max(env(safe-area-inset-bottom), 0px)',
    paddingBottom: 'env(safe-area-inset-bottom)',
} as const;

interface TableBulkDeleteBaseProps<TId extends string | number> {
    selectedIds: Set<TId>;
    setSelectedIds: Dispatch<SetStateAction<Set<TId>>>;
    /** URL of the bulk delete endpoint (a Wayfinder `.url()` string). */
    endpoint: string;
    /** Singular entity label used in the confirmation dialog (e.g. `"user"`). */
    entityName: string;
    serializeIds?: (ids: TId[]) => (string | number)[];
    onSuccess?: () => void;
}

function useTableBulkDelete<TId extends string | number>({
    selectedIds,
    setSelectedIds,
    endpoint,
    serializeIds = (ids) => [...ids],
    onSuccess,
}: TableBulkDeleteBaseProps<TId>) {
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const count = selectedIds.size;

    const handleDelete = () => {
        router.delete(endpoint, {
            data: { ids: serializeIds(Array.from(selectedIds)) },
            preserveScroll: true,
            onStart: () => setLoading(true),
            onSuccess: () => {
                setSelectedIds(new Set());
                setOpen(false);
                onSuccess?.();
            },
            onFinish: () => setLoading(false),
        });
    };

    return { loading, open, setOpen, handleDelete, count };
}

function TableBulkDeleteDialogContent({
    entityName,
    count,
    loading,
    onConfirm,
}: {
    entityName: string;
    count: number;
    loading: boolean;
    onConfirm: () => void;
}) {
    return (
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                <AlertDialogDescription>
                    This action cannot be undone. This will permanently delete{' '}
                    {count} {entityName}
                    {count > 1 && 's'} and remove the data from our servers.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction
                    variant="destructive"
                    disabled={loading}
                    onClick={(e) => {
                        e.preventDefault();
                        onConfirm();
                    }}
                >
                    {loading ? (
                        <>
                            <Spinner className="size-4!" /> Deleting...
                        </>
                    ) : (
                        <>Continue</>
                    )}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    );
}

export function TableBulkDeleteMultiple<TId extends string | number>(
    props: TableBulkDeleteBaseProps<TId>,
) {
    const { entityName } = props;
    const { loading, open, setOpen, handleDelete, count } =
        useTableBulkDelete(props);

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>
                <Button variant="outline" size="sm" className="group">
                    <Trash2
                        className="size-4 group-hover:text-destructive"
                        aria-hidden="true"
                    />
                    <span className="group-hover:text-destructive">
                        Delete ({count})
                    </span>
                </Button>
            </AlertDialogTrigger>
            <TableBulkDeleteDialogContent
                entityName={entityName}
                count={count}
                loading={loading}
                onConfirm={handleDelete}
            />
        </AlertDialog>
    );
}

interface TableBulkDeleteMultipleMobileProps<
    TId extends string | number,
> extends TableBulkDeleteBaseProps<TId> {
    selectableCount: number;
    selectAllAriaLabel: string;
    onToggleSelectAll: () => void;
}

export function TableBulkDeleteMultipleMobile<TId extends string | number>({
    selectableCount,
    selectAllAriaLabel,
    onToggleSelectAll,
    entityName,
    selectedIds,
    ...bulkDeleteProps
}: TableBulkDeleteMultipleMobileProps<TId>) {
    const { loading, open, setOpen, handleDelete, count } = useTableBulkDelete({
        selectedIds,
        entityName,
        ...bulkDeleteProps,
    });

    const selectAllChecked = getSelectAllCheckedState(count, selectableCount);
    const isVisible = count > 0;

    const floatingBar = (
        <div
            data-visible={isVisible}
            className="fixed right-0 bottom-0 left-0 z-50 m-0 translate-y-full transition-transform duration-300 ease-in-out data-[visible=true]:translate-y-0"
        >
            <div className="border-t bg-background">
                <div className="mx-auto flex max-w-screen-xl items-center justify-between gap-3 px-4 py-7">
                    <div className="flex items-center gap-2">
                        <Checkbox
                            checked={selectAllChecked}
                            onCheckedChange={onToggleSelectAll}
                            aria-label={selectAllAriaLabel}
                        />
                        <span className="text-sm">Select all</span>
                    </div>
                    <Button
                        variant="destructive"
                        size="sm"
                        onClick={() => setOpen(true)}
                        className="h-7 rounded-sm px-2!"
                    >
                        <Trash2 className="size-4" />
                        Delete ({count})
                    </Button>
                </div>
                <div style={SAFE_AREA_SPACER_STYLE} />
            </div>
        </div>
    );

    return (
        <>
            {createPortal(floatingBar, document.body)}
            <AlertDialog open={open} onOpenChange={setOpen}>
                <TableBulkDeleteDialogContent
                    entityName={entityName}
                    count={count}
                    loading={loading}
                    onConfirm={handleDelete}
                />
            </AlertDialog>
        </>
    );
}
