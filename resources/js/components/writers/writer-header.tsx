import { Link } from '@inertiajs/react';
import { Building2, CalendarRange, Landmark, Percent } from 'lucide-react';
import { formatDealSplit } from '@/components/deal-split';
import { formatDate } from '@/components/detail-grid';
import { CodeChip, ProfileHeader } from '@/components/profile-header';
import { WriterStatusBadge } from '@/components/writers/writer-badges';
import { show as showPublisher } from '@/routes/publishers';
import type { WriterDetail } from '@/types';

/** The name, code and status of a writer with its key facts at a glance. */
export function WriterHeader({ writer }: { writer: WriterDetail }) {
    return (
        <ProfileHeader
            name={writer.name}
            subtitle={
                writer.aliases.length > 0
                    ? `Also known as ${writer.aliases.map((alias) => alias.name).join(', ')}`
                    : undefined
            }
            meta={
                <>
                    <CodeChip>{writer.writer_code}</CodeChip>
                    <WriterStatusBadge writer={writer} />
                    {writer.default_role_label && (
                        <span>{writer.default_role_label}</span>
                    )}
                    {writer.email && (
                        <a
                            href={`mailto:${writer.email}`}
                            className="hover:text-foreground hover:underline"
                        >
                            {writer.email}
                        </a>
                    )}
                </>
            }
            facts={[
                {
                    icon: Landmark,
                    label: 'CMO / society',
                    value: writer.cmo_society,
                },
                {
                    icon: Building2,
                    label: 'Publisher',
                    value: writer.publisher && (
                        <Link
                            href={showPublisher(writer.publisher.id)}
                            className="link-hover after:h-[2px]"
                        >
                            {writer.publisher.name}
                        </Link>
                    ),
                },
                {
                    icon: CalendarRange,
                    label: 'Agreement',
                    value: `${formatDate(writer.agreement_start_date)} → ${formatDate(writer.agreement_end_date) ?? 'open ended'}`,
                },
                {
                    icon: Percent,
                    label: 'Taja / Writer deal',
                    value: formatDealSplit(
                        writer.taja_percentage_deal,
                        writer.writer_percentage_deal,
                    ),
                },
            ]}
        />
    );
}
