import AppLayout from '@/Layouts/AppLayout';
import ShowCard, { ShowCardData } from '@/Components/ShowCard';
import { Box, Heading, SimpleGrid, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';

export default function WatchlistIndex({ shows }: { shows: ShowCardData[] }) {
    return (
        <AppLayout>
            <Head title="Watchlist" />
            <Heading size="xl" mb={2} fontWeight="bold">
                Your Watchlist
            </Heading>
            <Text color="mea.muted" mb={8} fontSize="md">
                Shows you want to watch when video is linked
            </Text>

            {shows.length === 0 ? (
                <Box
                    bg="mea.surface"
                    borderWidth="1px"
                    borderColor="mea.border"
                    borderRadius="lg"
                    p={8}
                    textAlign="center"
                >
                    <Text color="mea.muted">
                        Your watchlist is empty. Add shows while browsing to track what to watch when
                        video is linked.
                    </Text>
                </Box>
            ) : (
                <SimpleGrid columns={{ base: 1, lg: 2 }} gap={6}>
                    {shows.map((show) => (
                        <ShowCard key={show.id} show={show} />
                    ))}
                </SimpleGrid>
            )}
        </AppLayout>
    );
}
