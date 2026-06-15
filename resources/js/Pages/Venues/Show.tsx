import AppLayout from '@/Layouts/AppLayout';
import ShowCard, { ShowCardData } from '@/Components/ShowCard';
import WikipediaSourceBadge from '@/Components/WikipediaSourceBadge';
import { Box, Flex, Heading, SimpleGrid, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';

interface VenueAlias {
    name: string;
}

interface VenueData {
    id: number;
    name: string;
    slug: string;
    city?: string | null;
    state_province?: string | null;
    country?: string | null;
    location?: string | null;
    capacity?: number | null;
    wikipedia_url?: string | null;
    aliases?: VenueAlias[];
}

function formatCapacity(capacity: number): string {
    return `${capacity.toLocaleString('en-US')} capacity`;
}

function formatMetaLine(venue: VenueData): string {
    const parts: string[] = [];

    if (venue.location) {
        parts.push(venue.location);
    }

    if (venue.capacity) {
        parts.push(formatCapacity(venue.capacity));
    }

    return parts.join(' | ');
}

export default function VenueShowPage({
    venue,
    shows,
}: {
    venue: VenueData;
    shows: ShowCardData[];
}) {
    const metaLine = formatMetaLine(venue);
    const aliases = venue.aliases ?? [];

    return (
        <AppLayout>
            <Head title={venue.name} />
            <Box
                as="article"
                bg="mea.surface"
                borderWidth="1px"
                borderColor="mea.border"
                borderRadius="xl"
                overflow="hidden"
            >
                <Box p={{ base: 6, md: 8 }}>
                    <Heading as="h1" size="2xl" mb={2} fontWeight="bold">
                        {venue.name}
                    </Heading>
                    {metaLine ? (
                        <Text color="mea.gold" fontSize="md" fontWeight="medium" mb={4}>
                            {metaLine}
                        </Text>
                    ) : null}

                    {aliases.length > 0 ? (
                        <Box mb={6}>
                            <Heading as="h2" size="sm" mb={2} color="mea.muted">
                                Former names
                            </Heading>
                            <Text color="mea.muted" fontSize="sm">
                                {aliases.map((alias) => alias.name).join(', ')}
                            </Text>
                        </Box>
                    ) : null}

                    {venue.wikipedia_url ? (
                        <Flex mb={8}>
                            <WikipediaSourceBadge url={venue.wikipedia_url} />
                        </Flex>
                    ) : null}

                    <Heading as="h2" size="md" mb={4}>
                        Shows at this venue
                        {shows.length > 0 ? ` (${shows.length})` : ''}
                    </Heading>

                    {shows.length === 0 ? (
                        <Text color="mea.muted">
                            No published shows are linked to this venue yet.
                        </Text>
                    ) : (
                        <SimpleGrid columns={{ base: 1, sm: 2, lg: 3 }} gap={5}>
                            {shows.map((show) => (
                                <ShowCard key={show.id} show={show} />
                            ))}
                        </SimpleGrid>
                    )}
                </Box>
            </Box>
        </AppLayout>
    );
}
