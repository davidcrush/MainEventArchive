import { Box, Button, Flex, Text } from '@chakra-ui/react';
import RatingStars from './RatingStars';
import ShowThumbnail from './ShowThumbnail';

export interface Participant {
    id: number;
    name: string;
    side: number;
    sort_order: number;
}

export interface MatchData {
    id: number;
    card_order: number;
    match_type: string;
    title_name?: string | null;
    is_rateable: boolean;
    participants?: Participant[];
    winner_side?: number | null;
    finish?: string | null;
    duration_seconds?: number | null;
    rating_average?: number;
    rating_count?: number;
}

function formatParticipants(participants: Participant[]): string {
    const sides = participants.reduce<Record<number, string[]>>((acc, p) => {
        acc[p.side] = acc[p.side] ?? [];
        acc[p.side].push(p.name);
        return acc;
    }, {});

    return Object.values(sides)
        .map((names) => names.join(' & '))
        .join(' vs ');
}

function formatResult(match: MatchData): string | null {
    if (!match.winner_side || !match.participants?.length) {
        return null;
    }

    const sides = match.participants.reduce<Record<number, string[]>>((acc, p) => {
        acc[p.side] = acc[p.side] ?? [];
        acc[p.side].push(p.name);
        return acc;
    }, {});

    const winnerNames = sides[match.winner_side];
    if (!winnerNames) {
        return null;
    }

    const loserSides = Object.keys(sides)
        .map(Number)
        .filter((side) => side !== match.winner_side);
    const loserNames = loserSides.flatMap((side) => sides[side]);

    const winner = winnerNames.join(' & ');
    const loser = loserNames.join(' & ');
    const finish = match.finish ? ` via ${match.finish.replace(/_/g, ' ')}` : '';

    if (loser) {
        return `${winner} def. ${loser}${finish}`;
    }

    return `${winner} won${finish}`;
}

function formatDuration(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;

    return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

export default function MatchRow({
    match,
    spoilersEnabled,
    promotionName,
}: {
    match: MatchData;
    spoilersEnabled: boolean;
    promotionName?: string;
}) {
    const resultLine = spoilersEnabled ? formatResult(match) : null;
    const participantLine = formatParticipants(match.participants ?? []);

    return (
        <Box
            as="li"
            bg="mea.surfaceAlt"
            borderWidth="1px"
            borderColor={spoilersEnabled ? 'mea.red' : 'mea.border'}
            borderRadius="md"
            p={4}
        >
            <Flex justify="space-between" align="start" gap={4} flexWrap="wrap">
                <Flex gap={3} flex={1} minW={0}>
                    {spoilersEnabled ? (
                        <ShowThumbnail promotionName={promotionName} size="sm" />
                    ) : null}
                    <Box flex={1} minW={0}>
                        {match.title_name ? (
                            <Text fontSize="xs" color="mea.gold" mb={1} fontWeight="semibold" textTransform="uppercase">
                                {match.title_name}
                            </Text>
                        ) : null}

                        {spoilersEnabled && resultLine ? (
                            <Text fontWeight="semibold" mb={1}>
                                {resultLine}
                            </Text>
                        ) : (
                            <Text fontWeight="medium" mb={1}>
                                {participantLine}
                            </Text>
                        )}

                        <Text fontSize="sm" color="mea.muted">
                            {match.match_type.replace(/_/g, ' ')}
                        </Text>

                        {spoilersEnabled && match.duration_seconds ? (
                            <Text fontSize="sm" color="mea.muted" mt={1}>
                                ({formatDuration(match.duration_seconds)})
                            </Text>
                        ) : null}
                    </Box>
                </Flex>

                <Flex direction="column" align="flex-end" gap={2}>
                    {spoilersEnabled ? (
                        <Button
                            size="xs"
                            variant="outline"
                            borderColor="mea.border"
                            color="mea.muted"
                            disabled
                            cursor="not-allowed"
                            opacity={0.6}
                        >
                            Jump to match
                        </Button>
                    ) : null}

                    {match.is_rateable ? (
                        <RatingStars
                            rateableType="match"
                            rateableId={match.id}
                            average={match.rating_average}
                            count={match.rating_count}
                            label={spoilersEnabled ? 'Rate Match' : undefined}
                        />
                    ) : null}
                </Flex>
            </Flex>
        </Box>
    );
}
