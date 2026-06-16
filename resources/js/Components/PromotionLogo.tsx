import ShowThumbnail from '@/Components/ShowThumbnail';
import { Box, Image } from '@chakra-ui/react';
import aewLogo from '../../images/promotions/aew.svg';
import ecwLogo from '../../images/promotions/ecw.svg';
import tnaLogo from '../../images/promotions/tna.svg';
import wcwLogo from '../../images/promotions/wcw.svg';
import wweLogo from '../../images/promotions/wwe.svg';

const promotionLogos: Record<string, string> = {
    aew: aewLogo,
    ecw: ecwLogo,
    tna: tnaLogo,
    wcw: wcwLogo,
    wwe: wweLogo,
};

/** Tile background + padding tuned per logo colors and aspect ratio (do not recolor artwork). */
const logoDisplayConfig: Record<string, { bg: string; borderColor?: string; paddingScale?: number }> = {
    aew: { bg: '#ffffff', borderColor: 'mea.border', paddingScale: 0.5 },
    ecw: { bg: '#ffffff', borderColor: 'mea.border' },
    tna: { bg: '#000000', borderColor: 'mea.border', paddingScale: 0.5 },
    wcw: { bg: '#ffffff', borderColor: 'mea.border' },
    wwe: { bg: '#000000', borderColor: 'mea.border' },
};

const sizeStyles = {
    sm: { dimension: '48px', padding: 2 },
    md: { dimension: '64px', padding: 2.5 },
    lg: { dimension: '80px', padding: 3 },
} as const;

function scaledPadding(base: number, scale: number): number {
    return Math.max(1, Math.round(base * scale * 4) / 4);
}

export default function PromotionLogo({
    promotionSlug,
    promotionName,
    size = 'md',
}: {
    promotionSlug?: string;
    promotionName?: string;
    size?: 'sm' | 'md' | 'lg';
}) {
    const logoSrc = promotionSlug ? promotionLogos[promotionSlug] : undefined;
    const display = promotionSlug
        ? (logoDisplayConfig[promotionSlug] ?? { bg: 'mea.surfaceAlt', borderColor: 'mea.border' })
        : { bg: 'mea.surfaceAlt', borderColor: 'mea.border' };
    const dimensions = sizeStyles[size];
    const padding = scaledPadding(dimensions.padding, display.paddingScale ?? 1);

    if (!logoSrc) {
        return <ShowThumbnail promotionName={promotionName} size={size} />;
    }

    return (
        <Box
            flexShrink={0}
            w={dimensions.dimension}
            h={dimensions.dimension}
            borderRadius="lg"
            bg={display.bg}
            borderWidth="1px"
            borderColor={display.borderColor}
            display="flex"
            alignItems="center"
            justifyContent="center"
            p={padding}
        >
            <Image
                src={logoSrc}
                alt=""
                aria-hidden
                w="100%"
                h="100%"
                objectFit="contain"
            />
        </Box>
    );
}
