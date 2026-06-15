import AppLayout from '@/Layouts/AppLayout';
import ShowCard, { ShowCardData } from '@/Components/ShowCard';
import { router } from '@inertiajs/react';
import { Box, Heading, Input, SimpleGrid, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function SearchIndex({
    query,
    shows,
}: {
    query: string;
    shows: ShowCardData[];
}) {
    const submit = (e: FormEvent) => {
        e.preventDefault();
        const form = e.target as HTMLFormElement;
        const q = (form.elements.namedItem('q') as HTMLInputElement).value;
        router.get(route('search'), { q });
    };

    return (
        <AppLayout>
            <Head title="Search" />
            <Heading size="xl" mb={2} fontWeight="bold">
                Search
            </Heading>
            <Text color="mea.muted" mb={8} fontSize="md">
                Find shows and wrestlers across the catalog
            </Text>

            <Box as="form" onSubmit={submit} mb={8} maxW="lg">
                <Input
                    name="q"
                    defaultValue={query}
                    placeholder="Search shows or wrestlers on the card..."
                    bg="mea.surface"
                    borderColor="mea.border"
                    borderRadius="full"
                    px={5}
                    py={6}
                    _placeholder={{ color: 'mea.muted' }}
                    _focus={{ borderColor: 'mea.gold', boxShadow: '0 0 0 1px var(--chakra-colors-mea-gold)' }}
                />
            </Box>

            {query && shows.length === 0 ? (
                <Text color="mea.muted">No results for &ldquo;{query}&rdquo;</Text>
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
