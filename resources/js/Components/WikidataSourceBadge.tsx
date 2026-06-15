import { Image, Link } from '@chakra-ui/react';
import wikidataLogo from '../../images/third-party/wikidata-logo.svg';

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

export default function WikidataSourceBadge({ sourceId }: { sourceId: string }) {
    const url = `https://www.wikidata.org/wiki/${sourceId}`;

    return (
        <Link
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="View source data on Wikidata (opens in new tab)"
            {...badgeStyles}
        >
            <Image src={wikidataLogo} alt="" h="20px" w="auto" aria-hidden />
            View on Wikidata
        </Link>
    );
}
