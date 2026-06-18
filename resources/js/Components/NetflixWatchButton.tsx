import { Image, Link } from '@chakra-ui/react';
import netflixLogo from '../../images/third-party/Netflix_Logo_RGB.png';

export default function NetflixWatchButton({ url }: { url: string }) {
    return (
        <Link
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Watch on Netflix (opens search in new tab)"
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
            minW="120px"
            flexShrink={0}
            _hover={{ opacity: 0.9, textDecoration: 'none', borderColor: 'mea.muted' }}
            _focusVisible={{ outline: '2px solid', outlineColor: 'mea.gold', outlineOffset: '2px' }}
        >
            <Image
                src={netflixLogo}
                alt=""
                aria-hidden
                h="22px"
                w="auto"
                objectFit="contain"
            />
        </Link>
    );
}
