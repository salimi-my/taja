import { PublisherStatusBadge } from '@/components/publishers/publisher-badges';
import { ContentCard } from '@/components/content-card';
import {
    DetailGrid,
    DetailItem,
    formatDate,
    formatPercent,
    formatYesNo,
} from '@/components/detail-grid';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { WorkCatalogue } from '@/components/works/work-catalogue';
import { formatTerritories } from '@/lib/publishers';
import { show } from '@/routes/publishers';
import type { PublisherDetail, WorkParty } from '@/types';

type PublisherDetailsProps = {
    publisher: PublisherDetail;
    rightsTypeLabels: Record<string, string>;
    /** The works the publisher is a party of. */
    catalogue: WorkParty[];
    includeEnded: boolean;
};

export function PublisherDetails({
    publisher,
    rightsTypeLabels,
    catalogue,
    includeEnded,
}: PublisherDetailsProps) {
    return (
        <Tabs defaultValue="overview">
            <TabsList>
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="works">Works</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" className="space-y-4">
                <ContentCard
                    title="Registration"
                    description="Who the publisher is and how to reach it."
                >
                    <DetailGrid>
                        <DetailItem label="Publisher code">
                            <span className="font-mono">
                                {publisher.publisher_code}
                            </span>
                        </DetailItem>
                        <DetailItem label="Name">{publisher.name}</DetailItem>
                        <DetailItem label="Status">
                            <PublisherStatusBadge publisher={publisher} />
                        </DetailItem>
                        <DetailItem label="Company registration number">
                            {publisher.company_registration_number}
                        </DetailItem>
                        <DetailItem label="Official email">
                            {publisher.official_email}
                        </DetailItem>
                        <DetailItem label="Incorporation date">
                            {formatDate(publisher.incorporation_date)}
                        </DetailItem>
                        <DetailItem label="TIN number">
                            {publisher.tin_number}
                        </DetailItem>
                        <DetailItem label="Registration address">
                            {publisher.registration_address}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
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
                    title="Agreement and rights"
                    description="What the publisher granted and for how long."
                >
                    <DetailGrid>
                        <DetailItem label="Rights types granted">
                            <div className="flex flex-wrap gap-1">
                                {publisher.rights_types_granted.map((type) => (
                                    <Badge
                                        key={type}
                                        variant="secondary"
                                        className="font-normal"
                                    >
                                        {rightsTypeLabels[type] ?? type}
                                    </Badge>
                                ))}
                            </div>
                        </DetailItem>
                        <DetailItem label="Territories granted">
                            {formatTerritories(publisher.territories_granted)}
                        </DetailItem>
                        <DetailItem label="Agreement start">
                            {formatDate(publisher.agreement_start_date)}
                        </DetailItem>
                        <DetailItem label="Agreement end">
                            {formatDate(publisher.agreement_end_date)}
                        </DetailItem>
                        <DetailItem label="Auto renewal">
                            {formatYesNo(publisher.auto_renewal)}
                        </DetailItem>
                        <DetailItem label="Pre-term">
                            {formatYesNo(publisher.pre_term)}
                        </DetailItem>
                        <DetailItem label="Post-term">
                            {publisher.post_term}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
                    title="Deals"
                    description="How income is shared between TAJA and the publisher."
                >
                    <DetailGrid>
                        <DetailItem label="Taja percentage deal">
                            {formatPercent(publisher.taja_percentage_deal)}
                        </DetailItem>
                        <DetailItem label="Client percentage deal">
                            {formatPercent(publisher.client_percentage_deal)}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
                    title="Banking"
                    description="Where payments to the publisher are sent."
                >
                    <DetailGrid>
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
                    </DetailGrid>
                </ContentCard>

                <ContentCard
                    title="Person in charge"
                    description="The contact person at the publisher."
                >
                    <DetailGrid>
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
                            {publisher.person_in_charge_mobile_no}
                        </DetailItem>
                        <DetailItem label="Email">
                            {publisher.person_in_charge_email}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
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
                        <DetailItem label="Notes">{publisher.notes}</DetailItem>
                    </DetailGrid>
                </ContentCard>
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
        </Tabs>
    );
}
