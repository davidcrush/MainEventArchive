import AppLayout from '@/Layouts/AppLayout';
import { Box, Heading, Link, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';

export default function Attribution() {
    return (
        <AppLayout>
            <Head title="Attribution" />
            <Heading size="lg" mb={6}>
                Data Attribution
            </Heading>
            <Box display="flex" flexDirection="column" gap={4} color="mea.muted">
                <Text>
                    Main Event Archive seeds catalog metadata from legitimate open data sources.
                    We add curation, spoiler-safe presentation, community ratings, and watchlists.
                </Text>
                <Box>
                    <Heading size="sm" color="white" mb={2}>
                        Wikidata
                    </Heading>
                    <Text>
                        Structured event data is sourced from{' '}
                        <Link href="https://www.wikidata.org" color="mea.gold" target="_blank">
                            Wikidata
                        </Link>{' '}
                        (CC0). Individual show pages link to source entities where applicable.
                    </Text>
                </Box>
                <Box>
                    <Heading size="sm" color="white" mb={2}>
                        Wikipedia
                    </Heading>
                    <Text>
                        Match card enrichment may adapt content from Wikipedia via the MediaWiki API
                        (CC BY-SA 4.0). Adapted content is attributed to Wikipedia contributors.
                        Show pages link to source articles where applicable; Wikipedia and Wikidata
                        logos on those links are official Wikimedia marks used only as outbound
                        hyperlinks per the Wikimedia trademark policy.
                    </Text>
                </Box>
                <Box>
                    <Heading size="sm" color="white" mb={2}>
                        Promotion logos
                    </Heading>
                    <Text>
                        Promotion logos on the Promotions page are sourced from{' '}
                        <Link href="https://commons.wikimedia.org" color="mea.gold" target="_blank">
                            Wikimedia Commons
                        </Link>{' '}
                        where tagged public domain. Trademarks may still apply; use is descriptive
                        only. See our documentation for per-file sources.
                    </Text>
                </Box>
                <Box>
                    <Heading size="sm" color="white" mb={2}>
                        Third-party ratings
                    </Heading>
                    <Text>
                        We do not display or cache ratings from Cagematch or other proprietary
                        databases. Outbound link badges are staff-curated references only.
                    </Text>
                </Box>
            </Box>
        </AppLayout>
    );
}
