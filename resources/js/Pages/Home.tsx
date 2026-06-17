import AppLayout from '@/Layouts/AppLayout';
import FeaturedCarousel from '@/Components/FeaturedCarousel';
import { ShowCardData } from '@/Components/ShowCard';
import { Box, Flex, Heading, Text } from '@chakra-ui/react';
import { Head, Link } from '@inertiajs/react';
import heroRing from '../../images/hero-ring.jpg';

export default function Home({ featuredShows }: { featuredShows: ShowCardData[] }) {
    return (
        <AppLayout fullWidth>
            <Head title="Home" />

            <Box
                position="relative"
                backgroundImage={`url(${heroRing})`}
                backgroundSize="cover"
                backgroundPosition="center"
                minH={{ base: '420px', md: '520px' }}
                display="flex"
                alignItems="center"
                justifyContent="center"
                textAlign="center"
                px={6}
            >
                <Box
                    position="absolute"
                    inset={0}
                    bgGradient="to-b"
                    gradientFrom="rgba(10, 14, 39, 0.25)"
                    gradientVia="rgba(10, 14, 39, 0.45)"
                    gradientTo="mea.bg"
                />
                <Box position="relative" zIndex={1} maxW="4xl" py={{ base: 16, md: 24 }}>
                    <Heading
                        size={{ base: '3xl', md: '5xl' }}
                        mb={4}
                        fontWeight="bold"
                        letterSpacing="tight"
                        lineHeight="shorter"
                    >
                        Find Wrestling Shows
                    </Heading>
                    <Text
                        color="mea.gold"
                        fontSize={{ base: 'xl', md: '2xl' }}
                        mb={8}
                        fontWeight="semibold"
                    >
                        Spoiler-Safe Browsing
                    </Text>
                    <Flex justify="center">
                        <Link href={route('browse')} style={{ textDecoration: 'none' }}>
                            <Box
                                bgGradient="to-r"
                                gradientFrom="mea.gold"
                                gradientTo="mea.goldBright"
                                color="mea.bg"
                                px={{ base: 5, md: 8 }}
                                py={{ base: 3, md: 4 }}
                                borderRadius="full"
                                fontWeight="bold"
                                fontSize={{ base: 'sm', md: 'md' }}
                                boxShadow="goldGlow"
                                transition="all 0.2s"
                                _hover={{
                                    transform: 'translateY(-2px)',
                                }}
                            >
                                Browse Shows
                            </Box>
                        </Link>
                    </Flex>
                </Box>
            </Box>

            <FeaturedCarousel shows={featuredShows} />
        </AppLayout>
    );
}
