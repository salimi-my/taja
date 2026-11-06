import { CalendarRange, Globe, Landmark, Percent } from 'lucide-react';
import { formatDealSplit } from '@/components/deal-split';
import { formatDate } from '@/components/detail-grid';
import { PublisherStatusBadge } from '@/components/publishers/publisher-badges';
import { CodeChip, ProfileHeader } from '@/components/profile-header';
import { formatTerritories } from '@/lib/publishers';
import type { PublisherDetail } from '@/types';

/** The name, code and status of a publisher with its key facts at a glance. */
export function PublisherHeader({ publisher }: { publisher: PublisherDetail }) {
    return (
        <ProfileHeader
            name={publisher.name}
            meta={
                <>
                    <CodeChip>{publisher.publisher_code}</CodeChip>
                    <PublisherStatusBadge publisher={publisher} />
                    {publisher.official_email && (
                        <a
                            href={`mailto:${publisher.official_email}`}
                            className="hover:text-foreground hover:underline"
                        >
                            {publisher.official_email}
                        </a>
                    )}
                </>
            }
            facts={[
                {
                    icon: Landmark,
                    label: 'Society / CMO',
                    value: publisher.society_cmo,
                },
                {
                    icon: Globe,
                    label: 'Territories',
                    value: formatTerritories(publisher.territories_granted),
                },
                {
                    icon: CalendarRange,
                    label: 'Agreement',
                    value: `${formatDate(publisher.agreement_start_date)} → ${formatDate(publisher.agreement_end_date) ?? 'open ended'}`,
                },
                {
                    icon: Percent,
                    label: 'Taja / Client deal',
                    value: formatDealSplit(
                        publisher.taja_percentage_deal,
                        publisher.client_percentage_deal,
                    ),
                },
            ]}
        />
    );
}
