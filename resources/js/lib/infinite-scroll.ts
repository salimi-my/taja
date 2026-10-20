import type { UIEvent } from 'react';

interface InfiniteScrollParams {
    event: UIEvent<HTMLElement>;
    hasMore: boolean;
    isLoadingMore: boolean;
    onLoadMore: () => void;
    threshold?: number;
}

export function handleInfiniteScroll({
    event,
    hasMore,
    isLoadingMore,
    onLoadMore,
    threshold = 32,
}: InfiniteScrollParams) {
    if (!hasMore || isLoadingMore) {
        return;
    }

    const element = event.currentTarget;
    const distanceToBottom =
        element.scrollHeight - element.scrollTop - element.clientHeight;

    if (distanceToBottom <= threshold) {
        onLoadMore();
    }
}
