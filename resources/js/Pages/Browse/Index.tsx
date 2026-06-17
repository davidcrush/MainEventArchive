import AppLayout from '@/Layouts/AppLayout';
import BrowseFilterSelect from '@/Components/BrowseFilterSelect';
import BrowsePagination, { PaginatedShows } from '@/Components/BrowsePagination';
import ShowCard, { ShowCardData } from '@/Components/ShowCard';
import { router } from '@inertiajs/react';
import { Box, Flex, Heading, SimpleGrid, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';

interface Promotion {
    id: number;
    name: string;
    slug: string;
}

const showTypeOptions = [
    { label: 'Show Type: PPV', value: 'ppv' },
    { label: 'Show Type: TV', value: 'tv' },
    { label: 'Show Type: Special', value: 'special' },
];

const watchableOptions = [
    { label: 'Availability: All shows', value: '' },
    { label: 'Availability: Watchable only', value: '1' },
];

const platformOptions = [
    { label: 'Platform: All', value: '' },
    { label: 'Platform: YouTube', value: 'youtube' },
    { label: 'Platform: Netflix', value: 'netflix' },
];

export default function BrowseIndex({
    shows,
    promotions,
    years,
    filters,
}: {
    shows: PaginatedShows<ShowCardData>;
    promotions: Promotion[];
    years: number[];
    filters: { promotion: string; year: number | null; show_type: string; watchable: boolean; platform: string | null };
}) {
    const buildBrowseQuery = (overrides: Partial<Record<string, string>> = {}) => {
        const merged = {
            promotion: filters.promotion,
            show_type: filters.show_type,
            year: filters.year?.toString() ?? '',
            watchable: filters.watchable ? '1' : '',
            platform: filters.platform ?? '',
            page: shows.meta.current_page > 1 ? String(shows.meta.current_page) : '',
            ...overrides,
        };

        return {
            promotion: merged.promotion,
            show_type: merged.show_type,
            year: merged.year || undefined,
            watchable: merged.watchable === '1' ? '1' : undefined,
            platform: merged.platform || undefined,
            page: merged.page && Number(merged.page) > 1 ? merged.page : undefined,
        };
    };

    const updateFilter = (key: string, value: string) => {
        router.get(route('browse'), buildBrowseQuery({ [key]: value, page: '' }), { preserveState: true });
    };

    const promotionOptions = useMemo(
        () => [
            { label: 'Promotion: All', value: 'all' },
            ...promotions.map((promotion) => ({
                label: `Promotion: ${promotion.name}`,
                value: promotion.slug,
            })),
        ],
        [promotions],
    );

    const yearOptions = useMemo(
        () => [
            { label: 'Year', value: '' },
            ...years.map((year) => ({
                label: String(year),
                value: String(year),
            })),
        ],
        [years],
    );

    return (
        <AppLayout>
            <Head title="Browse" />
            <Heading size="xl" mb={2} fontWeight="bold">
                Browse Catalog
            </Heading>
            <Text color="mea.muted" mb={8} fontSize="md">
                Filter shows by promotion, year, type, availability, and streaming platform
            </Text>

            <Box
                as="nav"
                aria-label="Catalog filters"
                mb={8}
                bg="mea.surface"
                borderWidth="1px"
                borderColor="mea.border"
                borderRadius="xl"
                p={4}
            >
                <Flex gap={3} flexWrap="wrap">
                    <BrowseFilterSelect
                        label="Promotion"
                        value={filters.promotion}
                        options={promotionOptions}
                        onChange={(value) => updateFilter('promotion', value)}
                    />
                    <BrowseFilterSelect
                        label="Year"
                        value={filters.year?.toString() ?? ''}
                        options={yearOptions}
                        onChange={(value) => updateFilter('year', value)}
                    />
                    <BrowseFilterSelect
                        label="Show type"
                        value={filters.show_type}
                        options={showTypeOptions}
                        onChange={(value) => updateFilter('show_type', value)}
                    />
                    <BrowseFilterSelect
                        label="Availability"
                        value={filters.watchable ? '1' : ''}
                        options={watchableOptions}
                        onChange={(value) => updateFilter('watchable', value)}
                    />
                    <BrowseFilterSelect
                        label="Platform"
                        value={filters.platform ?? ''}
                        options={platformOptions}
                        onChange={(value) => updateFilter('platform', value)}
                    />
                </Flex>
            </Box>

            {shows.data.length === 0 ? (
                <Text color="mea.muted">No published shows match these filters yet.</Text>
            ) : (
                <>
                    <SimpleGrid columns={{ base: 1, lg: 2 }} gap={6}>
                        {shows.data.map((show) => (
                            <ShowCard key={show.id} show={show} />
                        ))}
                    </SimpleGrid>
                    <BrowsePagination links={shows.links} meta={shows.meta} />
                </>
            )}
        </AppLayout>
    );
}
