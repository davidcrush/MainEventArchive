import WikipediaSourceBadge from '@/Components/WikipediaSourceBadge';
import PromotionLogo from '@/Components/PromotionLogo';
import { Link } from '@inertiajs/react';
import { Badge, Box, Flex, Heading, Text } from '@chakra-ui/react';

export interface PromotionCardData {
    id: number;
    name: string;
    slug: string;
    logo_path?: string | null;
    founded_year?: number | null;
    is_active: boolean;
    active_years_label?: string | null;
    status_label: string;
    headquarters?: string | null;
    description?: string | null;
    wikipedia_url?: string | null;
    published_show_count?: number;
}

export default function PromotionCard({ promotion }: { promotion: PromotionCardData }) {
    return (
        <Box
            bg="mea.surface"
            borderWidth="1px"
            borderColor="mea.border"
            borderRadius="xl"
            overflow="hidden"
            transition="all 0.2s"
            _hover={{
                borderColor: 'mea.gold',
                transform: 'translateY(-3px)',
                boxShadow: 'cardHover',
            }}
        >
            <Link
                href={route('browse', { promotion: promotion.slug })}
                aria-label={`Browse ${promotion.name} shows`}
            >
                <Flex
                    direction={{ base: 'column', sm: 'row' }}
                    gap={5}
                    p={{ base: 4, md: 5 }}
                    align={{ base: 'stretch', sm: 'flex-start' }}
                >
                    <Flex justify={{ base: 'center', sm: 'flex-start' }}>
                        <PromotionLogo
                            promotionSlug={promotion.slug}
                            promotionName={promotion.name}
                            size="lg"
                        />
                    </Flex>
                    <Box flex={1} minW={0}>
                        <Flex
                            align="center"
                            gap={3}
                            mb={2}
                            flexWrap="wrap"
                            justify={{ base: 'center', sm: 'flex-start' }}
                        >
                            <Heading size="md">{promotion.name}</Heading>
                            <Badge
                                colorPalette={promotion.is_active ? 'green' : 'gray'}
                                variant="subtle"
                            >
                                {promotion.status_label}
                            </Badge>
                        </Flex>
                        <Flex
                            direction="column"
                            gap={1}
                            mb={3}
                            color="mea.muted"
                            fontSize="sm"
                            textAlign={{ base: 'center', sm: 'left' }}
                        >
                            {promotion.founded_year !== null && promotion.founded_year !== undefined && (
                                <Text>Founded {promotion.founded_year}</Text>
                            )}
                            {promotion.active_years_label && (
                                <Text>Active {promotion.active_years_label}</Text>
                            )}
                            {promotion.headquarters && (
                                <Text>Based in {promotion.headquarters}</Text>
                            )}
                            {typeof promotion.published_show_count === 'number' && (
                                <Text>
                                    {promotion.published_show_count}{' '}
                                    {promotion.published_show_count === 1 ? 'show' : 'shows'} in catalog
                                </Text>
                            )}
                        </Flex>
                        {promotion.description && (
                            <Text color="white" fontSize="md" lineClamp={3}>
                                {promotion.description}
                            </Text>
                        )}
                    </Box>
                </Flex>
            </Link>
            {promotion.wikipedia_url && (
                <Box
                    px={{ base: 4, md: 5 }}
                    pb={{ base: 4, md: 5 }}
                    pt={0}
                    display="flex"
                    justifyContent={{ base: 'center', sm: 'flex-start' }}
                >
                    <WikipediaSourceBadge url={promotion.wikipedia_url} />
                </Box>
            )}
        </Box>
    );
}
