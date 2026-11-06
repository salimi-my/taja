import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { availableImports, importOrder } from '@/lib/imports';
import { create } from '@/routes/imports';
import type { ImportType } from '@/types';

const labels: Record<ImportType, string> = {
    publishers: 'Publishers',
    writers: 'Writers',
    works: 'Works',
};

type ImportOrderNoteProps = {
    current: ImportType;
};

/** Tells the user which file to import first, with links between the import pages. */
export function ImportOrderNote({ current }: ImportOrderNoteProps) {
    return (
        <p className="text-sm text-muted-foreground">
            Import the files in this order, because later files refer to earlier
            records:{' '}
            {importOrder.map((type, index) => (
                <Fragment key={type}>
                    {index > 0 && ' → '}
                    {type === current ? (
                        <strong className="text-foreground">
                            {labels[type]}
                        </strong>
                    ) : availableImports.includes(type) ? (
                        <Link
                            href={create(type)}
                            className="text-foreground underline underline-offset-4"
                        >
                            {labels[type]}
                        </Link>
                    ) : (
                        <span>{labels[type]} (coming soon)</span>
                    )}
                </Fragment>
            ))}
            .
        </p>
    );
}
