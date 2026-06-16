import NetflixWatchButton from '@/Components/NetflixWatchButton';
import YouTubeWatchButton from '@/Components/YouTubeWatchButton';
import { Box, Flex, Heading, Text, VStack } from '@chakra-ui/react';

export interface WatchTarget {
    provider: string;
    url: string;
    mode: string;
    label: string;
}

function WatchTargetRow({ target }: { target: WatchTarget }) {
    if (target.provider === 'youtube') {
        return (
            <VStack gap={2}>
                <YouTubeWatchButton url={target.url} />
                <Text color="mea.muted" fontSize="sm">
                    Opens on YouTube.com · new tab
                </Text>
            </VStack>
        );
    }

    if (target.provider === 'netflix') {
        return (
            <VStack gap={2}>
                <NetflixWatchButton url={target.url} mode={target.mode} />
                {target.mode === 'search' ? (
                    <Text color="mea.muted" fontSize="sm">
                        Search Netflix for this event
                    </Text>
                ) : (
                    <Text color="mea.muted" fontSize="sm">
                        Opens on Netflix.com · new tab
                    </Text>
                )}
            </VStack>
        );
    }

    return null;
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

    return (
        <Box
            bg="mea.surfaceAlt"
            borderWidth="1px"
            borderColor="mea.border"
            borderRadius="lg"
            p={{ base: 6, md: 8 }}
            textAlign="center"
        >
            <Flex direction="column" align="center" gap={5}>
                <Heading as="h2" size="sm" color="white" fontWeight="semibold">
                    Where to watch
                </Heading>
                <Flex
                    direction={{ base: 'column', sm: 'row' }}
                    gap={6}
                    flexWrap="wrap"
                    justify="center"
                    align={{ base: 'center', sm: 'flex-start' }}
                >
                    {watchTargets.map((target) => (
                        <WatchTargetRow
                            key={`${target.provider}-${target.mode}-${target.url}`}
                            target={target}
                        />
                    ))}
                </Flex>
            </Flex>
        </Box>
    );
}
