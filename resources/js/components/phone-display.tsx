import { parsePhoneNumber } from 'react-phone-number-input';
import flags from 'react-phone-number-input/flags';
import { cn } from '@/lib/utils';

type PhoneDisplayProps = {
    phone: string | null;
    className?: string;
};

/**
 * Show an E.164 phone number in international format with its country flag.
 */
export function PhoneDisplay({ phone, className }: PhoneDisplayProps) {
    if (!phone) {
        return (
            <span className={cn('text-muted-foreground', className)}>-</span>
        );
    }

    let parsed: ReturnType<typeof parsePhoneNumber>;

    try {
        parsed = parsePhoneNumber(phone);
    } catch {
        parsed = undefined;
    }

    const Flag = parsed?.country
        ? flags[parsed.country as keyof typeof flags]
        : undefined;

    if (!parsed || !Flag) {
        return <span className={className}>{phone}</span>;
    }

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <span className="flex h-3.5 w-5.5 shrink-0 overflow-hidden border bg-foreground/20 md:h-4.5 md:w-6.5 [&_svg:not([class*='size-'])]:size-full">
                <Flag title={parsed.country ?? ''} />
            </span>
            <span className="text-nowrap">{parsed.formatInternational()}</span>
        </div>
    );
}
