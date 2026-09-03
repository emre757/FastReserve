import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import type { PaginationData } from '@/types/pagination';

export function CompanyPagination({ links, meta }: PaginationData) {
    const metaLinks = meta.links.slice(1, -1); // remove prev and next from links

    return (
        <Pagination>
            <PaginationContent>
                {links.prev && (
                    <PaginationItem>
                        <PaginationPrevious href={links.prev} />
                    </PaginationItem>
                )}

                {metaLinks.map((link) => (
                    <PaginationItem key={link.page}>
                        <PaginationLink
                            href={link.url ?? undefined}
                            isActive={link.active}
                        >
                            {link.label}
                        </PaginationLink>
                    </PaginationItem>
                ))}

                <PaginationItem>
                    <PaginationEllipsis />
                </PaginationItem>

                {links.next && (
                    <PaginationItem>
                        <PaginationNext href={links.next} />
                    </PaginationItem>
                )}
            </PaginationContent>
        </Pagination>
    );
}
