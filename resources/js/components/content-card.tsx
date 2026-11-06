import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type ContentCardProps = {
    title: string;
    description: string;
    /** An optional icon shown in front of the title. */
    icon?: LucideIcon;
    children: ReactNode;
};

/**
 * The standard titled card used for page sections such as settings forms.
 */
export function ContentCard({
    title,
    description,
    icon: Icon,
    children,
}: ContentCardProps) {
    return (
        <Card className="border border-sidebar-border ring-0">
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-xl font-semibold">
                    {Icon && (
                        <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <Icon className="size-4" />
                        </span>
                    )}
                    {title}
                </CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}
