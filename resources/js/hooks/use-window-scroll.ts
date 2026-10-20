import { useLayoutEffect, useState } from 'react';

export type WindowScrollPosition = {
    x: number | null;
    y: number | null;
};

/**
 * Track the window scroll position. Updates are throttled with
 * requestAnimationFrame so we never re-render more than once per frame.
 */
export function useWindowScroll(): WindowScrollPosition {
    const [position, setPosition] = useState<WindowScrollPosition>({
        x: null,
        y: null,
    });

    useLayoutEffect(() => {
        let frameId: number | null = null;

        const handleScroll = (): void => {
            if (frameId !== null) {
                return;
            }

            frameId = requestAnimationFrame(() => {
                setPosition({ x: window.scrollX, y: window.scrollY });
                frameId = null;
            });
        };

        handleScroll();
        window.addEventListener('scroll', handleScroll, { passive: true });

        return () => {
            window.removeEventListener('scroll', handleScroll);

            if (frameId !== null) {
                cancelAnimationFrame(frameId);
            }
        };
    }, []);

    return position;
}
