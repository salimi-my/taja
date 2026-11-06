import { ClipboardCheck, Disc3, Fingerprint, Landmark } from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import {
    DetailGrid,
    DetailItem,
    formatDate,
    formatYesNo,
} from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { WorkConflictBadge } from '@/components/works/work-badges';
import { WorkHeader } from '@/components/works/work-header';
import { WorkHistory } from '@/components/works/work-history';
import { WorkPartiesPanel } from '@/components/works/work-parties-panel';
import type {
    PaginatedResource,
    RightsHistoryEntry,
    WorkDetail,
    WorkParty,
    WorkPartyOptions,
} from '@/types';

type WorkDetailsProps = {
    work: WorkDetail;
    parties: WorkParty[];
    asOf: string | null;
    partyOptions: WorkPartyOptions | null;
    /** `undefined` while the deferred history is loading. */
    history: PaginatedResource<RightsHistoryEntry> | undefined;
};

export function WorkDetails({
    work,
    parties,
    asOf,
    partyOptions,
    history,
}: WorkDetailsProps) {
    return (
        <div className="flex flex-col gap-4">
            <WorkHeader work={work} />

            <Tabs defaultValue="overview">
                <TabsList>
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="parties">Parties</TabsTrigger>
                    <TabsTrigger value="history">History</TabsTrigger>
                </TabsList>

                <TabsContent
                    value="overview"
                    className="grid items-start gap-4 lg:grid-cols-2"
                >
                    <div className="flex flex-col gap-4">
                        <ContentCard
                            icon={Fingerprint}
                            title="Identity"
                            description="How the work is identified and titled."
                        >
                            <DetailGrid>
                                <DetailItem label="MACP Work ID">
                                    <span className="font-mono">
                                        {work.macp_work_id}
                                    </span>
                                </DetailItem>
                                <DetailItem label="ISWC">
                                    {work.iswc}
                                </DetailItem>
                                <DetailItem label="English title">
                                    {work.english_title}
                                </DetailItem>
                                <DetailItem label="Other title">
                                    {work.other_title}
                                </DetailItem>
                                <DetailItem label="Original work title">
                                    {work.original_work_title}
                                </DetailItem>
                                <DetailItem label="Sub title">
                                    {work.sub_title_work}
                                </DetailItem>
                                <DetailItem label="Society number">
                                    {work.society_no}
                                </DetailItem>
                                <DetailItem label="ISRCs">
                                    {work.isrcs.length > 0 ? (
                                        <div className="flex flex-wrap gap-1">
                                            {work.isrcs.map((isrc) => (
                                                <Badge
                                                    key={isrc}
                                                    variant="secondary"
                                                    className="font-mono"
                                                >
                                                    {isrc}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : null}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>
                        <ContentCard
                            icon={Disc3}
                            title="Details"
                            description="Descriptive information about the recording."
                        >
                            <DetailGrid>
                                <DetailItem label="Work type">
                                    {work.work_type}
                                </DetailItem>
                                <DetailItem label="Genre">
                                    {work.genre}
                                </DetailItem>
                                <DetailItem label="Language">
                                    {work.language}
                                </DetailItem>
                                <DetailItem label="Duration">
                                    {work.duration}
                                </DetailItem>
                                <DetailItem label="Artist">
                                    {work.artist_name}
                                </DetailItem>
                                <DetailItem label="Release date">
                                    {formatDate(work.release_date)}
                                </DetailItem>
                                <DetailItem label="Public domain">
                                    {formatYesNo(work.is_public_domain)}
                                </DetailItem>
                                <DetailItem label="Song link">
                                    {work.song_link ? (
                                        <a
                                            href={work.song_link}
                                            target="_blank"
                                            rel="noreferrer noopener"
                                            className="link-hover after:h-[2px]"
                                        >
                                            {work.song_link}
                                        </a>
                                    ) : null}
                                </DetailItem>
                                <DetailItem label="Remark">
                                    {work.remark}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>
                    </div>
                    <div className="flex flex-col gap-4">
                        <ContentCard
                            icon={ClipboardCheck}
                            title="Registration"
                            description="Administration period and the society registration."
                        >
                            <DetailGrid>
                                <DetailItem label="Registration date">
                                    {formatDate(work.registration_date)}
                                </DetailItem>
                                <DetailItem label="Administration start date">
                                    {formatDate(work.administration_start_date)}
                                </DetailItem>
                                <DetailItem label="Administration end date">
                                    {formatDate(work.administration_end_date)}
                                </DetailItem>
                                <DetailItem label="Conflict status">
                                    <WorkConflictBadge work={work} />
                                </DetailItem>
                                <DetailItem label="Conflict notes">
                                    {work.conflict_notes}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>
                        <ContentCard
                            icon={Landmark}
                            title="Society references"
                            description="Identifiers held by COMPASS and WAMI."
                        >
                            <DetailGrid>
                                <DetailItem label="COMPASS internal no.">
                                    {work.compass_work_internal_no}
                                </DetailItem>
                                <DetailItem label="COMPASS work type">
                                    {work.compass_work_type}
                                </DetailItem>
                                <DetailItem label="COMPASS start date">
                                    {formatDate(work.compass_start_date)}
                                </DetailItem>
                                <DetailItem label="WAMI internal no.">
                                    {work.wami_work_internal_no}
                                </DetailItem>
                                <DetailItem label="WAMI work type">
                                    {work.wami_work_type}
                                </DetailItem>
                                <DetailItem label="Created">
                                    {formatDate(work.created_at)}
                                </DetailItem>
                                <DetailItem label="Last updated">
                                    {formatDate(work.updated_at)}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>
                    </div>
                </TabsContent>

                <TabsContent value="parties">
                    <ContentCard
                        title="Parties"
                        description="The writers and publishers in effect, with their shares. Pick a date to see how the work was owned then."
                    >
                        <WorkPartiesPanel
                            workId={work.id}
                            parties={parties}
                            asOf={asOf}
                            partyOptions={partyOptions}
                        />
                    </ContentCard>
                </TabsContent>

                <TabsContent value="history">
                    <ContentCard
                        title="History"
                        description="Every change to the work and its parties, newest first. Entries are never edited or deleted."
                    >
                        <WorkHistory
                            workId={work.id}
                            asOf={asOf}
                            history={history}
                        />
                    </ContentCard>
                </TabsContent>
            </Tabs>
        </div>
    );
}
