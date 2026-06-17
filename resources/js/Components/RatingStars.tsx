import { Button, Flex, IconButton, Text } from '@chakra-ui/react';
import { getLocalRating, setLocalRating } from '@/lib/localRatings';
import { useEffect, useState } from 'react';

function UserRatingDisplay({ stars, compact = false }: { stars: number; compact?: boolean }) {
    return (
        <Flex align="center" gap={1}>
            {[1, 2, 3, 4, 5].map((star) => (
                <Text
                    key={star}
                    fontSize={compact ? 'xs' : 'sm'}
                    color={star <= stars ? 'mea.gold' : 'mea.border'}
                    lineHeight={1}
                >
                    ★
                </Text>
            ))}
        </Flex>
    );
}

export default function RatingStars({
    rateableType,
    rateableId,
    compact = false,
    label,
}: {
    rateableType: 'show' | 'match';
    rateableId: number;
    compact?: boolean;
    label?: string;
}) {
    const [userRating, setUserRating] = useState<number | null>(() =>
        getLocalRating(rateableType, rateableId),
    );
    const [isEditing, setIsEditing] = useState(false);

    useEffect(() => {
        setUserRating(getLocalRating(rateableType, rateableId));
        setIsEditing(false);
    }, [rateableType, rateableId]);

    if (compact) {
        if (userRating == null) {
            return null;
        }

        return <UserRatingDisplay stars={userRating} compact />;
    }

    const showRatingInput = userRating == null || isEditing;

    const handleRate = (stars: number) => {
        setLocalRating(rateableType, rateableId, stars);
        setUserRating(stars);
        setIsEditing(false);
    };

    return (
        <Flex direction="column" gap={2}>
            {label ? (
                <Text fontSize="sm" fontWeight="semibold" color="white">
                    {label}
                </Text>
            ) : null}

            {userRating != null && !isEditing ? (
                <Flex align="center" gap={1}>
                    <UserRatingDisplay stars={userRating} />
                    <IconButton
                        aria-label="Edit rating"
                        size="xs"
                        variant="ghost"
                        color="mea.muted"
                        minW="auto"
                        h="auto"
                        px={1}
                        fontSize="xs"
                        onClick={() => setIsEditing(true)}
                        _hover={{ color: 'mea.gold', bg: 'whiteAlpha.100' }}
                    >
                        ✎
                    </IconButton>
                </Flex>
            ) : null}

            {showRatingInput ? (
                <Flex gap={1}>
                    {[1, 2, 3, 4, 5].map((star) => (
                        <Button
                            key={star}
                            size="xs"
                            variant="ghost"
                            color="mea.gold"
                            minW="auto"
                            px={1}
                            onClick={() => handleRate(star)}
                            aria-label={`Rate ${star} stars`}
                            _hover={{ color: 'mea.goldBright', bg: 'whiteAlpha.100' }}
                        >
                            ★
                        </Button>
                    ))}
                </Flex>
            ) : null}
        </Flex>
    );
}
