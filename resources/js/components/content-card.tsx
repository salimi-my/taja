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
    children: ReactNode;
};

/**
 * The standard titled card used for page sections such as settings forms.
 */
export function ContentCard({
    title,
    description,
    children,
}: ContentCardProps) {
    return (
        <Card className="border border-sidebar-border ring-0">
            <CardHeader className="pb-3">
                <CardTitle className="text-xl font-semibold">{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}
