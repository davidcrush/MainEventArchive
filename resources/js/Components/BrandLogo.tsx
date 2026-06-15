import { Box, Flex, Image, Text } from '@chakra-ui/react';
import logo from '../../images/brand/logo.jpg';

export default function BrandLogo({ compact = false }: { compact?: boolean }) {
    const iconSize = compact ? '64px' : { base: '80px', md: '96px' };

    return (
        <Flex align="center" gap={{ base: 3, md: 4 }} flexShrink={0}>
            <Box
                overflow="hidden"
                w={iconSize}
                h={iconSize}
                flexShrink={0}
                borderRadius="md"
            >
                <Image
                    src={logo}
                    alt=""
                    w={iconSize}
                    h="185%"
                    objectFit="cover"
                    objectPosition="top center"
                    aria-hidden
                />
            </Box>

            {!compact ? (
                <Box lineHeight="tight">
                    <Text
                        fontWeight="bold"
                        fontSize={{ base: 'md', md: 'lg' }}
                        letterSpacing="wider"
                        textTransform="uppercase"
                        color="white"
                    >
                        Main Event
                    </Text>
                    <Flex align="center" gap={2} mt={0.5}>
                        <Box h="1px" w={{ base: 3, md: 4 }} bg="mea.gold" />
                        <Text
                            fontWeight="semibold"
                            fontSize={{ base: 'xs', md: 'sm' }}
                            letterSpacing="widest"
                            textTransform="uppercase"
                            color="mea.gold"
                        >
                            Archive
                        </Text>
                        <Box h="1px" w={{ base: 3, md: 4 }} bg="mea.gold" />
                    </Flex>
                    <Text
                        fontSize="xs"
                        color="mea.muted"
                        letterSpacing="wider"
                        textTransform="uppercase"
                        display={{ base: 'none', md: 'block' }}
                        mt={1}
                    >
                        Search · Browse · Rate · Watch
                    </Text>
                </Box>
            ) : null}
        </Flex>
    );
}
