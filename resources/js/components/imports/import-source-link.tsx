import { Link } from '@inertiajs/react';
import { FileUp } from 'lucide-react';
import { useIsAdmin } from '@/hooks/use-is-admin';
import { cn } from '@/lib/utils';
import { show } from '@/routes/imports';

type ImportSourceLinkProps = {
    csvImportId: number | null;
    className?: string;
};

/**
 * A link to the CSV import that wrote a history entry. Import pages are for
 * admins only, so nothing is shown to other users, and nothing is shown once
 * the import was deleted from the import history.
 */
export function ImportSourceLink({
    csvImportId,
    className,
}: ImportSourceLinkProps) {
    const isAdmin = useIsAdmin();

    if (!isAdmin || csvImportId === null) {
        return null;
    }

    return (
        <Link
            href={show(csvImportId)}
            className={cn(
                'inline-flex items-center gap-1 font-medium text-primary',
                className,
            )}
        >
            <FileUp aria-hidden="true" className="size-3.5" />
            <span className="link-hover after:h-[2px]">View import</span>
        </Link>
    );
}
