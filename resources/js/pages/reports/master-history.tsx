import { Head, Link } from '@inertiajs/react';
import { History } from 'lucide-react';
import { useMemo } from 'react';
import { ContentCard } from '@/components/content-card';
import { formatDate } from '@/components/detail-grid';
import { ReportActions } from '@/components/reports/report-actions';
import { TableColumnHeader } from '@/components/table/table-column-header';
import { TableDateRangeFilter } from '@/components/table/table-date-range-filter';
import { TableFacetedFilter } from '@/components/table/table-faceted-filter';
import { TablePagination } from '@/components/table/table-pagination';
import {
    TableDesktopFrame,
    TableDesktopToolbar,
    TableQueryState,
} from '@/components/table/table-shell';
import { TableSkeleton } from '@/components/table/table-skeleton';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { UserAvatar, UserAvatarName } from '@/components/user-avatar-name';
import { useTableParams } from '@/hooks/use-table-params';
import { exportQuery, reportDefaults } from '@/lib/reports';
import { paramsFromServer } from '@/lib/table';
import type { ServerFilters } from '@/lib/table';
import { masterHistory } from '@/routes/reports';
import { masterHistory as exportMasterHistory } from '@/routes/reports/export';
import { show as showPublisher } from '@/routes/publishers';
import { show as showWriter } from '@/routes/writers';
import type {
    EnumOption,
    MasterHistoryEntry,
    PaginatedResource,
    ReportUserOption,
} from '@/types';

type Props = {
    rows: PaginatedResource<MasterHistoryEntry>;
    filters: ServerFilters;
    subjectTypes: EnumOption[];
    changeTypes: EnumOption[];
    users: ReportUserOption[];
};

const DEFAULTS = reportDefaults('recorded_at.desc');
const REFRESHED_PROPS = ['rows', 'filters'];

