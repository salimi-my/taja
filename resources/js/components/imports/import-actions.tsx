import { Form, Link } from '@inertiajs/react';
import { CheckCheck, Download, X } from 'lucide-react';
import CsvImportController from '@/actions/App/Http/Controllers/CsvImportController';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { importListRoutes } from '@/lib/imports';
import { create, errors as errorsFile } from '@/routes/imports';
import type { CsvImport } from '@/types';

type ImportActionsProps = {
    csvImport: CsvImport;
};

export function ImportActions({ csvImport }: ImportActionsProps) {
    const list = importListRoutes[csvImport.type];
    const canCancel =
        csvImport.status === 'previewed' || csvImport.status === 'validating';
    const isFinished = ['imported', 'cancelled', 'failed'].includes(
        csvImport.status,
    );

    return (
        <div className="flex flex-wrap items-center gap-2">
            {csvImport.status === 'previewed' && (
                <>
                    <Form
                        {...CsvImportController.confirm.form(csvImport.id)}
                        disableWhileProcessing
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    csvImport.valid_rows === 0 ||
                                    csvImport.layout_error !== null
                                }
                            >
                                {processing ? (
                                    <Spinner className="size-4" />
                                ) : (
                                    <CheckCheck />
                                )}
                                Import {csvImport.valid_rows.toLocaleString()}{' '}
                                valid{' '}
                                {csvImport.valid_rows === 1 ? 'row' : 'rows'}
                            </Button>
                        )}
                    </Form>
                </>
            )}

            {canCancel && (
                <Form
                    {...CsvImportController.cancel.form(csvImport.id)}
                    disableWhileProcessing
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="outline"
                            disabled={processing}
                        >
                            <X />
                            Cancel
                        </Button>
                    )}
                </Form>
            )}

            {isFinished && (
                <>
                    <Button asChild>
                        <Link href={list.href}>View {list.title}</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={create(csvImport.type)}>
                            Import another file
                        </Link>
                    </Button>
                </>
            )}

            {csvImport.has_errors_file && (
                <Button variant="outline" asChild>
                    <a href={errorsFile.url(csvImport.id)}>
                        <Download />
                        Download errors CSV
                    </a>
                </Button>
            )}
        </div>
    );
}
