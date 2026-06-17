import ShowCard, { ShowCardData } from '@/Components/ShowCard';
import { Box, Flex, Heading, IconButton } from '@chakra-ui/react';
import { useRef } from 'react';

const CARD_WIDTH = 380;
const CARD_GAP = 20;

export default function FeaturedCarousel({ shows }: { shows: ShowCardData[] }) {
    const scrollRef = useRef<HTMLDivElement>(null);

    const scrollBy = (direction: 'prev' | 'next') => {
        const container = scrollRef.current;
        if (!container) {
            return;
        }

        const offset = (CARD_WIDTH + CARD_GAP) * (direction === 'next' ? 1 : -1);
        container.scrollBy({ left: offset, behavior: 'smooth' });
    };

    return (
        <Box bg="mea.featuredBand" py={{ base: 8, md: 10 }} px={{ base: 4, md: 6 }}>
            <Flex maxW="7xl" mx="auto" direction="column" gap={6}>
                <Flex align="center" justify="space-between" gap={4}>
                    <Heading size="xl" fontWeight="bold">
                        Latest Shows
                    </Heading>
                    <Flex gap={2} flexShrink={0}>
                        <IconButton
                            aria-label="Previous latest shows"
                            variant="outline"
                            borderColor="mea.border"
                            color="white"
                            borderRadius="full"
                            size="sm"
                            onClick={() => scrollBy('prev')}
                            _hover={{ borderColor: 'mea.gold', color: 'mea.gold' }}
                        >
                            ‹
                        </IconButton>
                        <IconButton
                            aria-label="Next latest shows"
                            variant="outline"
                            borderColor="mea.border"
                            color="white"
                            borderRadius="full"
                            size="sm"
                            onClick={() => scrollBy('next')}
                            _hover={{ borderColor: 'mea.gold', color: 'mea.gold' }}
                        >
                            ›
                        </IconButton>
                    </Flex>
                </Flex>

                <Box
                    ref={scrollRef}
                    overflowX="auto"
                    overflowY="hidden"
                    mx={{ base: -4, md: 0 }}
                    px={{ base: 4, md: 0 }}
                    css={{
                        scrollbarWidth: 'none',
                        '&::-webkit-scrollbar': { display: 'none' },
                    }}
                >
                    <Flex gap={`${CARD_GAP}px`} pb={2} w="max-content">
                        {shows.map((show) => (
                            <Box key={show.id} flexShrink={0} w={`${CARD_WIDTH}px`}>
                                <ShowCard show={show} variant="carousel" />
                            </Box>
                        ))}
                    </Flex>
                </Box>
            </Flex>
        </Box>
    );
}
