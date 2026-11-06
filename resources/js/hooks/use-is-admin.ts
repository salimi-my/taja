import { usePage } from '@inertiajs/react';
import type { Auth } from '@/types';

/** Whether the signed-in user has the admin role. Used to hide write actions. */
export function useIsAdmin(): boolean {
    const { auth } = usePage<{ auth: Auth }>().props;

    return auth.user?.role === 'admin';
}
