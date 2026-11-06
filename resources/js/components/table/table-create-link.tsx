import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';

type TableCreateLinkProps = {
    /** The accessible name of the button, e.g. `Create writer`. */
    label: string;
    href: InertiaLinkProps['href'];
    /** Render an icon-only button, used in the mobile toolbar. */
    compact?: boolean;
};

/**
 * The "Create New" button of a datatable. It opens the create page of the
 * resource instead of a dialog.
 */
export function TableCreateLink({
    label,
    href,
    compact = false,
}: TableCreateLinkProps) {
    if (compact) {
        return (
            <Button size="icon-sm" aria-label={label} asChild>
                <Link href={href}>
                    <Plus />
                </Link>
            </Button>
        );
    }

    return (
        <Button size="sm" asChild>
            <Link href={href}>
                <Plus />
                Create New
            </Link>
        </Button>
    );
}
