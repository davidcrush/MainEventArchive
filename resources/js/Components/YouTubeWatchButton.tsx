import { Image, Link } from '@chakra-ui/react';
import youtubeLogo from '../../images/third-party/youtube-logo-full-white.png';

export default function YouTubeWatchButton({ url }: { url: string }) {
    return (
        <Link
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Watch on YouTube (opens in new tab)"
            display="inline-flex"
            alignItems="center"
            justifyContent="center"
            bg="#000000"
            borderWidth="1px"
            borderColor="mea.border"
            borderRadius="md"
            px={5}
            py={3}
            minH="48px"
            minW="140px"
            flexShrink={0}
            _hover={{ opacity: 0.9, textDecoration: 'none', borderColor: 'mea.muted' }}
            _focusVisible={{ outline: '2px solid', outlineColor: 'mea.gold', outlineOffset: '2px' }}
        >
            <Image
                src={youtubeLogo}
                alt=""
                aria-hidden
                h="26px"
                w="auto"
                objectFit="contain"
            />
        </Link>
    );
}
