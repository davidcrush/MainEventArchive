import { defineSlotRecipe } from '@chakra-ui/react';

export const meaSelectSlotRecipe = defineSlotRecipe({
    className: 'chakra-select',
    slots: [
        'root',
        'label',
        'control',
        'trigger',
        'indicatorGroup',
        'indicator',
        'content',
        'item',
        'itemText',
        'itemIndicator',
        'itemGroup',
        'itemGroupLabel',
        'valueText',
        'clearTrigger',
    ],
    base: {
        root: {
            display: 'flex',
            flexDirection: 'column',
            gap: '1.5',
            width: 'full',
        },
        control: {
            pos: 'relative',
        },
        trigger: {
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            width: 'full',
            minH: 'var(--select-trigger-height)',
            px: 'var(--select-trigger-padding-x)',
            borderWidth: '1px',
            userSelect: 'none',
            textAlign: 'start',
        },
        indicatorGroup: {
            display: 'flex',
            alignItems: 'center',
            gap: '1',
            pos: 'absolute',
            insetEnd: '0',
            top: '0',
            bottom: '0',
            px: 'var(--select-trigger-padding-x)',
            pointerEvents: 'none',
        },
        content: {
            display: 'flex',
            flexDirection: 'column',
            maxH: '96',
            overflowY: 'auto',
            outline: '0',
            zIndex: 'popover',
        },
        item: {
            position: 'relative',
            userSelect: 'none',
            display: 'flex',
            alignItems: 'center',
            gap: '2',
            cursor: 'option',
            justifyContent: 'space-between',
            flex: '1',
            textAlign: 'start',
            borderRadius: 'md',
        },
        valueText: {
            lineClamp: '1',
            maxW: '80%',
        },
    },
    variants: {
        variant: {
            outline: {
                trigger: {
                    bg: 'mea.surfaceAlt',
                    color: 'white',
                    borderColor: 'mea.border',
                    borderRadius: 'full',
                    _placeholderShown: {
                        color: 'mea.muted',
                    },
                    _focusVisible: {
                        borderColor: 'mea.gold',
                        boxShadow: '0 0 0 1px var(--chakra-colors-mea-gold)',
                    },
                    _expanded: {
                        borderColor: 'mea.gold',
                    },
                },
                content: {
                    bg: 'mea.surface',
                    borderWidth: '1px',
                    borderColor: 'mea.border',
                    boxShadow: 'cardHover',
                    borderRadius: 'xl',
                },
                item: {
                    color: 'white',
                    _highlighted: {
                        bg: 'mea.surfaceAlt',
                    },
                },
                itemIndicator: {
                    color: 'mea.gold',
                },
                indicator: {
                    color: 'mea.muted',
                },
            },
        },
        size: {
            sm: {
                root: {
                    '--select-trigger-height': '44px',
                    '--select-trigger-padding-x': '20px',
                },
                content: {
                    p: '1',
                    textStyle: 'sm',
                },
                trigger: {
                    textStyle: 'sm',
                },
                item: {
                    py: '1.5',
                    px: '2',
                },
            },
        },
    },
    defaultVariants: {
        variant: 'outline',
        size: 'sm',
    },
});
