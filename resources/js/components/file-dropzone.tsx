import { FileSpreadsheet, UploadCloud, X } from 'lucide-react';
import { useRef, useState } from 'react';
import type { ChangeEvent, DragEvent } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type FileDropzoneProps = {
    /** The form field name, so the file is submitted with the surrounding form. */
    name: string;
    id?: string;
    /** Accepted file extensions, including the dot, e.g. `['.csv']`. */
    extensions: string[];
    /** The largest accepted file, in bytes. */
    maxBytes: number;
    /** Upload progress from 0 to 100, shown while the form is submitting. */
    progress?: number | null;
    disabled?: boolean;
    /** A server-side error for the field. */
    error?: string;
    'aria-describedby'?: string;
};

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    if (bytes < 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${(bytes / (1024 * 1024 * 1024)).toFixed(1)} GB`;
}

/**
 * A drag and drop area for one file. It keeps a real file input inside, so
 * the file is submitted by the surrounding form and the area stays usable
 * with the keyboard and by clicking.
 */
export function FileDropzone({
    name,
    id = `field-${name}`,
    extensions,
    maxBytes,
    progress = null,
    disabled = false,
    error,
    'aria-describedby': describedBy,
}: FileDropzoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [file, setFile] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    const shownError = localError ?? error;

    const problemWith = (candidate: File): string | null => {
        const lowerName = candidate.name.toLowerCase();

        if (!extensions.some((extension) => lowerName.endsWith(extension))) {
            return `The file must be a ${extensions.join(' or ')} file.`;
        }

        if (candidate.size > maxBytes) {
            return `The file must not be larger than ${formatBytes(maxBytes)}.`;
        }

        return null;
    };

    const accept = (candidate: File | undefined) => {
        if (!candidate) {
            return;
        }

        const problem = problemWith(candidate);

        if (problem !== null) {
            clear();
            setLocalError(problem);

            return;
        }

        if (inputRef.current) {
            const transfer = new DataTransfer();
            transfer.items.add(candidate);
            inputRef.current.files = transfer.files;
        }

        setLocalError(null);
        setFile(candidate);
    };

    const clear = () => {
        if (inputRef.current) {
            inputRef.current.value = '';
        }

        setFile(null);
        setLocalError(null);
    };

    const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
        accept(event.target.files?.[0]);
    };

    const handleDrop = (event: DragEvent<HTMLLabelElement>) => {
        event.preventDefault();
        setIsDragging(false);

        if (!disabled) {
            accept(event.dataTransfer.files[0]);
        }
    };

    const handleDragOver = (event: DragEvent<HTMLLabelElement>) => {
        event.preventDefault();

        if (!disabled) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (event: DragEvent<HTMLLabelElement>) => {
        if (!event.currentTarget.contains(event.relatedTarget as Node | null)) {
            setIsDragging(false);
        }
    };

    return (
        <div className="flex flex-col gap-2">
            <label
                htmlFor={id}
                onDragEnter={handleDragOver}
                onDragOver={handleDragOver}
                onDragLeave={handleDragLeave}
                onDrop={handleDrop}
                data-dragging={isDragging}
                data-invalid={!!shownError}
                className={cn(
                    'group relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-6 py-10 text-center transition-colors',
                    'border-border bg-muted/30 hover:border-primary/60 hover:bg-primary/5',
                    'has-[:focus-visible]:border-primary has-[:focus-visible]:ring-3 has-[:focus-visible]:ring-ring/50',
                    'data-[dragging=true]:border-primary data-[dragging=true]:bg-primary/10',
                    'data-[invalid=true]:border-destructive/60 data-[invalid=true]:bg-destructive/5',
                    disabled && 'pointer-events-none opacity-60',
                )}
            >
                <input
                    ref={inputRef}
                    id={id}
                    type="file"
                    name={name}
                    accept={extensions.join(',')}
                    onChange={handleChange}
                    disabled={disabled}
                    aria-invalid={!!shownError}
                    aria-describedby={describedBy}
                    className="sr-only"
                />

                {file === null ? (
                    <>
                        <span
                            className={cn(
                                'flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary transition-transform',
                                isDragging && 'scale-110',
                            )}
                        >
                            <UploadCloud className="size-6" />
                        </span>
                        <span className="flex flex-col gap-1">
                            <span className="text-sm font-medium">
                                {isDragging ? (
                                    'Drop the file to select it'
                                ) : (
                                    <>
                                        Drag and drop your file here, or{' '}
                                        <span className="text-primary underline underline-offset-4">
                                            browse
                                        </span>
                                    </>
                                )}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {extensions.join(', ')} up to{' '}
                                {formatBytes(maxBytes)}
                            </span>
                        </span>
                    </>
                ) : (
                    <div className="flex w-full max-w-md items-center gap-3 rounded-lg border bg-background p-3 text-left shadow-xs">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <FileSpreadsheet className="size-5" />
                        </span>
                        <span className="flex min-w-0 flex-1 flex-col gap-1">
                            <span className="truncate text-sm font-medium">
                                {file.name}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {formatBytes(file.size)}
                                {progress !== null && ` · ${progress}%`}
                            </span>
                            {progress !== null && (
                                <span className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <span
                                        className="block h-full rounded-full bg-primary transition-[width]"
                                        style={{ width: `${progress}%` }}
                                    />
                                </span>
                            )}
                        </span>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            aria-label="Remove file"
                            disabled={disabled}
                            onClick={(event) => {
                                event.preventDefault();
                                clear();
                            }}
                        >
                            <X />
                        </Button>
                    </div>
                )}
            </label>

            {shownError && (
                <p role="alert" className="text-sm text-destructive">
                    {shownError}
                </p>
            )}
        </div>
    );
}
