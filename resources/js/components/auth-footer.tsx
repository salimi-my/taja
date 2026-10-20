import { usePage } from '@inertiajs/react';
import { Separator } from '@/components/ui/separator';

export function AuthFooter() {
    const { name } = usePage().props;
    const year = new Date().getFullYear();

    return (
        <footer className="flex shrink-0 items-center justify-center gap-1 px-4 text-muted-foreground">
            <p className="text-xs leading-tight text-nowrap">&copy; {year}</p>
            <Separator orientation="vertical" className="mx-1 h-4!" />
            <p className="text-xs leading-tight font-semibold">{name}</p>
        </footer>
    );
}
