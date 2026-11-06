import { Form } from '@inertiajs/react';
import { Download, Upload } from 'lucide-react';
import CsvImportController from '@/actions/App/Http/Controllers/CsvImportController';
import { FileDropzone } from '@/components/file-dropzone';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldLabel } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { example, template } from '@/routes/imports';
import type { ImportType } from '@/types';

type ImportUploadFormProps = {
    type: ImportType;
    maxRows: number;
    maxFileMegabytes: number;
};

export function ImportUploadForm({
    type,
    maxRows,
    maxFileMegabytes,
}: ImportUploadFormProps) {
    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-wrap gap-2">
                <Button variant="outline" asChild>
                    <a href={template.url(type)}>
                        <Download />
                        Download template
                    </a>
                </Button>
                <Button variant="outline" asChild>
                    <a href={example.url(type)}>
                        <Download />
                        Download example
                    </a>
                </Button>
            </div>

            <Form
                {...CsvImportController.store.form(type)}
                disableWhileProcessing
                className="flex flex-col gap-4"
            >
                {({ processing, errors, progress }) => (
                    <>
                        <Field data-invalid={!!errors.file}>
                            <FieldLabel htmlFor="field-file">
                                CSV file
                            </FieldLabel>
                            <FileDropzone
                                name="file"
                                id="field-file"
                                extensions={['.csv']}
                                maxBytes={maxFileMegabytes * 1024 * 1024}
                                progress={progress?.percentage ?? null}
                                disabled={processing}
                                error={errors.file}
                                aria-describedby="field-file-description"
                            />
                            <FieldDescription id="field-file-description">
                                UTF-8 CSV with a header row, up to{' '}
                                {maxFileMegabytes >= 1024
                                    ? `${maxFileMegabytes / 1024} GB`
                                    : `${maxFileMegabytes} MB`}{' '}
                                and {maxRows.toLocaleString()} data rows. The
                                file is checked in the background, and nothing
                                is imported until you confirm the preview.
                            </FieldDescription>
                        </Field>

                        <div>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <Spinner className="size-4" />
                                ) : (
                                    <Upload />
                                )}
                                Upload and preview
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </div>
    );
}
