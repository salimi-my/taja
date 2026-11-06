import { format, isValid, parse } from "date-fns";
import { CalendarIcon } from "lucide-react";
import * as React from "react";

import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";

const DATE_FORMAT = "yyyy-MM-dd";

interface DatePickerProps {
  /** The selected date as `YYYY-MM-DD`, or an empty string when none is picked. */
  value?: string;
  onChange?: (value: string) => void;
  disabled?: boolean;
  id?: string;
  placeholder?: string;
  /** Shows a clear button, for dates that are optional. */
  clearable?: boolean;
  "aria-invalid"?: boolean;
}

/** Parses a `YYYY-MM-DD` string, returning undefined when it is empty or invalid. */
function parseDate(value: string | undefined): Date | undefined {
  if (!value) return undefined;
  const date = parse(value, DATE_FORMAT, new Date());
  return isValid(date) ? date : undefined;
}

export function DatePicker({
  value,
  onChange,
  disabled,
  id,
  placeholder = "Pick a date",
  clearable = true,
  "aria-invalid": ariaInvalid,
}: DatePickerProps) {
  const [open, setOpen] = React.useState(false);
  const date = parseDate(value);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          id={id}
          type="button"
          variant="outline"
          disabled={disabled}
          aria-invalid={ariaInvalid}
          className={cn(
            "w-full justify-start text-left font-normal",
            !date && "text-muted-foreground",
          )}
        >
          <CalendarIcon className="size-4 shrink-0" />
          <span className="truncate">
            {date ? format(date, "d MMM yyyy") : placeholder}
          </span>
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align="start">
        <Calendar
          mode="single"
          selected={date}
          defaultMonth={date}
          onSelect={(selected) => {
            onChange?.(selected ? format(selected, DATE_FORMAT) : "");
            setOpen(false);
          }}
          captionLayout="dropdown"
          startMonth={new Date(1900, 0)}
          endMonth={new Date(new Date().getFullYear() + 30, 11)}
          autoFocus
        />
        {clearable && date && (
          <div className="border-t p-2">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="w-full"
              onClick={() => {
                onChange?.("");
                setOpen(false);
              }}
            >
              Clear date
            </Button>
          </div>
        )}
      </PopoverContent>
    </Popover>
  );
}
