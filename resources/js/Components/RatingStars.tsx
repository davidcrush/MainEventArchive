import { Button, Flex, Text } from '@chakra-ui/react';
import { router, usePage } from '@inertiajs/react';

function StarDisplay({ average, count }: { average?: number; count?: number }) {
    const filled = Math.round(average ?? 0);

    return (
        <Flex align="center" gap={1}>
            {[1, 2, 3, 4, 5].map((star) => (
                <Text
                    key={star}
                    fontSize="sm"
                    color={star <= filled ? 'mea.gold' : 'mea.border'}
                    lineHeight={1}
                >
                    ★
                </Text>
            ))}
            {count !== undefined && count > 0 && average !== undefined ? (
                <Text fontSize="xs" color="mea.muted" ml={1}>
                    {average.toFixed(1)} ({count})
                </Text>
            ) : null}
        </Flex>
    );
}

export default function RatingStars({
    rateableType,
    rateableId,
    average,
    count,
    compact = false,
    label,
}: {
    rateableType?: 'show' | 'match';
    rateableId?: number;
    average?: number;
    count?: number;
    compact?: boolean;
    label?: string;
}) {
    const { auth } = usePage().props as { auth: { user?: { email_verified_at?: string } } };
    const canRate = !!auth.user?.email_verified_at && rateableType && rateableId;

    if (compact) {
        if (!count || count === 0) {
            return (
                <Text fontSize="xs" color="mea.muted">
                    No ratings yet
                </Text>
            );
        }

        return <StarDisplay average={average} count={count} />;
    }

    return (
        <Flex direction="column" gap={2}>
            {label ? (
                <Text fontSize="sm" fontWeight="semibold" color="white">
                    {label}
                </Text>
            ) : null}

            {count !== undefined && count > 0 && average !== undefined ? (
                <StarDisplay average={average} count={count} />
            ) : (
                <Text fontSize="sm" color="mea.muted">
                    No MEA ratings yet
                </Text>
            )}

            {canRate ? (
                <Flex gap={1}>
                    {[1, 2, 3, 4, 5].map((star) => (
                        <Button
                            key={star}
                            size="xs"
                            variant="ghost"
                            color="mea.gold"
                            minW="auto"
                            px={1}
                            onClick={() =>
                                router.post(route('ratings.store'), {
                                    rateable_type: rateableType,
                                    rateable_id: rateableId,
                                    stars: star,
                                })
                            }
                            aria-label={`Rate ${star} stars`}
                            _hover={{ color: 'mea.goldBright', bg: 'whiteAlpha.100' }}
                        >
                            ★
                        </Button>
                    ))}
                </Flex>
            ) : rateableType ? (
                <Text fontSize="xs" color="mea.muted">
                    Sign in and verify email to rate
                </Text>
            ) : null}
        </Flex>
    );
}
