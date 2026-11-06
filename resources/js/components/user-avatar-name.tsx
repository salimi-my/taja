import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type UserAvatarProps = {
    name: string;
    avatarUrl?: string | null;
    /** `compact` fits inside a line of small text. */
    size?: 'default' | 'compact';
};

/** A small round avatar that falls back to the user's initials. */
export function UserAvatar({
    name,
    avatarUrl,
    size = 'default',
}: UserAvatarProps) {
    const getInitials = useInitials();
    const isCompact = size === 'compact';

    return (
        <Avatar size="sm" className={cn(isCompact && 'size-3.5')}>
            <AvatarImage src={avatarUrl ?? ''} alt={name} loading="lazy" />
            <AvatarFallback
                className={cn(
                    'bg-zinc-200/50 dark:bg-zinc-700/60',
                    isCompact ? 'text-[7px]' : 'text-[10px]',
                )}
            >
                {getInitials(name)}
            </AvatarFallback>
        </Avatar>
    );
}

type UserAvatarNameProps = UserAvatarProps & {
    className?: string;
};

/** A small avatar followed by the user's name, for showing who did something. */
export function UserAvatarName({
    name,
    avatarUrl,
    size = 'default',
    className,
}: UserAvatarNameProps) {
    return (
        <span
            className={cn(
                'inline-flex min-w-0 items-center',
                size === 'compact' ? 'gap-1 align-middle' : 'gap-2',
                className,
            )}
        >
            <UserAvatar name={name} avatarUrl={avatarUrl} size={size} />
            <span className="truncate">{name}</span>
        </span>
    );
}
