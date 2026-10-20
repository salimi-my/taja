import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import { ContentCard } from '@/components/content-card';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Appearance settings" />

            <h1 className="sr-only">Appearance settings</h1>

            <ContentCard
                title="Appearance"
                description="Update the appearance settings for your account."
            >
                <AppearanceTabs />
            </ContentCard>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};
