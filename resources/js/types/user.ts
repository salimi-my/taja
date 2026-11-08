import type { UserRole } from '@/types/auth';

export type Gender = 'male' | 'female' | 'other';

/** A user as returned by `App\Http\Resources\UserResource`. */
export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    gender: Gender | null;
    gender_label: string | null;
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

/** A gender as returned by `App\Enums\Gender::options()`. */
export type GenderOption = {
    value: Gender;
    label: string;
};
