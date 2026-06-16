import { Link } from '@inertiajs/react';
import { Badge, Box, Flex, Heading, Text } from '@chakra-ui/react';
import PromotionLogo from './PromotionLogo';
import RatingStars from './RatingStars';

export interface ShowCardData {
    id: number;
    title: string;
    slug: string;
    date: string;
    show_type: string;
    promotion?: { name: string; slug: string };
    rating_average?: number;
    rating_count?: number;
    has_video?: boolean;
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
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

    return (
        <Link href={route('shows.show', show.slug)}>
            <Box
                bg="mea.surface"
                borderWidth="1px"
                borderColor="mea.border"
                borderRadius="xl"
                p={isCarousel ? 4 : { base: 4, md: 5 }}
                h={isCarousel ? '100%' : undefined}
                transition="all 0.2s"
                _hover={{
                    borderColor: 'mea.gold',
                    transform: 'translateY(-3px)',
                    boxShadow: 'cardHover',
                }}
            >
                <Flex gap={4} align="center">
                    <PromotionLogo
                        promotionSlug={show.promotion?.slug}
                        promotionName={show.promotion?.name}
                        size={thumbnailSize}
                    />
                    <Box flex={1} minW={0}>
                        <Heading
                            size={isCarousel ? 'sm' : 'sm'}
                            mb={1}
                            lineClamp={isCarousel ? 2 : 1}
                        >
                            {show.title}
                        </Heading>
                        <Text color="mea.muted" fontSize="sm" mb={2}>
                            {formatDate(show.date)}
                        </Text>
                        <Flex align="center" gap={2} flexWrap="wrap">
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
                            <RatingStars
                                average={show.rating_average}
                                count={show.rating_count}
                                compact
                            />
                        </Flex>
                    </Box>
                </Flex>
            </Box>
        </Link>
    );
}
