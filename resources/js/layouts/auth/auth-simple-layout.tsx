import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { AuthFooter } from '@/components/auth-footer';
import { ModeToggle } from '@/components/mode-toggle';
import { Card, CardContent } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center bg-muted p-6 pt-20 md:p-10">
            <Link
                href={home()}
                className="absolute top-5 left-5 flex items-center gap-2"
            >
                <AppLogo />
            </Link>
            <div className="absolute top-5 right-5">
                <ModeToggle />
            </div>

            <div className="flex w-full max-w-sm flex-col gap-6 md:max-w-216">
                <Card className="overflow-hidden p-0">
                    <CardContent className="grid min-h-112.5 p-0 md:grid-cols-2">
                        <div className="flex h-full flex-col justify-center gap-6 p-6 md:p-8">
                            <div className="flex flex-col items-center text-center">
                                <h1 className="text-2xl font-bold">{title}</h1>
                                <p className="text-pretty text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                            {children}
                        </div>
                        <div className="relative hidden items-center justify-center overflow-hidden bg-primary/15 p-4 md:flex dark:bg-primary/95">
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-primary/15 dark:stroke-primary-foreground/15" />
                            <div className="relative flex size-24 items-center justify-center rounded-3xl bg-primary text-primary-foreground shadow-lg dark:bg-primary-foreground dark:text-primary">
                                <AppLogoIcon className="size-14 fill-current" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <AuthFooter />
            </div>
        </div>
    );
}
