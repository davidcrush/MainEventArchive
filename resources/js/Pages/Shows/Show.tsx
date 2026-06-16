import AppLayout from '@/Layouts/AppLayout';
import CagematchBadge from '@/Components/CagematchBadge';
import MatchRow, { MatchData } from '@/Components/MatchRow';
import RatingStars from '@/Components/RatingStars';
import SpoilerToggle from '@/Components/SpoilerToggle';
import VideoPlaceholder, { WatchTarget } from '@/Components/VideoPlaceholder';
import WikidataSourceBadge from '@/Components/WikidataSourceBadge';
import WikipediaSourceBadge, { isWikipediaSourceUrl } from '@/Components/WikipediaSourceBadge';
import { Link, router } from '@inertiajs/react';
import { Badge, Box, Button, Flex, Heading, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';

interface LinkedVenue {
    name: string;
    slug: string;
}

interface ShowData {
    id: number;
    title: string;
    slug: string;
    date: string;
    episode_number?: number | null;
    venue?: string | LinkedVenue | null;
    city?: string | null;
    show_type: string;
    tv_rating?: number | null;
    promotion?: { name: string; slug: string };
    cagematch_url?: string | null;
    source?: string | null;
    source_id?: string | null;
    source_url?: string | null;
    matches?: MatchData[];
    rating_average?: number;
    rating_count?: number;
    on_watchlist?: boolean;
    is_watched?: boolean;
    watch_targets?: WatchTarget[];
}

function formatShowDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

function isLinkedVenue(venue: ShowData['venue']): venue is LinkedVenue {
    return typeof venue === 'object' && venue !== null && 'slug' in venue;
}

function renderVenueLabel(venue: ShowData['venue']) {
    if (isLinkedVenue(venue)) {
        return (
            <Link href={route('venues.show', venue.slug)}>
                <Text
                    as="span"
                    color="mea.gold"
                    fontWeight="medium"
                    _hover={{ color: 'mea.goldBright', textDecoration: 'underline' }}
                >
                    {venue.name}
                </Text>
            </Link>
        );
    }

    if (typeof venue === 'string' && venue !== '') {
        return venue;
    }

    return null;
}

export default function ShowPage({
    show,
    spoilersEnabled,
}: {
    show: ShowData;
    spoilersEnabled: boolean;
}) {
    const metaParts = [
        formatShowDate(show.date),
        renderVenueLabel(show.venue),
        show.city,
        show.show_type.toUpperCase(),
        show.tv_rating != null ? `TV rating: ${show.tv_rating}` : null,
    ].filter(Boolean);

    const showWikidataBadge = show.source === 'wikidata' && show.source_id;
    const showWikipediaBadge =
        isWikipediaSourceUrl(show.source_url) ||
        (show.source === 'wikipedia' && !!show.source_url);

    return (
        <AppLayout>
            <Head title={show.title} />
            <Box
                as="article"
                bg="mea.surface"
                borderWidth="1px"
                borderColor={spoilersEnabled ? 'mea.red' : 'mea.border'}
                borderRadius="xl"
                overflow="hidden"
            >
                {spoilersEnabled ? (
                    <Box bg="mea.red" py={2} px={6} textAlign="center">
                        <Text fontWeight="bold" fontSize="sm" textTransform="uppercase" letterSpacing="wider">
                            Spoilers Enabled
                        </Text>
                    </Box>
                ) : null}

                <Box p={{ base: 6, md: 8 }}>
                    <Flex justify="space-between" align="start" mb={6} flexWrap="wrap" gap={4}>
                        <Box>
                            {!spoilersEnabled ? (
                                <Badge
                                    bgGradient="to-r"
                                    gradientFrom="mea.gold"
                                    gradientTo="mea.goldBright"
                                    color="mea.bg"
                                    mb={3}
                                    px={4}
                                    py={1.5}
                                    borderRadius="full"
                                    fontSize="xs"
                                    fontWeight="bold"
                                    textTransform="uppercase"
                                    letterSpacing="wider"
                                    boxShadow="goldGlow"
                                >
                                    Spoilers OFF
                                </Badge>
                            ) : null}
                            <Heading as="h1" size="2xl" mb={2} fontWeight="bold">
                                {show.title}
                            </Heading>
                            <Text color="mea.gold" fontSize="md" fontWeight="medium">
                                {metaParts.map((part, index) => (
                                    <Text as="span" key={index}>
                                        {index > 0 ? ' | ' : ''}
                                        {part}
                                    </Text>
                                ))}
                            </Text>
                        </Box>
                        <SpoilerToggle showSlug={show.slug} initialEnabled={spoilersEnabled} />
                    </Flex>

                    {(show.matches ?? []).length > 0 ? (
                        <Box mb={8}>
                            <Heading size="md" mb={4}>
                                Match Card
                            </Heading>
                            <Box as="ol" display="flex" flexDirection="column" gap={3}>
                                {(show.matches ?? []).map((match) => (
                                    <MatchRow
                                        key={match.id}
                                        match={match}
                                        spoilersEnabled={spoilersEnabled}
                                        promotionName={show.promotion?.name}
                                        promotionSlug={show.promotion?.slug}
                                    />
                                ))}
                            </Box>
                        </Box>
                    ) : null}

                    <Box mb={8}>
                        <VideoPlaceholder watchTargets={show.watch_targets ?? []} />
                    </Box>

                    <Flex
                        gap={4}
                        mb={8}
                        flexWrap="wrap"
                        align="center"
                        justify="space-between"
                    >
                        <Flex gap={4} flexWrap="wrap" align="center">
                            <Button
                                bg="mea.gold"
                                color="mea.bg"
                                fontWeight="semibold"
                                px={6}
                                _hover={{ bg: 'mea.goldBright' }}
                                onClick={() =>
                                    router.post(route(show.on_watchlist ? 'watchlist.destroy' : 'watchlist.store', show.id))
                                }
                            >
                                {show.on_watchlist ? 'Remove from Watchlist' : 'Add to Watchlist'}
                            </Button>
                            <Button
                                variant="outline"
                                borderColor="mea.border"
                                color="white"
                                px={6}
                                _hover={{ bg: 'mea.surfaceAlt' }}
                                onClick={() =>
                                    router.post(route(show.is_watched ? 'watched.destroy' : 'watched.store', show.id))
                                }
                            >
                                {show.is_watched ? 'Unmark Watched' : 'Mark as Watched'}
                            </Button>
                        </Flex>
                        <Flex gap={3} flexWrap="wrap" align="center" justify="flex-end">
                            {showWikidataBadge ? (
                                <WikidataSourceBadge sourceId={show.source_id!} />
                            ) : null}
                            {showWikipediaBadge && show.source_url ? (
                                <WikipediaSourceBadge url={show.source_url} />
                            ) : null}
                            {show.cagematch_url ? <CagematchBadge url={show.cagematch_url} /> : null}
                        </Flex>
                    </Flex>

                    <Box
                        borderTopWidth="1px"
                        borderColor="mea.border"
                        pt={6}
                    >
                        <RatingStars
                            rateableType="show"
                            rateableId={show.id}
                            average={show.rating_average}
                            count={show.rating_count}
                            label="Rate Show"
                        />
                    </Box>
                </Box>
            </Box>
        </AppLayout>
    );
}
