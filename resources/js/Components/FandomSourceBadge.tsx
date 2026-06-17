import { Link } from '@chakra-ui/react';

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

export default function FandomSourceBadge({ url }: { url: string }) {
    return (
        <Link
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="View source data on the Pro Wrestling Fandom wiki (opens in new tab)"
            {...badgeStyles}
        >
            View on Fandom
        </Link>
    );
}
