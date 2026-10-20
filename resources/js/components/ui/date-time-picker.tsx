
import { format, isValid, parse } from "date-fns";
import { CalendarIcon, ClockIcon } from "lucide-react";
import * as React from "react";

import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Input } from "@/components/ui/input";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { cn } from "@/lib/utils";

interface DateTimePickerProps {
  value?: string;
  onChange?: (value: string) => void;
  disabled?: boolean;
  id?: string;
  placeholder?: string;
  "aria-invalid"?: boolean;
}

/** Parses a datetime-local string ("YYYY-MM-DDTHH:mm") into { date, time } parts. */
function parseDateTimeLocal(value: string | undefined): {
  date: Date | undefined;
  time: string;
} {
  if (!value) return { date: undefined, time: "00:00" };
  const [datePart, timePart] = value.split("T");
  const date = parse(datePart, "yyyy-MM-dd", new Date());
  return {
    date: isValid(date) ? date : undefined,
    time: timePart ?? "00:00",
  };
}

export function DateTimePicker({
  value,
  onChange,
  disabled,
  id,
  placeholder = "Pick a date & time",
  "aria-invalid": ariaInvalid,
}: DateTimePickerProps) {
  const [open, setOpen] = React.useState(false);
  const { date, time } = parseDateTimeLocal(value);

  const handleDaySelect = (selected: Date | undefined) => {
    if (!selected) return;
    const datePart = format(selected, "yyyy-MM-dd");
    onChange?.(`${datePart}T${time}`);
  };

  const handleTimeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newTime = e.target.value;
    if (date) {
      const datePart = format(date, "yyyy-MM-dd");
      onChange?.(`${datePart}T${newTime}`);
    } else {
      onChange?.(`T${newTime}`);
    }
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          id={id}
          variant="outline"
          disabled={disabled}
          aria-invalid={ariaInvalid}
          className={cn(
            "w-full justify-start text-left font-normal",
            !value && "text-muted-foreground",
            ariaInvalid && "border-destructive",
          )}
        >
          <CalendarIcon className="mr-0.5 size-4 shrink-0" />
          {date ? (
            <span>
              {format(date, "d MMM yyyy")}{" "}
              {format(parse(time, "HH:mm", new Date()), "hh:mm aa")}
            </span>
          ) : (
            <span>{placeholder}</span>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align="start">
        <Calendar
          mode="single"
          selected={date}
          onSelect={handleDaySelect}
          captionLayout="dropdown"
          startMonth={new Date(2020, 0)}
          endMonth={new Date(2035, 11)}
          autoFocus
        />
        <div className="border-t p-3">
          <div className="flex items-center gap-2">
            <label className="text-muted-foreground flex min-w-12 items-center gap-1 text-xs">
              <ClockIcon className="size-3.5 shrink-0" />
              Time
            </label>
            <Input
              type="time"
              value={time}
              onChange={handleTimeChange}
              className="bg-background appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
            />
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
}
