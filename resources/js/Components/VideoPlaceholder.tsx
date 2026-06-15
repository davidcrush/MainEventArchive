import { Box, Link, Text } from '@chakra-ui/react';

export default function VideoPlaceholder({
    video,
}: {
    video?: { url: string } | null;
}) {
    return (
        <Box
            bg="mea.surfaceAlt"
            borderWidth="1px"
            borderColor="mea.border"
            borderRadius="lg"
            p={{ base: 6, md: 8 }}
            textAlign="center"
        >
            {video?.url ? (
                <Link
                    href={video.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    display="inline-flex"
                    alignItems="center"
                    justifyContent="center"
                    bg="mea.gold"
                    color="mea.bg"
                    fontWeight="semibold"
                    px={8}
                    py={2.5}
                    borderRadius="md"
                    aria-label="Watch on YouTube (opens in new tab)"
                    _hover={{ bg: 'mea.goldBright', textDecoration: 'none' }}
                >
                    Watch on YouTube
                </Link>
            ) : (
                <Text color="mea.muted" fontSize="md">
                    Sorry, no public recording is available
                </Text>
            )}
        </Box>
    );
}