export default function MasterHistoryReport({
    rows,
    filters,
    subjectTypes,
    changeTypes,
    users,
}: Props) {
    const {
        params,
        setParams,
        isFiltered,
        isLoading,
        hasError,
        resetFilters,
        retry,
    } = useTableParams({
        url: masterHistory().url,
        initialParams: paramsFromServer(filters, rows.meta.current_page),
        defaultParams: DEFAULTS,
        only: REFRESHED_PROPS,
    });

    const subjectTypeOptions = useMemo(
        () =>
            subjectTypes.map((item) => ({ id: item.value, name: item.label })),
        [subjectTypes],
    );
    const changeTypeOptions = useMemo(
        () => changeTypes.map((item) => ({ id: item.value, name: item.label })),
        [changeTypes],
    );
    const userOptions = useMemo(
        () => users.map((item) => ({ id: String(item.id), name: item.name })),
        [users],
    );
    const avatarUrlsByUserId = useMemo(
        () => new Map(users.map((item) => [String(item.id), item.avatar_url])),
        [users],
    );

    return (
        <>
            <Head title="Master History" />

            <ContentCard
                title="Master History"
                description="Every recorded change to a writer or publisher, newest first."
            >
                <div className="space-y-4">
                    <div>
                        <TableDesktopToolbar
                            params={params}
                            setParams={setParams}
                            isFiltered={isFiltered}
                            onResetFilters={resetFilters}
                            filters={
                                <>
                                    <TableFacetedFilter
                                        title="Record type"
                                        column="types"
                                        params={params}
                                        setParams={setParams}
                                        options={subjectTypeOptions}
                                    />
                                    <TableFacetedFilter
                                        title="Change type"
                                        column="change_types"
                                        params={params}
                                        setParams={setParams}
                                        options={changeTypeOptions}
                                    />
                                    <TableFacetedFilter
                                        title="User"
                                        column="users"
                                        params={params}
                                        setParams={setParams}
                                        options={userOptions}
                                        renderOptionLeading={(option) => (
                                            <UserAvatar
                                                name={option.name}
                                                avatarUrl={
                                                    avatarUrlsByUserId.get(
                                                        String(option.id),
                                                    ) ?? null
                                                }
                                            />
                                        )}
                                    />
                                    <TableDateRangeFilter
                                        params={params}
                                        setParams={setParams}
                                        startKey="recorded_from"
                                        endKey="recorded_to"
                                        placeholder="Recorded date range"
                                    />
                                </>
                            }
                            actions={
                                <ReportActions
                                    exportUrl={
                                        exportMasterHistory({
                                            query: exportQuery(
                                                params,
                                                DEFAULTS,
                                            ),
                                        }).url
                                    }
                                />
                            }
                        />
                    </div>

                    <TableQueryState
                        isLoading={isLoading}
                        hasError={hasError}
                        data={rows}
                        resourceName="the history"
                        onRetry={retry}
                        skeleton={<TableSkeleton columnCount={6} />}
                    >
                        {(data) =>
                            data.data.length === 0 ? (
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <History />
                                        </EmptyMedia>
                                        <EmptyTitle>
                                            No history found
                                        </EmptyTitle>
                                        <EmptyDescription>
                                            No change matches the filters.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            ) : (
                                <>
                                    <TableDesktopFrame>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>
                                                    <TableColumnHeader
                                                        title="Recorded"
                                                        column="recorded_at"
                                                        params={params}
                                                        setParams={setParams}
                                                    />
                                                </TableHead>
                                                <TableHead>
                                                    <TableColumnHeader
                                                        title="Record"
                                                        column="subject_name"
                                                        params={params}
                                                        setParams={setParams}
                                                    />
                                                </TableHead>
                                                <TableHead>Change</TableHead>
                                                <TableHead>Summary</TableHead>
                                                <TableHead>By</TableHead>
                                                <TableHead>Remarks</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {data.data.map((entry) => (
                                                <TableRow key={entry.id}>
                                                    <TableCell className="whitespace-nowrap">
                                                        {formatDate(
                                                            entry.recorded_at,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="max-w-[14rem]">
                                                        <span className="block text-xs text-muted-foreground">
                                                            {entry.subject
                                                                .type ===
                                                            'writer'
                                                                ? 'Writer'
                                                                : 'Publisher'}
                                                            {entry.subject
                                                                .code && (
                                                                <span className="ml-1 font-mono">
                                                                    {
                                                                        entry
                                                                            .subject
                                                                            .code
                                                                    }
                                                                </span>
                                                            )}
                                                        </span>
                                                        {entry.subject
                                                            .is_deleted ? (
                                                            <span className="block truncate font-semibold">
                                                                {
                                                                    entry
                                                                        .subject
                                                                        .name
                                                                }
                                                            </span>
                                                        ) : (
                                                            <Link
                                                                href={(entry
                                                                    .subject
                                                                    .type ===
                                                                    'writer'
                                                                    ? showWriter
                                                                    : showPublisher)(
                                                                    entry
                                                                        .subject
                                                                        .id,
                                                                )}
                                                                className="link-hover block truncate font-semibold after:h-[2px]"
                                                            >
                                                                {
                                                                    entry
                                                                        .subject
                                                                        .name
                                                                }
                                                            </Link>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="secondary">
                                                            {
                                                                entry.change_type_label
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="min-w-[16rem] whitespace-normal">
                                                        {entry.summary}
                                                    </TableCell>
                                                    <TableCell className="whitespace-nowrap">
                                                        {entry.user_name ? (
                                                            <UserAvatarName
                                                                name={
                                                                    entry.user_name
                                                                }
                                                                avatarUrl={
                                                                    entry.user_avatar_url
                                                                }
                                                            />
                                                        ) : entry.csv_import_id ? (
                                                            'Import'
                                                        ) : (
                                                            'System'
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="min-w-[10rem] whitespace-normal text-muted-foreground">
                                                        {entry.remarks || 'N/A'}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </TableDesktopFrame>
                                    <div>
                                        <TablePagination
                                            total={data.meta.total}
                                            params={params}
                                            setParams={setParams}
                                        />
                                    </div>
                                </>
                            )
                        }
                    </TableQueryState>
                </div>
            </ContentCard>
        </>
    );
}

MasterHistoryReport.layout = {
    breadcrumbs: [
        { title: 'Reports', href: masterHistory() },
        { title: 'Master History', href: masterHistory() },
    ],
};
