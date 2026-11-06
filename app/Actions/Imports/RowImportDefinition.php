<?php

namespace App\Actions\Imports;

use App\Enums\MasterStatus;
use App\Enums\PartyRole;
use App\Enums\RightsType;
use App\Models\CsvImport;
use App\Rules\TerritoryList;
use BackedEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * An import where one row creates one record. Each row is turned into
 * attributes, validated with the same rules as the matching form and checked
 * for repeats inside the file.
 */
abstract class RowImportDefinition extends ImportDefinition
{
    private ?DuplicateTracker $tracker = null;

    /** @var array<string, array<int, mixed>>|null */
    private ?array $checkedRules = null;

    /**
     * The validation rules, keyed by record attribute. `unique` and `exists`
     * rules are skipped: they would query the database for every row, so
     * uniqueness is checked once per chunk and lookups go through `prepareChunk`.
     *
     * @return array<string, array<int, ValidationRule|callable|array<mixed>|string>>
     */
    abstract protected function rules(): array;

    /**
     * The table the records are created in, used to find values that are already taken.
     */
    abstract protected function table(): string;

    /**
     * Create one record from validated attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    abstract protected function createRecord(CsvImport $import, array $attributes): void;

    /**
     * Attributes that must be unique, in the file and in the table, as attribute => header.
     *
     * @return array<string, string>
     */
    protected function uniqueFields(): array
    {
        return [];
    }

    /**
     * Attributes that only produce a warning when used twice, in the file or in the table.
     *
     * @return array<string, string>
     */
    protected function warnOnRepeatFields(): array
    {
        return [];
    }

    /**
     * Load whatever the row checks need for the whole chunk with one query.
     *
     * @param  list<array{line: int, data: array<string, string|null>}>  $rows
     */
    protected function prepareChunk(array $rows): void {}

    /**
     * Fill attributes that need more than a cast (e.g. a code looked up in the database).
     *
     * @param  array<string, string|null>  $data
     * @param  array<string, mixed>  $attributes
     * @return list<array{column: string|null, message: string}>
     */
    protected function resolveExtras(array $data, array &$attributes): array
    {
        return [];
    }

    /**
     * Checks that need several attributes, run once the rules pass.
     *
     * @param  array<string, mixed>  $attributes
     * @return list<array{column: string|null, message: string}>
     */
    protected function crossChecks(array $attributes): array
    {
        return [];
    }

    public function begin(CsvImport $import): void
    {
        $this->tracker = new DuplicateTracker($import->id);
        $this->tracker->clear();
    }

    public function finish(CsvImport $import): void
    {
        $this->tracker?->clear();
    }

    public function validateChunk(array $rows): ChunkResult
    {
        $result = new ChunkResult;

        $this->prepareChunk($rows);

        $attributesByLine = [];

        foreach ($rows as $row) {
            $attributesByLine[$row['line']] = $this->normalize($row['data']);
        }

        $fields = [...$this->uniqueFields(), ...$this->warnOnRepeatFields()];
        $taken = $this->takenValues($attributesByLine, array_keys($fields));
        $repeats = $this->repeatedValues($attributesByLine, array_keys($fields));

        foreach ($rows as $row) {
            $line = $row['line'];
            $attributes = $attributesByLine[$line];

            $errors = $this->resolveExtras($row['data'], $attributes);
            $errors = [...$errors, ...$this->ruleErrors($attributes)];

            if ($errors === []) {
                $errors = $this->crossChecks($attributes);
            }

            foreach ($this->uniqueFields() as $field => $header) {
                $message = $this->repeatMessage($attributes, $field, $header, $line, $taken, $repeats, 'The :header :value has already been taken.', 'The :header :value is already used on line :line of this file.');

                if ($message !== null) {
                    $errors[] = ['column' => $header, 'message' => $message];
                }
            }

            foreach ($errors as $error) {
                $result->addError($line, $error['column'], $error['message']);
            }

            foreach ($this->warnOnRepeatFields() as $field => $header) {
                $message = $this->repeatMessage($attributes, $field, $header, $line, $taken, $repeats, 'A record with the :header :value already exists.', 'The :header :value is also used on line :line of this file.');

                if ($message !== null) {
                    $result->addWarning($line, $header, $message);
                }
            }

            if ($errors === []) {
                $result->addRecord($line, $attributes);
            }
        }

        return $result;
    }

    public function commitChunk(CsvImport $import, array $records): array
    {
        try {
            DB::transaction(function () use ($import, $records): void {
                foreach ($records as $attributes) {
                    $this->createRecord($import, $attributes);
                }
            });

            return ['imported' => count($records), 'failed' => []];
        } catch (QueryException) {
            // A value became taken while the chunk was saved: save the rows one by one to find which.
        }

        $imported = 0;
        $failed = [];

        foreach ($records as $line => $attributes) {
            try {
                DB::transaction(fn () => $this->createRecord($import, $attributes));

                $imported++;
            } catch (QueryException) {
                $failed[$line] = 'The row could not be saved, most likely because a value is already used.';
            }
        }

        return ['imported' => $imported, 'failed' => $failed];
    }

