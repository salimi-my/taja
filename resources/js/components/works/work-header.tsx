import { CalendarDays, Clock, Languages, Mic2, Music } from 'lucide-react';
import { formatDate } from '@/components/detail-grid';
import { CodeChip, ProfileHeader } from '@/components/profile-header';
import {
    WorkConflictBadge,
    WorkRegistrationBadge,
} from '@/components/works/work-badges';
import type { WorkDetail } from '@/types';

/** The title, code and registration status of a work with its key facts at a glance. */
export function WorkHeader({ work }: { work: WorkDetail }) {
    const subtitle = [work.english_title, work.other_title]
        .filter((title) => title && title !== work.title)
        .join(' · ');

    return (
        <ProfileHeader
            name={work.title}
            icon={Music}
            subtitle={subtitle || undefined}
            meta={
                <>
                    <CodeChip>{work.tap_number}</CodeChip>
                    <WorkRegistrationBadge work={work} />
                    {work.conflict_status !== 'no_conflict' && (
                        <WorkConflictBadge work={work} />
                    )}
                    {work.iswc && (
                        <span className="font-mono">{work.iswc}</span>
                    )}
                </>
            }
            facts={[
                { icon: Mic2, label: 'Artist', value: work.artist_name },
                {
                    icon: Music,
                    label: 'Type / genre',
                    value: [work.work_type, work.genre]
                        .filter(Boolean)
                        .join(' · '),
                },
                { icon: Languages, label: 'Language', value: work.language },
                {
                    icon: work.duration ? Clock : CalendarDays,
                    label: work.duration ? 'Duration' : 'Release date',
                    value: work.duration ?? formatDate(work.release_date),
                },
            ]}
        />
    );
}
