import { Link } from '@inertiajs/react';
import { Badge, Box, Flex, Heading, Text } from '@chakra-ui/react';
import PromotionLogo from './PromotionLogo';
import RatingStars from './RatingStars';

export interface LinkedVenue {
    name: string;
    slug: string;
}

export interface MainEventPreview {
    line: string;
    title_name?: string | null;
}

export interface ShowCardData {
    id: number;
    title: string;
    slug: string;
    date: string;
    venue?: string | LinkedVenue | null;
    city?: string | null;
    show_type: string;
    promotion?: { name: string; slug: string };
    rating_average?: number;
    rating_count?: number;
    has_video?: boolean;
    has_card?: boolean;
    main_event_preview?: MainEventPreview | null;
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatLocationLine(
    venue?: string | LinkedVenue | null,
    city?: string | null,
): string | null {
    const venueName =
        typeof venue === 'object' && venue !== null
            ? venue.name
            : typeof venue === 'string' && venue !== ''
              ? venue
              : null;
    const cityName = city && city !== '' ? city : null;

    if (venueName && cityName) {
        return `${venueName} • ${cityName}`;
    }

    return venueName ?? cityName;
}

function formatMainEventLine(preview: MainEventPreview): string {
    if (preview.title_name) {
        return `${preview.line} — ${preview.title_name}`;
    }

    return preview.line;
}

function CardAvailabilityBadge({ hasCard }: { hasCard: boolean }) {
    if (hasCard) {
        return (
            <Badge
                bg="mea.surfaceAlt"
                color="mea.gold"
                borderWidth="1px"
                borderColor="mea.gold"
                fontSize="2xs"
                px={2}
                py={0.5}
                borderRadius="md"
                textTransform="uppercase"
                title="Match card available"
            >
                Card
            </Badge>
        );
    }

    return (
        <Badge
            bg="mea.surfaceAlt"
            color="mea.muted"
            fontSize="2xs"
            px={2}
            py={0.5}
            borderRadius="md"
            textTransform="uppercase"
            title="Match card not available yet"
        >
            No card
        </Badge>
    );
}

function MainEventPreviewText({
    preview,
    variant,
}: {
    preview: MainEventPreview;
    variant: 'default' | 'carousel';
}) {
    const mainEventLine = formatMainEventLine(preview);

    return (
        <Text
            color="mea.muted"
            fontSize={variant === 'carousel' ? 'xs' : 'sm'}
            lineClamp={1}
            mt={variant === 'carousel' ? 3 : 0}
            title={mainEventLine}
        >
            <Text as="span" color="mea.gold" fontWeight="semibold">
                Main event:{' '}
            </Text>
            {mainEventLine}
        </Text>
    );
}

export default function ShowCard({
    show,
    variant = 'default',
}: {
    show: ShowCardData;
    variant?: 'default' | 'carousel';
}) {
    const isCarousel = variant === 'carousel';
    const thumbnailSize = isCarousel ? 'lg' : 'md';
    const hasCard = show.has_card ?? false;
    const mainEventPreview = show.main_event_preview;
    const locationLine = formatLocationLine(show.venue, show.city);

    return (
        <Box h={isCarousel ? undefined : '100%'}>
            <Link
                href={route('shows.show', show.slug)}
                style={{ display: 'block', height: '100%', textDecoration: 'none' }}
            >
            <Box
                bg="mea.surface"
                borderWidth="1px"
                borderColor="mea.border"
                borderRadius="xl"
                p={isCarousel ? 4 : { base: 5, md: 6 }}
                h={isCarousel ? '100%' : '100%'}
                display="flex"
                flexDirection="column"
                transition="all 0.2s"
                _hover={{
                    borderColor: 'mea.gold',
                    transform: 'translateY(-3px)',
                    boxShadow: 'cardHover',
                }}
            >
                <Flex gap={4} align="flex-start" flex={1}>
                    <PromotionLogo
                        promotionSlug={show.promotion?.slug}
                        promotionName={show.promotion?.name}
                        size={thumbnailSize}
                    />
                    <Box flex={1} minW={0}>
                        <Heading size={isCarousel ? 'sm' : 'md'} mb={1} lineClamp={2} minH="2.75rem">
                            {show.title}
                        </Heading>
                        <Text color="mea.muted" fontSize="sm" mb={1}>
                            {formatDate(show.date)}
                        </Text>
                        <Text
                            color="mea.muted"
                            fontSize="sm"
                            mb={3}
                            lineClamp={1}
                            minH="1.25rem"
                            title={locationLine ?? undefined}
                            visibility={locationLine ? 'visible' : 'hidden'}
                        >
                            {locationLine ?? '\u00A0'}
                        </Text>
                        <Flex align="center" gap={2} flexWrap="wrap" minH="1.5rem">
                            <Badge
                                bg="mea.surfaceAlt"
                                color="mea.muted"
                                fontSize="2xs"
                                px={2}
                                py={0.5}
                                borderRadius="md"
                                textTransform="uppercase"
                            >
                                {show.show_type}
                            </Badge>
                            <CardAvailabilityBadge hasCard={hasCard} />
                            {show.has_video ? (
                                <Badge
                                    bg="mea.gold"
                                    color="mea.bg"
                                    fontSize="2xs"
                                    px={2}
                                    py={0.5}
                                    borderRadius="md"
                                    textTransform="uppercase"
                                    title="Full show video available"
                                >
                                    Video
                                </Badge>
                            ) : null}
                            <RatingStars rateableType="show" rateableId={show.id} compact />
                        </Flex>
                    </Box>
                </Flex>

                {mainEventPreview ? (
                    isCarousel ? (
                        <MainEventPreviewText preview={mainEventPreview} variant="carousel" />
                    ) : (
                        <Box
                            mt={4}
                            pt={3}
                            borderTopWidth="1px"
                            borderColor="mea.border"
                            minH="2.25rem"
                        >
                            <MainEventPreviewText preview={mainEventPreview} variant="default" />
                        </Box>
                    )
                ) : !hasCard && !isCarousel ? (
                    <Text
                        mt={4}
                        pt={3}
                        borderTopWidth="1px"
                        borderColor="mea.border"
                        fontSize="sm"
                        color="mea.muted"
                        fontStyle="italic"
                        lineClamp={1}
                        minH="2.25rem"
                    >
                        Match card not available yet
                    </Text>
                ) : null}
            </Box>
            </Link>
        </Box>
    );
}