    /**
     * Turn the cells of a row into record attributes.
     *
     * @param  array<string, string|null>  $data
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        $attributes = [];

        foreach ($this->columns() as $column) {
            if ($column->field === null) {
                continue;
            }

            $attributes[$column->field] = $this->cast($column->cast, $data[$column->header] ?? null);
        }

        return $attributes;
    }

    protected function cast(ImportCast $cast, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($cast) {
            ImportCast::Text => $value,
            ImportCast::Boolean => match (strtolower($value)) {
                'y', 'yes' => true,
                'n', 'no' => false,
                default => $value,
            },
            ImportCast::Territories => TerritoryList::parse($value) ?: null,
            ImportCast::RightsTypes => $this->castRightsTypes($value),
            ImportCast::Status => $this->enumValueOrText(MasterStatus::fromImport($value), $value),
            ImportCast::Role => $this->enumValueOrText(PartyRole::fromImport($value), $value),
        };
    }

    /**
     * @return list<string>|null
     */
    private function castRightsTypes(string $value): ?array
    {
        $items = array_values(array_filter(
            array_map('trim', preg_split('/[|,;]/', $value) ?: []),
            fn (string $item): bool => $item !== '',
        ));

        if ($items === []) {
            return null;
        }

        return array_map(
            fn (string $item): string => $this->enumValueOrText(RightsType::fromImport($item), $item),
            $items,
        );
    }

    /**
     * The value of the matched enum case, or the text as written when nothing matched.
     */
    private function enumValueOrText(?BackedEnum $case, string $text): string
    {
        return $case === null ? $text : (string) $case->value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{column: string|null, message: string}>
     */
    private function ruleErrors(array $attributes): array
    {
        $validator = Validator::make($attributes, $this->checkedRules(), [], $this->attributeNames());

        $errors = [];

        foreach ($validator->errors()->messages() as $key => $messages) {
            $header = $this->headerFor(explode('.', $key)[0]);

            foreach ($messages as $message) {
                $errors[] = ['column' => $header, 'message' => $message];
            }
        }

        return $errors;
    }

    /**
     * The rules without the ones that query the database for every row.
     *
     * @return array<string, array<int, mixed>>
     */
    private function checkedRules(): array
    {
        return $this->checkedRules ??= array_filter(array_map(
            fn (array $rules): array => array_values(array_filter(
                $rules,
                fn (mixed $rule): bool => ! $rule instanceof Unique && ! $rule instanceof Exists,
            )),
            $this->rules(),
        ));
    }

    /**
     * The values of the chunk that already exist in the table, lowercased, per attribute.
     *
     * @param  array<int, array<string, mixed>>  $attributesByLine
     * @param  list<string>  $fields
     * @return array<string, array<string, true>>
     */
    private function takenValues(array $attributesByLine, array $fields): array
    {
        $taken = [];

        foreach ($fields as $field) {
            $values = array_values(array_unique($this->valuesOf($attributesByLine, $field)));

            $taken[$field] = $values === [] ? [] : array_fill_keys(
                array_map(
                    fn (mixed $value): string => strtolower((string) $value),
                    DB::table($this->table())->whereIn($field, $values)->pluck($field)->all(),
                ),
                true,
            );
        }

        return $taken;
    }

    /**
     * The rows of the chunk that repeat a value of an earlier row of the file, per attribute.
     *
     * @param  array<int, array<string, mixed>>  $attributesByLine
     * @param  list<string>  $fields
     * @return array<string, array<int, int>>
     */
    private function repeatedValues(array $attributesByLine, array $fields): array
    {
        $repeats = [];

        foreach ($fields as $field) {
            $repeats[$field] = $this->tracker?->register($field, $this->valuesOf($attributesByLine, $field)) ?? [];
        }

        return $repeats;
    }

    /**
     * The non-empty string values of an attribute, keyed by line.
     *
     * @param  array<int, array<string, mixed>>  $attributesByLine
     * @return array<int, string>
     */
    private function valuesOf(array $attributesByLine, string $field): array
    {
        $values = [];

        foreach ($attributesByLine as $line => $attributes) {
            $value = $attributes[$field] ?? null;

            if (is_string($value) && $value !== '') {
                $values[$line] = $value;
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, array<string, true>>  $taken
     * @param  array<string, array<int, int>>  $repeats
     */
    private function repeatMessage(array $attributes, string $field, string $header, int $line, array $taken, array $repeats, string $takenMessage, string $repeatMessage): ?string
    {
        $value = $attributes[$field] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (isset($repeats[$field][$line])) {
            return strtr($repeatMessage, [':header' => $header, ':value' => $value, ':line' => (string) $repeats[$field][$line]]);
        }

        if (isset($taken[$field][strtolower($value)])) {
            return strtr($takenMessage, [':header' => $header, ':value' => $value]);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        $names = [];

        foreach ($this->columns() as $column) {
            if ($column->field !== null) {
                $names[$column->field] = $column->header;
                $names[$column->field.'.*'] = $column->header;
            }
        }

        return $names;
    }

    private function headerFor(string $field): ?string
    {
        foreach ($this->columns() as $column) {
            if ($column->field === $field) {
                return $column->header;
            }
        }

        return null;
    }
}
