import BrandLogo from '@/Components/BrandLogo';
import BuiltByLink from '@/Components/BuiltByLink';
import GitHubRepoLink from '@/Components/GitHubRepoLink';
import { Link, usePage } from '@inertiajs/react';
import { Box, Flex, Text } from '@chakra-ui/react';

export default function AppLayout({
    children,
    fullWidth = false,
}: {
    children: React.ReactNode;
    fullWidth?: boolean;
}) {
    const version = usePage().props.app.version;

    return (
        <Box minH="100vh" bg="mea.bg">
            <Box
                as="header"
                position="sticky"
                top={0}
                zIndex={100}
                bg="mea.bgElevated"
                borderBottomWidth="1px"
                borderColor="mea.border"
                px={{ base: 4, md: 6 }}
                py={5}
            >
                <Flex
                    maxW="7xl"
                    mx="auto"
                    align="center"
                    justify="space-between"
                    gap={4}
                >
                    <Box flexShrink={0}>
                        <Link href={route('home')} aria-label="Main Event Archive home">
                            <BrandLogo />
                        </Link>
                    </Box>

                    <Flex align="center" gap={{ base: 4, md: 6 }} flexShrink={0}>
                        <Link href={route('browse')}>
                            <Text
                                color="white"
                                fontSize="md"
                                fontWeight="semibold"
                                _hover={{ color: 'mea.gold' }}
                                transition="color 0.15s"
                            >
                                Browse
                            </Text>
                        </Link>
                        <Link href={route('promotions.index')}>
                            <Text
                                color="white"
                                fontSize="md"
                                fontWeight="semibold"
                                _hover={{ color: 'mea.gold' }}
                                transition="color 0.15s"
                            >
                                Promotions
                            </Text>
                        </Link>
                    </Flex>
                </Flex>
            </Box>

            <Box
                as="main"
                maxW={fullWidth ? undefined : '7xl'}
                mx="auto"
                px={fullWidth ? 0 : { base: 4, md: 6 }}
                py={fullWidth ? 0 : { base: 6, md: 8 }}
            >
                {children}
            </Box>

            <Box as="footer" borderTopWidth="1px" borderColor="mea.border" px={{ base: 4, md: 6 }} py={6} mt={12}>
                <Flex
                    maxW="7xl"
                    mx="auto"
                    justify="space-between"
                    color="mea.muted"
                    fontSize="sm"
                    flexWrap="wrap"
                    gap={2}
                >
                    <Flex direction="column" gap={1}>
                        <Text>Main Event Archive — spoiler-safe wrestling catalog</Text>
                        <BuiltByLink />
                    </Flex>
                    <Flex align="center" gap={3}>
                        <Link href={route('limitations')}>
                            <Text _hover={{ color: 'mea.gold' }}>Known limitations</Text>
                        </Link>
                        <Link href={route('attribution')}>
                            <Text _hover={{ color: 'mea.gold' }}>Attribution</Text>
                        </Link>
                        <GitHubRepoLink />
                        <Text
                            fontSize="xs"
                            color="mea.muted"
                            opacity={0.75}
                            aria-label="Application version"
                        >
                            v{version}
                        </Text>
                    </Flex>
                </Flex>
            </Box>
        </Box>
    );
}
