import { createSystem, defaultConfig } from '@chakra-ui/react';
import { meaSelectSlotRecipe } from './select-recipe';
import { scrollbarGlobalCss } from './scrollbar';

const { html: scrollbarHtml, ...scrollbarRest } = scrollbarGlobalCss;

export const system = createSystem(defaultConfig, {
    theme: {
        tokens: {
            colors: {
                mea: {
                    bg: { value: '#0a0e27' },
                    bgElevated: { value: '#0f1535' },
                    surface: { value: '#131a3a' },
                    surfaceAlt: { value: '#1b2350' },
                    featuredBand: { value: '#121832' },
                    gold: { value: '#D4AF37' },
                    goldBright: { value: '#F0CC6B' },
                    red: { value: '#C41E3A' },
                    border: { value: '#2a3357' },
                    muted: { value: '#94a3b8' },
                },
            },
            shadows: {
                goldGlow: { value: '0 0 24px rgba(212, 175, 55, 0.45)' },
                cardHover: { value: '0 8px 24px rgba(0, 0, 0, 0.35)' },
            },
        },
        slotRecipes: {
            select: meaSelectSlotRecipe,
        },
    },
    globalCss: {
        html: {
            fontSize: '16px',
            ...scrollbarHtml,
        },
        body: {
            bg: 'mea.bg',
            color: 'white',
            fontSize: 'md',
            lineHeight: '1.6',
        },
        ...scrollbarRest,
    },
});
