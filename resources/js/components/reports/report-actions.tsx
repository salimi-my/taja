import { Download } from 'lucide-react';
import { Button } from '@/components/ui/button';

type ReportActionsProps = {
    /** The URL of the CSV export, built with the Wayfinder export route. */
    exportUrl: string;
    /** Disables the button, e.g. while no writer is chosen. */
    disabled?: boolean;
};

/**
 * The Export CSV button of a report or profile.
 *
 * The export is a plain link, not an Inertia visit, because it downloads a file.
 */
export function ReportActions({ exportUrl, disabled }: ReportActionsProps) {
    return (
        <div className="flex items-center gap-2">
            {disabled ? (
                <Button variant="outline" size="sm" disabled>
                    <Download />
                    Export CSV
                </Button>
            ) : (
                <Button variant="outline" size="sm" asChild>
                    <a href={exportUrl} download>
                        <Download />
                        Export CSV
                    </a>
                </Button>
            )}
        </div>
    );
}
