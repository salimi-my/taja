import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { BreadcrumbContent } from '@/components/breadcrumb-content';
import { Footer } from '@/components/footer';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className="mt-16 flex min-h-[calc(100svh-160px)] flex-1 flex-col gap-6 p-4 print:mt-0 print:min-h-0 print:p-0">
                    <div className="print:hidden">
                        <BreadcrumbContent breadcrumbs={breadcrumbs} />
                    </div>
                    {children}
                </div>
                <Footer />
            </AppContent>
        </AppShell>
    );
}
