import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <AppLogoIcon className="size-5! fill-current" />
            </div>
            <div className="grid flex-1 text-left leading-tight">
                <span className="truncate text-base font-bold">{name}</span>
            </div>
        </>
    );
}
