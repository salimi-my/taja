import { CheckIcon } from 'lucide-react';
import { useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Combobox } from '@/components/ui/combobox';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
    InputGroupText,
} from '@/components/ui/input-group';
import { MultiSelect } from '@/components/ui/multi-select';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

export type Errors = Record<string, string | undefined>;

/**
 * Turn Laravel's dotted error keys (`parties.0.role`) into the bracket names
 * the form inputs use (`parties[0][role]`), so repeater rows can pass them to
 * the shared field components.
 */
export function toBracketErrors(errors: Errors): Errors {
    return Object.fromEntries(
        Object.entries(errors).map(([key, message]) => {
            const [head, ...rest] = key.split('.');

            return [
                rest.length > 0
                    ? head + rest.map((part) => `[${part}]`).join('')
                    : key,
                message,
            ];
        }),
    );
}

/** The first error for a field, or for any of its list items such as `name.0`. */
function firstErrorFor(errors: Errors, name: string): string | undefined {
    return (
        errors[name] ??
        Object.entries(errors).find(([key]) => key.startsWith(`${name}.`))?.[1]
    );
}

type TextFieldProps = {
    name: string;
    label: string;
    errors: Errors;
    defaultValue?: string | null;
    type?: 'text' | 'email';
    placeholder?: string;
    description?: string;
    className?: string;
};

export function TextField({
    name,
    label,
    errors,
    defaultValue,
    type = 'text',
    placeholder,
    description,
    className,
}: TextFieldProps) {
    return (
        <Field data-invalid={!!errors[name]} className={className}>
            <FieldLabel htmlFor={`field-${name}`}>{label}</FieldLabel>
            <Input
                id={`field-${name}`}
                name={name}
                type={type}
                defaultValue={defaultValue ?? ''}
                placeholder={placeholder}
                autoComplete="off"
                aria-invalid={!!errors[name]}
            />
            {description && <FieldDescription>{description}</FieldDescription>}
            <FieldError>{errors[name]}</FieldError>
        </Field>
    );
}

type PercentageFieldProps = {
    name: string;
    label: string;
    errors: Errors;
    defaultValue?: string | null;
    placeholder?: string;
    description?: string;
    className?: string;
};

/** A number input from 0 to 100, with up to 2 decimals and a `%` suffix. */
export function PercentageField({
    name,
    label,
    errors,
    defaultValue,
    placeholder,
    description,
    className,
}: PercentageFieldProps) {
    return (
        <Field data-invalid={!!errors[name]} className={className}>
            <FieldLabel htmlFor={`field-${name}`}>{label}</FieldLabel>
            <InputGroup>
                <InputGroupInput
                    id={`field-${name}`}
                    name={name}
                    type="number"
                    inputMode="decimal"
                    min={0}
                    max={100}
                    step={0.01}
                    defaultValue={defaultValue ?? ''}
                    placeholder={placeholder}
                    autoComplete="off"
                    aria-invalid={!!errors[name]}
                    className="[appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                />
                <InputGroupAddon align="inline-end">
                    <InputGroupText>%</InputGroupText>
                </InputGroupAddon>
            </InputGroup>
            {description && <FieldDescription>{description}</FieldDescription>}
            <FieldError>{errors[name]}</FieldError>
        </Field>
    );
}

type TextAreaFieldProps = {
    name: string;
    label: string;
    errors: Errors;
    defaultValue?: string | null;
    className?: string;
};

export function TextAreaField({
    name,
    label,
    errors,
    defaultValue,
    className = 'sm:col-span-2',
}: TextAreaFieldProps) {
    return (
        <Field data-invalid={!!errors[name]} className={className}>
            <FieldLabel htmlFor={`field-${name}`}>{label}</FieldLabel>
            <Textarea
                id={`field-${name}`}
                name={name}
                defaultValue={defaultValue ?? ''}
                aria-invalid={!!errors[name]}
            />
            <FieldError>{errors[name]}</FieldError>
        </Field>
    );
}

