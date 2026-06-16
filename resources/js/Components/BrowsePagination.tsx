import { Button, Flex, Text } from '@chakra-ui/react';
import { router } from '@inertiajs/react';

export interface PaginatedShowsMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface PaginatedShowsLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginatedShows<T> {
    data: T[];
    links: PaginatedShowsLinks;
    meta: PaginatedShowsMeta;
}

interface BrowsePaginationProps {
    links: PaginatedShowsLinks;
    meta: PaginatedShowsMeta;
}

export default function BrowsePagination({ links, meta }: BrowsePaginationProps) {
    if (meta.last_page <= 1) {
        return null;
    }

    const goToUrl = (url: string | null) => {
        if (url === null) {
            return;
        }

        router.get(url, {}, { preserveState: true });
    };

    return (
        <Flex
            as="nav"
            aria-label="Browse pagination"
            direction={{ base: 'column', sm: 'row' }}
            align={{ base: 'stretch', sm: 'center' }}
            justify="space-between"
            gap={4}
            mt={8}
        >
            {meta.from !== null && meta.to !== null ? (
                <Text color="mea.muted" fontSize="sm" textAlign={{ base: 'center', sm: 'left' }}>
                    Showing {meta.from}–{meta.to} of {meta.total}
                </Text>
            ) : null}

            <Flex gap={3} justify={{ base: 'center', sm: 'flex-end' }}>
                <Button
                    size="sm"
                    variant="outline"
                    borderColor="mea.border"
                    color="white"
                    disabled={links.prev === null}
                    onClick={() => goToUrl(links.prev)}
                >
                    Previous
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    borderColor="mea.border"
                    color="white"
                    disabled={links.next === null}
                    onClick={() => goToUrl(links.next)}
                >
                    Next
                </Button>
            </Flex>
        </Flex>
    );
}
