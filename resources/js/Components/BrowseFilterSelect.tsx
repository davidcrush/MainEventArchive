import {
    Portal,
    Select,
    VisuallyHidden,
    createListCollection,
} from '@chakra-ui/react';
import { useMemo } from 'react';

export interface BrowseFilterOption {
    label: string;
    value: string;
}

interface BrowseFilterSelectProps {
    label: string;
    value: string;
    options: BrowseFilterOption[];
    onChange: (value: string) => void;
}

export default function BrowseFilterSelect({
    label,
    value,
    options,
    onChange,
}: BrowseFilterSelectProps) {
    const collection = useMemo(
        () =>
            createListCollection({
                items: options,
                itemToString: (item) => item.label,
                itemToValue: (item) => item.value,
            }),
        [options],
    );

    return (
        <Select.Root
            collection={collection}
            size="sm"
            value={value !== '' ? [value] : []}
            onValueChange={(details) => onChange(details.value[0] ?? '')}
            positioning={{ sameWidth: true }}
            flex={{ base: '1 1 100%', sm: '1 1 auto' }}
        >
            <Select.HiddenSelect />
            <VisuallyHidden asChild>
                <Select.Label>{label}</Select.Label>
            </VisuallyHidden>
            <Select.Control>
                <Select.Trigger aria-label={label}>
                    <Select.ValueText placeholder={label} />
                </Select.Trigger>
                <Select.IndicatorGroup>
                    <Select.Indicator />
                </Select.IndicatorGroup>
            </Select.Control>
            <Portal>
                <Select.Positioner>
                    <Select.Content>
                        {collection.items.map((option) => (
                            <Select.Item item={option} key={option.value || '__empty'}>
                                <Select.ItemText>{option.label}</Select.ItemText>
                                <Select.ItemIndicator />
                            </Select.Item>
                        ))}
                    </Select.Content>
                </Select.Positioner>
            </Portal>
        </Select.Root>
    );
}
