import { Switch, Text, Flex, Badge } from '@chakra-ui/react';
import {
    appendSpoilersToUrl,
    resolveSpoilersForShow,
    setShowSpoilers,
} from '@/hooks/useSpoilers';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function SpoilerToggle({
    showSlug,
    initialEnabled,
}: {
    showSlug: string;
    initialEnabled: boolean;
}) {
    const [enabled, setEnabled] = useState(
        initialEnabled ?? resolveSpoilersForShow(showSlug),
    );

    return (
        <Flex align="center" gap={3}>
            <Text fontSize="sm" color="mea.muted">
                Show Spoilers
            </Text>
            <Switch.Root
                checked={enabled}
                aria-label="Show spoilers for this show"
                aria-pressed={enabled}
                colorPalette="yellow"
                onCheckedChange={(e) => {
                    const next = !!e.checked;
                    setEnabled(next);
                    setShowSpoilers(showSlug, next);
                    router.visit(appendSpoilersToUrl(window.location.pathname, next), {
                        preserveScroll: true,
                    });
                }}
            >
                <Switch.HiddenInput />
                <Switch.Control />
            </Switch.Root>
            {enabled ? (
                <Badge
                    bg="mea.red"
                    color="white"
                    px={3}
                    py={1}
                    borderRadius="md"
                    fontSize="xs"
                    fontWeight="bold"
                    textTransform="uppercase"
                >
                    Spoilers ON
                </Badge>
            ) : (
                <Badge
                    bg="transparent"
                    color="mea.gold"
                    borderWidth="1px"
                    borderColor="mea.gold"
                    px={3}
                    py={1}
                    borderRadius="md"
                    fontSize="xs"
                    fontWeight="bold"
                >
                    Spoilers OFF
                </Badge>
            )}
        </Flex>
    );
}
