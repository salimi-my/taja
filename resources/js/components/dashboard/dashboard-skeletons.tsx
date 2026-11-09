import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

/** Placeholder for the activity chart while its deferred data loads. */
export function ActivityChartSkeleton() {
    return (
        <Card
            aria-busy="true"
            className="flex h-full flex-col border-sidebar-border pt-0 max-md:pb-4"
        >
            <CardHeader className="flex flex-row gap-2 space-y-4 border-b py-5 max-md:p-4 md:items-center md:space-y-0">
                <div className="grid flex-1 gap-1">
                    <Skeleton className="h-4 w-24 max-md:h-[15.7px]" />
                    <Skeleton className="h-5 w-60 max-md:h-[14px] max-md:w-48" />
                </div>
                <Skeleton className="h-9 w-[140px] max-md:h-8 max-md:w-[125px]" />
            </CardHeader>
            <CardContent className="flex-1 px-2 pt-4 sm:px-6 sm:pt-6">
                <Skeleton className="h-[300px] w-full max-md:h-[200px]" />
            </CardContent>
            <CardFooter className="flex-col items-start gap-2 max-md:items-center max-md:px-4">
                <Skeleton className="h-[14px] w-56 max-md:h-3 max-md:w-44" />
                <Skeleton className="h-[14px] w-48 max-md:h-3 max-md:w-40" />
            </CardFooter>
        </Card>
    );
}

/** Placeholder for the conflict status chart while its deferred data loads. */
export function ConflictChartSkeleton() {
    return (
        <Card aria-busy="true" className="border-sidebar-border max-md:py-4">
            <CardHeader className="max-md:px-4">
                <Skeleton className="h-4 w-40 max-md:w-32" />
                <Skeleton className="h-5 w-60 max-md:h-3 max-md:w-48" />
            </CardHeader>
            <CardContent className="px-4 md:px-6">
                <Skeleton className="h-[240px] w-full max-md:h-[200px]" />
            </CardContent>
            <CardFooter className="max-md:px-4">
                <Skeleton className="h-4 w-48 max-md:h-3 max-md:w-40" />
            </CardFooter>
        </Card>
    );
}
