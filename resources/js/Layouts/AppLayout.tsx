import BrandLogo from '@/Components/BrandLogo';
import { getGlobalSpoilers, setGlobalSpoilers } from '@/hooks/useSpoilers';
import { Link, usePage } from '@inertiajs/react';
import { Box, Flex, Switch, Text } from '@chakra-ui/react';
import { useState } from 'react';

export default function AppLayout({
    children,
    fullWidth = false,
}: {
    children: React.ReactNode;
    fullWidth?: boolean;
}) {
    const [globalSpoilers, setGlobalSpoilersState] = useState(getGlobalSpoilers());
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
                        <Link href={route('watchlist.index')}>
                            <Text
                                color="white"
                                fontSize="md"
                                fontWeight="semibold"
                                _hover={{ color: 'mea.gold' }}
                                transition="color 0.15s"
                            >
                                Watchlist
                            </Text>
                        </Link>

                        <Flex align="center" gap={2}>
                            <Text
                                fontSize="sm"
                                color="mea.muted"
                                display={{ base: 'none', md: 'block' }}
                                whiteSpace="nowrap"
                            >
                                Spoiler Preference
                            </Text>
                            <Switch.Root
                                checked={globalSpoilers}
                                onCheckedChange={(e) => {
                                    const enabled = !!e.checked;
                                    setGlobalSpoilers(enabled);
                                    setGlobalSpoilersState(enabled);
                                }}
                                colorPalette="yellow"
                                size="md"
                            >
                                <Switch.HiddenInput />
                                <Switch.Control />
                            </Switch.Root>
                        </Flex>
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
                    <Text>Main Event Archive — spoiler-safe wrestling catalog</Text>
                    <Flex align="center" gap={3}>
                        <Link href={route('attribution')}>
                            <Text _hover={{ color: 'mea.gold' }}>Attribution</Text>
                        </Link>
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
