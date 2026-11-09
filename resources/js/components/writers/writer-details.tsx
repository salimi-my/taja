import {
    Banknote,
    Fingerprint,
    Handshake,
    HeartHandshake,
    IdCard,
    Percent,
    StickyNote,
    Theater,
} from 'lucide-react';
import { ContentCard } from '@/components/content-card';
import { DealSplit } from '@/components/deal-split';
import {
    DetailGrid,
    DetailItem,
    formatDate,
    formatYesNo,
} from '@/components/detail-grid';
import { PhoneDisplay } from '@/components/phone-display';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { WorkCatalogue } from '@/components/works/work-catalogue';
import { WriterHeader } from '@/components/writers/writer-header';
import { writerCatalogue as exportWriterCatalogue } from '@/routes/reports/export';
import { show as showWriter } from '@/routes/writers';
import type { WorkParty, WriterDetail } from '@/types';

type WriterDetailsProps = {
    writer: WriterDetail;
    rightsTypeLabels: Record<string, string>;
    /** The works the writer is a party of. */
    catalogue: WorkParty[];
    includeEnded: boolean;
};

export function WriterDetails({
    writer,
    rightsTypeLabels,
    catalogue,
    includeEnded,
}: WriterDetailsProps) {
    return (
        <div className="flex flex-col gap-4">
            <WriterHeader writer={writer} />

            <Tabs defaultValue="overview">
                <TabsList className="print:hidden">
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="catalogue">Catalogue</TabsTrigger>
                </TabsList>

                <TabsContent
                    value="overview"
                    className="grid items-start gap-4 lg:grid-cols-3"
                >
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        <ContentCard
                            icon={IdCard}
                            title="Identity"
                            description="Who the writer is and how to reach them."
                        >
                            <DetailGrid>
                                <DetailItem label="NRIC / passport">
                                    {writer.nric}
                                </DetailItem>
                                <DetailItem label="Date of birth">
                                    {formatDate(writer.date_of_birth)}
                                </DetailItem>
                                <DetailItem label="Date of death">
                                    {formatDate(writer.date_of_death)}
                                </DetailItem>
                                <DetailItem label="Nationality">
                                    {writer.nationality}
                                </DetailItem>
                                <DetailItem label="TIN number">
                                    {writer.tin_number}
                                </DetailItem>
                                <DetailItem label="Phone">
                                    <PhoneDisplay phone={writer.phone} />
                                </DetailItem>
                                <DetailItem label="Address">
                                    {writer.address}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>

                        <ContentCard
                            icon={Handshake}
                            title="Agreement and rights"
                            description="What the writer controls and for how long."
                        >
                            <div className="flex flex-col gap-5">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <DetailItem label="Rights types controlled">
                                        {writer.rights_types_controlled.length >
                                        0 ? (
                                            <div className="flex flex-wrap gap-1">
                                                {writer.rights_types_controlled.map(
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
                                        ) : null}
                                    </DetailItem>
                                    <DetailItem label="Controlled territories">
                                        {writer.controlled_territories.length >
                                        0 ? (
                                            <div className="flex flex-wrap gap-1">
                                                {writer.controlled_territories.map(
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
                                        ) : null}
                                    </DetailItem>
                                </div>
                                <DetailGrid>
                                    <DetailItem label="Agreement start date">
                                        {formatDate(
                                            writer.agreement_start_date,
                                        )}
                                    </DetailItem>
                                    <DetailItem label="Agreement end date">
                                        {formatDate(writer.agreement_end_date)}
                                    </DetailItem>
                                    <DetailItem label="Post-term">
                                        {writer.post_term}
                                    </DetailItem>
                                    <DetailItem label="Auto renewal">
                                        {formatYesNo(writer.auto_renewal)}
                                    </DetailItem>
                                    <DetailItem label="Pre-term">
                                        {formatYesNo(writer.pre_term)}
                                    </DetailItem>
                                </DetailGrid>
                            </div>
                        </ContentCard>

                        <ContentCard
                            icon={Fingerprint}
                            title="Society and IPI"
                            description="Collecting society membership and identifiers."
                        >
                            <DetailGrid>
                                <DetailItem label="CMO / society">
                                    {writer.cmo_society}
                                </DetailItem>
                                <DetailItem label="IPI base number">
                                    {writer.ipi_base_number}
                                </DetailItem>
                                <DetailItem label="IPI name number">
                                    {writer.ipi_name_number}
                                </DetailItem>
                            </DetailGrid>
                        </ContentCard>

                        <ContentCard
                            icon={StickyNote}
                            title="Other"
                            description="Where the record came from and any notes."
                        >
                            <DetailGrid>
                                <DetailItem label="Source system">
                                    {writer.source_system}
                                </DetailItem>
                                <DetailItem label="Created">
                                    {formatDate(writer.created_at)}
                                </DetailItem>
                                <DetailItem label="Last updated">
                                    {formatDate(writer.updated_at)}
                                </DetailItem>
                                <DetailItem label="Notes">
                                    {writer.notes}
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
                                taja={writer.taja_percentage_deal}
                                other={writer.writer_percentage_deal}
                                otherLabel="Writer"
                            />
                        </ContentCard>

                        <ContentCard
                            icon={Theater}
                            title="Pseudonyms"
                            description="Stage names the writer is also known by."
                        >
                            {writer.aliases.length === 0 ? (
                                <Empty className="border p-6">
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <Theater />
                                        </EmptyMedia>
                                        <EmptyTitle>No pseudonyms</EmptyTitle>
                                        <EmptyDescription>
                                            Stage names the writer is known by
                                            appear here.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            ) : (
                                <dl className="flex flex-col gap-4">
                                    {writer.aliases.map((alias) => (
                                        <DetailItem
                                            key={alias.id}
                                            label={`Pseudonym ${alias.position}`}
                                        >
                                            {alias.name}
                                            {alias.ipi_base_number && (
                                                <span className="block font-mono text-xs text-muted-foreground">
                                                    {alias.ipi_base_number}
                                                </span>
                                            )}
                                        </DetailItem>
                                    ))}
                                </dl>
                            )}
                        </ContentCard>

                        <ContentCard
                            icon={Banknote}
                            title="Banking"
                            description="Where payments are sent."
                        >
                            <dl className="flex flex-col gap-4">
                                <DetailItem label="Bank name">
                                    {writer.bank_name}
                                </DetailItem>
                                <DetailItem label="Account number">
                                    {writer.bank_account_number}
                                </DetailItem>
                                <DetailItem label="Swift code">
                                    {writer.swift_code}
                                </DetailItem>
                                <DetailItem label="Bank address">
                                    {writer.bank_address}
                                </DetailItem>
                            </dl>
                        </ContentCard>

                        <ContentCard
                            icon={HeartHandshake}
                            title="Heir"
                            description="Who the rights pass to."
                        >
                            <dl className="flex flex-col gap-4">
                                <DetailItem label="Name">
                                    {writer.heir_name}
                                </DetailItem>
                                <DetailItem label="NRIC / passport">
                                    {writer.heir_nric}
                                </DetailItem>
                                <DetailItem label="Relationship">
                                    {writer.heir_relationship}
                                </DetailItem>
                                <DetailItem label="Mobile number">
                                    <PhoneDisplay
                                        phone={writer.heir_mobile_number}
                                    />
                                </DetailItem>
                                <DetailItem label="Email">
                                    {writer.heir_email}
                                </DetailItem>
                            </dl>
                        </ContentCard>
                    </div>
                </TabsContent>

                <TabsContent value="catalogue">
                    <ContentCard
                        title="Catalogue"
                        description="The works this writer is a party of today."
                    >
                        <WorkCatalogue
                            rows={catalogue}
                            includeEnded={includeEnded}
                            url={showWriter(writer.id).url}
                            only={['catalogue', 'includeEnded']}
                            exportUrl={
                                exportWriterCatalogue({
                                    query: {
                                        writer_id: writer.id,
                                        ...(includeEnded
                                            ? { include_ended: 1 }
                                            : {}),
                                    },
                                }).url
                            }
                        />
                    </ContentCard>
                </TabsContent>
            </Tabs>
        </div>
    );
}
