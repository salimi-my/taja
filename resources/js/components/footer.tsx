import { usePage } from '@inertiajs/react';
import { Separator } from '@/components/ui/separator';

export function Footer() {
    const { name } = usePage().props;
    const year = new Date().getFullYear();

    return (
        <footer className="flex h-16 shrink-0 flex-col justify-center gap-2 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:items-start">
            <div className="flex items-center gap-2 px-4">
                <p className="text-xs leading-tight text-nowrap sm:text-sm">
                    &copy; {year}
                </p>
                <Separator orientation="vertical" className="mx-1 h-4!" />
                <p className="text-xs leading-tight font-semibold sm:text-sm">
                    {name}
                </p>
            </div>
        </footer>
    );
}
