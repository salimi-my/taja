import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';

type TableExportLinkProps = {
    /** The URL of the CSV export, built with the Wayfinder export route. */
    href: string;
    /** The accessible name of the button, e.g. `Export writers as CSV`. */
    label: string;
    /** Render an icon-only button, used in the mobile toolbar. */
    compact?: boolean;
};

/**
 * The "Export CSV" button of a datatable. It downloads every row matching the
 * current filters.
 *
 * The export is a plain link, not an Inertia visit, because it downloads a file.
 */
export function TableExportLink({
    href,
    label,
    compact = false,
}: TableExportLinkProps) {
    if (compact) {
        return (
            <Button variant="outline" size="icon-sm" aria-label={label} asChild>
                <a href={href} download>
                    <Download />
                </a>
            </Button>
        );
    }

    return (
        <Button variant="outline" size="sm" asChild>
            <a href={href} download>
                <Download />
                Export CSV
            </a>
        </Button>
    );
}
