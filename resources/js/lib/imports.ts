import { index as publishersIndex } from '@/routes/publishers';
import { index as worksIndex } from '@/routes/works';
import { index as writersIndex } from '@/routes/writers';
import type { ImportType } from '@/types';

/** The list page that shows the records an import creates. */
export const importListRoutes: Record<
    ImportType,
    { title: string; href: ReturnType<typeof publishersIndex> }
> = {
    publishers: { title: 'Publishers', href: publishersIndex() },
    writers: { title: 'Writers', href: writersIndex() },
    works: { title: 'Works', href: worksIndex() },
};

/** The order the files have to be imported in, because later files refer to earlier records. */
export const importOrder: ImportType[] = ['publishers', 'writers', 'works'];

/** The import types that can be used today. */
export const availableImports: ImportType[] = [
    'publishers',
    'writers',
    'works',
];