/** Selects with this many options or fewer are shown as a toggle group. */
const TOGGLE_GROUP_MAX_OPTIONS = 3;

type SelectFieldProps = {
    name: string;
    label: string;
    errors: Errors;
    options: { value: string; label: string }[];
    defaultValue: string;
    description?: string;
    className?: string;
    /**
     * `auto` shows a toggle group for a few options and a searchable combobox
     * for more. Use `combobox` for lists that come from the database and can
     * grow, so the control does not change shape as rows are added.
     */
    display?: 'auto' | 'combobox';
};

/**
 * A select that submits its value through a hidden input. The special value
 * `unset` is sent as an empty string, which the server stores as null.
 */
export function SelectField({
    name,
    label,
    errors,
    options,
    defaultValue,
    description,
    className,
    display = 'auto',
}: SelectFieldProps) {
    const [value, setValue] = useState(defaultValue);
    const id = `field-${name}`;
    const usesToggleGroup =
        display === 'auto' && options.length <= TOGGLE_GROUP_MAX_OPTIONS;

    return (
        <Field data-invalid={!!errors[name]} className={className}>
            <FieldLabel htmlFor={usesToggleGroup ? undefined : id}>
                {label}
            </FieldLabel>
            <input
                type="hidden"
                name={name}
                value={value === 'unset' ? '' : value}
            />
            {usesToggleGroup ? (
                <ToggleGroup
                    type="single"
                    variant="outline"
                    spacing={0}
                    aria-label={label}
                    value={value}
                    onValueChange={(next) => {
                        // Clicking the active option would clear the group.
                        if (next) {
                            setValue(next);
                        }
                    }}
                    className="w-full"
                >
                    {options.map((option) => (
                        <ToggleGroupItem
                            key={option.value}
                            value={option.value}
                            className="flex-1 gap-1.5 data-[state=on]:font-medium"
                        >
                            {value === option.value && (
                                <CheckIcon className="size-4" />
                            )}
                            {option.label}
                        </ToggleGroupItem>
                    ))}
                </ToggleGroup>
            ) : (
                <Combobox
                    id={id}
                    options={options}
                    value={value}
                    onValueChange={(next) => setValue(String(next))}
                    aria-invalid={!!errors[name]}
                    placeholder={`Select ${label.toLowerCase()}`}
                    searchPlaceholder="Search..."
                />
            )}
            {description && <FieldDescription>{description}</FieldDescription>}
            <FieldError>{errors[name]}</FieldError>
        </Field>
    );
}

type DateFieldProps = {
    name: string;
    label: string;
    errors: Errors;
    /** The date as `YYYY-MM-DD`. */
    defaultValue?: string | null;
    description?: string;
    className?: string;
};

/** A date picker that submits its `YYYY-MM-DD` value through a hidden input. */
export function DateField({
    name,
    label,
    errors,
    defaultValue,
    description,
    className,
}: DateFieldProps) {
    const [value, setValue] = useState(defaultValue ?? '');
    const id = `field-${name}`;

    return (
        <Field data-invalid={!!errors[name]} className={className}>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <input type="hidden" name={name} value={value} />
            <DatePicker
                id={id}
                value={value}
                onChange={setValue}
                aria-invalid={!!errors[name]}
            />
            {description && <FieldDescription>{description}</FieldDescription>}
            <FieldError>{errors[name]}</FieldError>
        </Field>
    );
}

type CheckboxGroupFieldProps = {
    /** The array field name without brackets, e.g. `rights_types_granted`. */
    name: string;
    label: string;
    errors: Errors;
    options: { value: string; label: string; description?: string }[];
    defaultValues?: string[];
    className?: string;
};

