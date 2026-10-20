import { Head } from '@inertiajs/react';
import { ContentCard } from '@/components/content-card';
import { UsersTable } from '@/components/users/users-table';
import type { ServerFilters } from '@/lib/table';
import { index } from '@/routes/users';
import type { ManagedUser, PaginatedResource, RoleOption } from '@/types';

type Props = {
    users: PaginatedResource<ManagedUser>;
    filters: ServerFilters;
    roles: RoleOption[];
};

export default function UsersIndex({ users, filters, roles }: Props) {
    return (
        <>
            <Head title="Users" />

            <ContentCard
                title="Users List"
                description="Here's the listing of all the users."
            >
                <UsersTable users={users} filters={filters} roles={roles} />
            </ContentCard>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
    ],
};
