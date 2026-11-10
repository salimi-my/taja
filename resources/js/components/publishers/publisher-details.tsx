import {
    Banknote,
    Fingerprint,
    Handshake,
    IdCard,
    Percent,
    StickyNote,
    UserRound,
} from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import { DealSplit } from '@/components/deal-split';
import {
    DetailGrid,
    DetailItem,
    formatDate,
    formatYesNo,
} from '@/components/detail-grid';
import { PublisherHeader } from '@/components/publishers/publisher-header';
import { PhoneDisplay } from '@/components/phone-display';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { MasterHistory } from '@/components/history/master-history';
import { WorkCatalogue } from '@/components/works/work-catalogue';
import { show } from '@/routes/publishers';
import type {
    MasterHistoryEntry,
    PaginatedResource,
    PublisherDetail,
    WorkParty,
} from '@/types';

type PublisherDetailsProps = {
    publisher: PublisherDetail;
    rightsTypeLabels: Record<string, string>;
    /** The works the publisher is a party of. */
    catalogue: WorkParty[];
    includeEnded: boolean;
    /** `undefined` while the deferred history is loading. */
    history: PaginatedResource<MasterHistoryEntry> | undefined;
};

export function PublisherDetails({
    publisher,
    rightsTypeLabels,
    catalogue,
    includeEnded,
    history,
}: PublisherDetailsProps) {
    return (
        <div className="flex flex-col gap-4">
            <PublisherHeader publisher={publisher} />

            <Tabs defaultValue="overview">
                <TabsList>
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="works">Works</TabsTrigger>
                    <TabsTrigger value="history">History</TabsTrigger>
                </TabsList>

                <TabsContent
                    value="overview"
                    className="grid items-start gap-4 lg:grid-cols-3"
                >
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        <ContentCard
                            icon={IdCard}
                            title="Registration"
                            description="Who the publisher is and how to reach it."
                        >
                            <DetailGrid>
                                <DetailItem label="Company registration number">
                                    {publisher.company_registration_number}
                                </DetailItem>
                                <DetailItem label="Incorporation date">
                                    {formatDate(publisher.incorporation_date)}
                                </DetailItem>
                                <DetailItem label="TIN number">
                                    {publisher.tin_number}
                                </DetailItem>
                                <DetailItem label="Official email">
                                    {publisher.official_email}
                                </DetailItem>
                                <DetailItem label="Registration address">
                                    {publisher.registration_address}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>

                        <ContentCard
                            icon={Fingerprint}
                            title="Society and IPI"
                            description="Collecting society membership and identifiers."
                        >
                            <DetailGrid>
                                <DetailItem label="Society / CMO">
                                    {publisher.society_cmo}
                                </DetailItem>
                                <DetailItem label="Society number">
                                    {publisher.society_number}
                                </DetailItem>
                                <DetailItem label="IPI base number">
                                    {publisher.ipi_base_number}
                                </DetailItem>
                                <DetailItem label="IPI name number">
                                    {publisher.ipi_name_number}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>

                        <ContentCard
                            icon={Handshake}
                            title="Agreement and rights"
                            description="What the publisher granted and for how long."
                        >
                            <div className="flex flex-col gap-5">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <DetailItem label="Rights types granted">
                                        <div className="flex flex-wrap gap-1">
                                            {publisher.rights_types_granted.map(
                                                (type) => (
                                                    <Badge
                                                        key={type}
                                                        variant="secondary"
                                                        className="font-normal"
                                                    >
                                                        {rightsTypeLabels[
                                                            type
                                                        ] ?? type}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </DetailItem>
                                    <DetailItem label="Territories granted">
                                        <div className="flex flex-wrap gap-1">
                                            {publisher.territories_granted.map(
                                                (territory) => (
                                                    <Badge
                                                        key={territory}
                                                        variant="outline"
                                                        className="font-normal"
                                                    >
                                                        {territory}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </DetailItem>
                                </div>
                                <DetailGrid>
                                    <DetailItem label="Agreement start">
                                        {formatDate(
                                            publisher.agreement_start_date,
                                        )}
                                    </DetailItem>
                                    <DetailItem label="Agreement end">
                                        {formatDate(
                                            publisher.agreement_end_date,
                                        )}
                                    </DetailItem>
                                    <DetailItem label="Post-term">
                                        {publisher.post_term}
                                    </DetailItem>
                                    <DetailItem label="Auto renewal">
                                        {formatYesNo(publisher.auto_renewal)}
                                    </DetailItem>
                                    <DetailItem label="Pre-term">
                                        {formatYesNo(publisher.pre_term)}
                                    </DetailItem>
                                </DetailGrid>
                            </div>
                        </ContentCard>

                        <ContentCard
                            icon={StickyNote}
                            title="Other"
                            description="Where the record came from and any notes."
                        >
                            <DetailGrid>
                                <DetailItem label="Source system">
                                    {publisher.source_system}
                                </DetailItem>
                                <DetailItem label="Created">
                                    {formatDate(publisher.created_at)}
                                </DetailItem>
                                <DetailItem label="Last updated">
                                    {formatDate(publisher.updated_at)}
                                </DetailItem>
                                <DetailItem label="Notes">
                                    {publisher.notes}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>
                    </div>

                    <div className="flex flex-col gap-4">
                        <ContentCard
                            icon={Percent}
                            title="Deal"
                            description="How income is shared."
                        >
                            <DealSplit
                                taja={publisher.taja_percentage_deal}
                                other={publisher.client_percentage_deal}
                                otherLabel="Client"
                            />
                        </ContentCard>

                        <ContentCard
                            icon={UserRound}
                            title="Person in charge"
                            description="The contact person at the publisher."
                        >
                            <dl className="flex flex-col gap-4">
                                <DetailItem label="Name">
                                    {publisher.person_in_charge_name}
                                </DetailItem>
                                <DetailItem label="Title">
                                    {publisher.person_in_charge_title}
                                </DetailItem>
                                <DetailItem label="NRIC / passport">
                                    {publisher.person_in_charge_nric}
                                </DetailItem>
                                <DetailItem label="Mobile number">
                                    <PhoneDisplay
                                        phone={
                                            publisher.person_in_charge_mobile_no
                                        }
                                    />
                                </DetailItem>
                                <DetailItem label="Email">
                                    {publisher.person_in_charge_email}
                                </DetailItem>
                            </dl>
                        </ContentCard>

                        <ContentCard
                            icon={Banknote}
                            title="Banking"
                            description="Where payments are sent."
                        >
                            <dl className="flex flex-col gap-4">
                                <DetailItem label="Account holder name">
                                    {publisher.bank_holder_name}
                                </DetailItem>
                                <DetailItem label="Bank name">
                                    {publisher.bank_name}
                                </DetailItem>
                                <DetailItem label="Account number">
                                    {publisher.client_account_number}
                                </DetailItem>
                                <DetailItem label="Swift code">
                                    {publisher.swift_code}
                                </DetailItem>
                                <DetailItem label="Bank address">
                                    {publisher.bank_address}
                                </DetailItem>
                            </dl>
                        </ContentCard>
                    </div>
                </TabsContent>

                <TabsContent value="works">
                    <ContentCard
                        title="Works"
                        description="The works this publisher is a party of today."
                    >
                        <WorkCatalogue
                            rows={catalogue}
                            includeEnded={includeEnded}
                            url={show(publisher.id).url}
                            only={['catalogue', 'includeEnded']}
                        />
                    </ContentCard>
                </TabsContent>

                <TabsContent value="history">
                    <ContentCard
                        title="History"
                        description="Every change to the publisher, newest first. Entries are never edited or deleted."
                    >
                        <MasterHistory
                            url={show(publisher.id).url}
                            subjectLabel="publisher"
                            history={history}
                        />
                    </ContentCard>
                </TabsContent>
            </Tabs>
        </div>
    );
}
