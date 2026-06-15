import AppLayout from '@/Layouts/AppLayout';
import BrowseFilterSelect from '@/Components/BrowseFilterSelect';
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

export default function BrowseIndex({
    shows,
    promotions,
    years,
    filters,
}: {
    shows: ShowCardData[];
    promotions: Promotion[];
    years: number[];
    filters: { promotion: string; year: number | null; show_type: string; watchable: boolean };
}) {
    const buildBrowseQuery = (overrides: Partial<Record<string, string>> = {}) => {
        const merged = {
            promotion: filters.promotion,
            show_type: filters.show_type,
            year: filters.year?.toString() ?? '',
            watchable: filters.watchable ? '1' : '',
            ...overrides,
        };

        return {
            promotion: merged.promotion,
            show_type: merged.show_type,
            year: merged.year || undefined,
            watchable: merged.watchable === '1' ? '1' : undefined,
        };
    };

    const updateFilter = (key: string, value: string) => {
        router.get(route('browse'), buildBrowseQuery({ [key]: value }), { preserveState: true });
    };

    const promotionOptions = useMemo(
        () =>
            promotions.map((promotion) => ({
                label: `Promotion: ${promotion.name}`,
                value: promotion.slug,
            })),
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
                Filter WCW PPVs by promotion, year, and show type
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
                </Flex>
            </Box>

            {shows.length === 0 ? (
                <Text color="mea.muted">No published shows match these filters yet.</Text>
            ) : (
                <SimpleGrid columns={{ base: 1, sm: 2, lg: 3 }} gap={5}>
                    {shows.map((show) => (
                        <ShowCard key={show.id} show={show} />
                    ))}
                </SimpleGrid>
            )}
        </AppLayout>
    );
}
