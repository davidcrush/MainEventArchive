export const scrollbarGlobalCss = {
    html: {
        scrollbarWidth: 'thin',
        scrollbarColor: '{colors.mea.border} {colors.mea.surfaceAlt}',
    },
    '*::-webkit-scrollbar': {
        width: '10px',
        height: '10px',
    },
    '*::-webkit-scrollbar-track': {
        bg: 'mea.surfaceAlt',
        borderRadius: 'full',
    },
    '*::-webkit-scrollbar-thumb': {
        bg: 'mea.border',
        borderRadius: 'full',
        borderWidth: '2px',
        borderStyle: 'solid',
        borderColor: 'mea.surfaceAlt',
    },
    '*::-webkit-scrollbar-thumb:hover': {
        bg: 'mea.gold',
    },
};
