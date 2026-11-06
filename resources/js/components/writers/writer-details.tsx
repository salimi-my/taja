import { Link } from '@inertiajs/react';
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
import { WriterStatusBadge } from '@/components/writers/writer-badges';
import { formatTerritories } from '@/lib/publishers';
import { show as showPublisher } from '@/routes/publishers';
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
        <Tabs defaultValue="overview">
            <TabsList className="print:hidden">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="catalogue">Catalogue</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" className="space-y-4">
                <ContentCard
                    title="Identity"
                    description="Who the writer is and how to reach them."
                >
                    <DetailGrid>
                        <DetailItem label="Writer code">
                            <span className="font-mono">
                                {writer.writer_code}
                            </span>
                        </DetailItem>
                        <DetailItem label="Name">{writer.name}</DetailItem>
                        <DetailItem label="Status">
                            <WriterStatusBadge writer={writer} />
                        </DetailItem>
                        <DetailItem label="Default role">
                            {writer.default_role_label}
                        </DetailItem>
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
                        <DetailItem label="Email">{writer.email}</DetailItem>
                        <DetailItem label="Phone">{writer.phone}</DetailItem>
                        <DetailItem label="Address">
                            {writer.address}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
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
                    title="Pseudonyms"
                    description="Stage names the writer is also known by."
                >
                    {writer.aliases.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No pseudonyms.
                        </p>
                    ) : (
                        <DetailGrid>
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
                        </DetailGrid>
                    )}
                </ContentCard>

                <ContentCard
                    title="Agreement and rights"
                    description="What the writer controls and for how long."
                >
                    <DetailGrid>
                        <DetailItem label="Publisher">
                            {writer.publisher && (
                                <Link
                                    href={showPublisher(writer.publisher.id)}
                                    className="link-hover after:h-[2px]"
                                >
                                    {writer.publisher.name} (
                                    {writer.publisher.publisher_code})
                                </Link>
                            )}
                        </DetailItem>
                        <DetailItem label="Rights types controlled">
                            {writer.rights_types_controlled.length > 0 ? (
                                <div className="flex flex-wrap gap-1">
                                    {writer.rights_types_controlled.map(
                                        (type) => (
                                            <Badge
                                                key={type}
                                                variant="secondary"
                                                className="font-normal"
                                            >
                                                {rightsTypeLabels[type] ?? type}
                                            </Badge>
                                        ),
                                    )}
                                </div>
                            ) : null}
                        </DetailItem>
                        <DetailItem label="Controlled territories">
                            {writer.controlled_territories.length > 0
                                ? formatTerritories(
                                      writer.controlled_territories,
                                  )
                                : null}
                        </DetailItem>
                        <DetailItem label="Agreement start date">
                            {formatDate(writer.agreement_start_date)}
                        </DetailItem>
                        <DetailItem label="Agreement end date">
                            {formatDate(writer.agreement_end_date)}
                        </DetailItem>
                        <DetailItem label="Auto renewal">
                            {formatYesNo(writer.auto_renewal)}
                        </DetailItem>
                        <DetailItem label="Pre-term">
                            {formatYesNo(writer.pre_term)}
                        </DetailItem>
                        <DetailItem label="Post-term">
                            {writer.post_term}
                        </DetailItem>
                        <DetailItem label="Taja percentage">
                            {formatPercent(writer.taja_percentage_deal)}
                        </DetailItem>
                        <DetailItem label="Writer percentage">
                            {formatPercent(writer.writer_percentage_deal)}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
                    title="Banking"
                    description="Where payments to the writer are sent."
                >
                    <DetailGrid>
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
                    </DetailGrid>
                </ContentCard>

                <ContentCard
                    title="Heir"
                    description="Who the rights pass to if the writer dies."
                >
                    <DetailGrid>
                        <DetailItem label="Name">{writer.heir_name}</DetailItem>
                        <DetailItem label="NRIC / passport">
                            {writer.heir_nric}
                        </DetailItem>
                        <DetailItem label="Relationship">
                            {writer.heir_relationship}
                        </DetailItem>
                        <DetailItem label="Mobile number">
                            {writer.heir_mobile_number}
                        </DetailItem>
                        <DetailItem label="Email">
                            {writer.heir_email}
                        </DetailItem>
                    </DetailGrid>
                </ContentCard>

                <ContentCard
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
                        <DetailItem label="Notes">{writer.notes}</DetailItem>
                    </DetailGrid>
                </ContentCard>
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
    );
}
