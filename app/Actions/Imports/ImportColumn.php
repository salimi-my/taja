<?php

namespace App\Actions\Imports;

/**
 * One column of an import file. The template, the field guide and the
 * validator all read these, so they cannot drift apart.
 */
final readonly class ImportColumn
{
    /**
     * @param  string  $header  The CSV header, e.g. `Publisher_Name`.
     * @param  string  $format  A short human description of the accepted values.
     * @param  string  $example  A fake example value.
     * @param  string|null  $field  The record attribute the cell is read into, or null when the importer handles the cell itself.
     */
    public function __construct(
        public string $header,
        public bool $required,
        public string $format,
        public string $example,
        public ?string $field = null,
        public ImportCast $cast = ImportCast::Text,
    ) {}

    /**
     * @return array{header: string, required: bool, format: string, example: string}
     */
    public function toGuide(): array
    {
        return [
            'header' => $this->header,
            'required' => $this->required,
            'format' => $this->format,
            'example' => $this->example,
        ];
    }
}
