import { Image, Link } from '@chakra-ui/react';
import wikipediaLogo from '../../images/third-party/wikipedia-logo.svg';

const badgeStyles = {
    display: 'inline-flex',
    alignItems: 'center',
    gap: 2,
    px: 4,
    py: 2,
    bg: 'mea.surfaceAlt',
    borderWidth: '1px',
    borderColor: 'mea.border',
    borderRadius: 'md',
    color: 'white',
    fontWeight: 'semibold',
    fontSize: 'sm',
    _hover: { opacity: 0.9, textDecoration: 'none', borderColor: 'mea.muted' },
} as const;

export function isWikipediaSourceUrl(url: string | null | undefined): boolean {
    if (!url) {
        return false;
    }

    try {
        const hostname = new URL(url).hostname;

        return hostname === 'wikipedia.org' || hostname.endsWith('.wikipedia.org');
    } catch {
        return url.includes('wikipedia.org');
    }
}

export default function WikipediaSourceBadge({ url }: { url: string }) {
    return (
        <Link
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="View source data on Wikipedia (opens in new tab)"
            {...badgeStyles}
        >
            <Image src={wikipediaLogo} alt="" h="20px" w="auto" aria-hidden />
            View on Wikipedia
        </Link>
    );
}
