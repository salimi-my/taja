import type { UserRole } from '@/types/auth';

/** A user as returned by `App\Http\Resources\UserResource`. */
export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
    avatar_url: string | null;
    email_verified_at: string | null;
    created_at: string | null;
    is_current_user: boolean;
};

/** A role as returned by `App\Enums\UserRole::options()`. */
export type RoleOption = {
    value: string;
    label: string;
};
