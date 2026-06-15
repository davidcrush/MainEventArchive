import { Box, Text } from '@chakra-ui/react';

function promotionAbbreviation(name: string): string {
    const words = name.trim().split(/\s+/);

    if (words.length === 1) {
        return words[0].slice(0, 3).toUpperCase();
    }

    return words.map((word) => word[0]).join('').slice(0, 4).toUpperCase();
}

export default function ShowThumbnail({
    promotionName,
    size = 'md',
}: {
    promotionName?: string;
    size?: 'sm' | 'md' | 'lg';
}) {
    const dimensions = { sm: '48px', md: '64px', lg: '80px' };
    const fontSize = { sm: 'xs', md: 'sm', lg: 'md' };

    return (
        <Box
            flexShrink={0}
            w={dimensions[size]}
            h={dimensions[size]}
            borderRadius="lg"
            bgGradient="to-br"
            gradientFrom="mea.goldBright"
            gradientTo="mea.gold"
            display="flex"
            alignItems="center"
            justifyContent="center"
            boxShadow="0 4px 12px rgba(212, 175, 55, 0.3)"
        >
            <Text
                fontSize={fontSize[size]}
                fontWeight="bold"
                color="mea.bg"
                letterSpacing="wider"
            >
                {promotionAbbreviation(promotionName ?? 'WCW')}
            </Text>
        </Box>
    );
}
