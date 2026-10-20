import { Head, Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, LogIn, UserPlus } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { AuthFooter } from '@/components/auth-footer';
import { ModeToggle } from '@/components/mode-toggle';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="relative flex min-h-svh flex-col items-center justify-center gap-8 bg-muted p-6">
                <div className="absolute top-5 right-5">
                    <ModeToggle />
                </div>

                <div className="flex max-w-md flex-col items-center gap-6 text-center">
                    <div className="flex size-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg">
                        <AppLogoIcon className="size-9 fill-current" />
                    </div>

                    <div className="space-y-2">
                        <h1 className="text-3xl font-bold tracking-tight">
                            {name}
                        </h1>
                        <p className="text-pretty text-muted-foreground">
                            A Laravel and Inertia React admin dashboard starter
                            kit with users management, settings and
                            authentication ready to go.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center justify-center gap-2">
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>
                                    <LayoutDashboard />
                                    Go to dashboard
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild>
                                    <Link href={login()}>
                                        <LogIn />
                                        Log in
                                    </Link>
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={register()}>
                                        <UserPlus />
                                        Register
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <AuthFooter />
            </div>
        </>
    );
}
