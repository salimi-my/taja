import { AlertCircle, RefreshCw } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';

interface TableFetchErrorProps {
    resource: string;
    onRetry: () => void;
}

export function TableFetchError({ resource, onRetry }: TableFetchErrorProps) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <AlertCircle className="text-destructive" />
                </EmptyMedia>
                <EmptyTitle>Failed to load {resource}</EmptyTitle>
                <EmptyDescription>
                    Unable to fetch {resource}. Please check your connection and
                    try again.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <Button variant="outline" size="sm" onClick={onRetry}>
                    <RefreshCw />
                    Retry
                </Button>
            </EmptyContent>
        </Empty>
    );
}
