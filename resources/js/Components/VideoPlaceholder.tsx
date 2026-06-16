import { Box, Flex, Link, Text } from '@chakra-ui/react';

export interface WatchTarget {
    provider: string;
    url: string;
    mode: string;
    label: string;
}

function netflixAriaLabel(mode: string): string {
    if (mode === 'search') {
        return 'Watch on Netflix (opens search in new tab)';
    }

    return 'Watch on Netflix (opens title in new tab)';
}

function buttonStyles(provider: string) {
    if (provider === 'netflix') {
        return {
            bg: '#E50914',
            color: 'white',
            _hover: { bg: '#F40612', textDecoration: 'none' },
        };
    }

    return {
        bg: 'mea.gold',
        color: 'mea.bg',
        _hover: { bg: 'mea.goldBright', textDecoration: 'none' },
    };
}

export default function VideoPlaceholder({
    watchTargets = [],
}: {
    watchTargets?: WatchTarget[];
}) {
    if (watchTargets.length === 0) {
        return (
            <Box
                bg="mea.surfaceAlt"
                borderWidth="1px"
                borderColor="mea.border"
                borderRadius="lg"
                p={{ base: 6, md: 8 }}
                textAlign="center"
            >
                <Text color="mea.muted" fontSize="md">
                    Sorry, no public recording is available
                </Text>
            </Box>
        );
    }

    const hasNetflixSearch = watchTargets.some(
        (target) => target.provider === 'netflix' && target.mode === 'search',
    );

    return (
        <Box
            bg="mea.surfaceAlt"
            borderWidth="1px"
            borderColor="mea.border"
            borderRadius="lg"
            p={{ base: 6, md: 8 }}
            textAlign="center"
        >
            <Flex direction="column" align="center" gap={3}>
                <Flex gap={3} flexWrap="wrap" justify="center">
                    {watchTargets.map((target) => (
                        <Link
                            key={`${target.provider}-${target.mode}-${target.url}`}
                            href={target.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            display="inline-flex"
                            alignItems="center"
                            justifyContent="center"
                            fontWeight="semibold"
                            px={8}
                            py={2.5}
                            borderRadius="md"
                            aria-label={
                                target.provider === 'netflix'
                                    ? netflixAriaLabel(target.mode)
                                    : 'Watch on YouTube (opens in new tab)'
                            }
                            {...buttonStyles(target.provider)}
                        >
                            {target.label}
                        </Link>
                    ))}
                </Flex>
                {hasNetflixSearch ? (
                    <Text color="mea.muted" fontSize="sm">
                        Search Netflix for this event
                    </Text>
                ) : null}
            </Flex>
        </Box>
    );
}
