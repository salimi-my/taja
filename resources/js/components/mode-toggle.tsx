import { MoonIcon, SunIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';

export function ModeToggle() {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    return (
        <TooltipProvider delayDuration={0}>
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        className="right-0 size-8 bg-sidebar dark:bg-sidebar"
                        variant="outline"
                        size="icon"
                        onClick={() =>
                            updateAppearance(
                                resolvedAppearance === 'dark'
                                    ? 'light'
                                    : 'dark',
                            )
                        }
                    >
                        <SunIcon className="h-[1.2rem] w-[1.2rem] scale-0 rotate-90 transition-transform duration-500 ease-in-out dark:scale-100 dark:rotate-0" />
                        <MoonIcon className="absolute h-[1.2rem] w-[1.2rem] scale-100 rotate-0 transition-transform duration-500 ease-in-out dark:scale-0 dark:-rotate-90" />
                        <span className="sr-only">Switch theme</span>
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="bottom">Switch theme</TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