/** A group of checkboxes shown as selectable cards, submitted as `name[]`. */
export function CheckboxGroupField({
    name,
    label,
    errors,
    options,
    defaultValues = [],
    className = 'sm:col-span-2',
}: CheckboxGroupFieldProps) {
    const error = firstErrorFor(errors, name);

    return (
        <Field data-invalid={!!error} className={className}>
            <FieldLabel>{label}</FieldLabel>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => {
                    const id = `field-${name}-${option.value}`;

                    return (
                        <FieldLabel
                            key={option.value}
                            htmlFor={id}
                            className="min-w-0 flex-1 basis-48"
                        >
                            <Field orientation="horizontal">
                                <Checkbox
                                    id={id}
                                    name={`${name}[]`}
                                    value={option.value}
                                    defaultChecked={defaultValues.includes(
                                        option.value,
                                    )}
                                />
                                <FieldContent>
                                    <FieldTitle>{option.label}</FieldTitle>
                                    {option.description && (
                                        <FieldDescription>
                                            {option.description}
                                        </FieldDescription>
                                    )}
                                </FieldContent>
                            </Field>
                        </FieldLabel>
                    );
                })}
            </div>
            <FieldError>{error}</FieldError>
        </Field>
    );
}

const WORLDWIDE = 'WW';

type TerritoryFieldProps = {
    /** The list field name, e.g. `territories_granted`. */
    name: string;
    label: string;
    errors: Errors;
    countries: { code: string; name: string }[];
    /** The saved territory codes: `WW` or country codes. */
    defaultValues?: string[];
    description?: string;
    className?: string;
};

/**
 * A multi-select of countries with a "Worldwide" option. Worldwide cannot be
 * combined with countries, so picking one clears the other. The codes are
 * submitted as `name[]`, or as a single empty `name` when nothing is picked so
 * that clearing the field still reaches the server.
 */
export function TerritoryField({
    name,
    label,
    errors,
    countries,
    defaultValues = [],
    description,
    className,
}: TerritoryFieldProps) {
    const [values, setValues] = useState(defaultValues);
    const error = firstErrorFor(errors, name);

    const handleChange = (next: string[]) => {
        const wasWorldwide = values.includes(WORLDWIDE);

        if (next.includes(WORLDWIDE) && !wasWorldwide) {
            setValues([WORLDWIDE]);
        } else if (wasWorldwide && next.length > 1) {
            setValues(next.filter((code) => code !== WORLDWIDE));
        } else {
            setValues(next);
        }
    };

    return (
        <Field data-invalid={!!error} className={className}>
            <FieldLabel htmlFor={`field-${name}`}>{label}</FieldLabel>
            {values.length > 0 ? (
                values.map((code) => (
                    <input
                        key={code}
                        type="hidden"
                        name={`${name}[]`}
                        value={code}
                    />
                ))
            ) : (
                <input type="hidden" name={name} value="" />
            )}
            <MultiSelect
                id={`field-${name}`}
                options={[
                    {
                        heading: 'Worldwide',
                        options: [
                            { label: 'Worldwide (WW)', value: WORLDWIDE },
                        ],
                    },
                    {
                        heading: 'Countries',
                        options: countries.map((country) => ({
                            label: `${country.name} (${country.code})`,
                            value: country.code,
                        })),
                    },
                ]}
                defaultValue={values}
                onValueChange={handleChange}
                placeholder="Select territories"
                maxCount={6}
                hideSelectAll
                modalPopover
                aria-invalid={!!error}
                className="min-h-9"
            />
            {description && <FieldDescription>{description}</FieldDescription>}
            <FieldError>{error}</FieldError>
        </Field>
    );
}

export const YES_NO_OPTIONS = [
    { value: 'unset', label: 'Not set' },
    { value: '1', label: 'Yes' },
    { value: '0', label: 'No' },
];

export function booleanToSelectValue(
    value: boolean | null | undefined,
): string {
    if (value === null || value === undefined) {
        return 'unset';
    }

    return value ? '1' : '0';
}
