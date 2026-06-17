import { Box, Flex, Text } from '@chakra-ui/react';
import RatingStars from './RatingStars';

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
    participant_line?: string;
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
}: {
    match: MatchData;
    spoilersEnabled: boolean;
}) {
    const resultLine = spoilersEnabled ? formatResult(match) : null;
    const participantLine = match.participant_line ?? formatParticipants(match.participants ?? []);

    return (
        <Box
            as="li"
            bg="mea.surfaceAlt"
            borderWidth="1px"
            borderColor="mea.border"
            borderRadius="md"
            p={4}
        >
            <Flex justify="space-between" align="start" gap={4} flexWrap="wrap">
                <Box flex={1} minW={0}>
                    {match.title_name ? (
                        <Text fontSize="xs" color="mea.gold" mb={1} fontWeight="semibold" textTransform="uppercase">
                            {match.title_name}
                        </Text>
                    ) : null}

                    <Box minH="2.5rem" mb={1}>
                        {spoilersEnabled && resultLine ? (
                            <Text fontWeight="semibold">
                                {resultLine}
                            </Text>
                        ) : (
                            <Text fontWeight="medium">
                                {participantLine}
                            </Text>
                        )}
                    </Box>

                    <Text fontSize="sm" color="mea.muted">
                        {match.match_type.replace(/_/g, ' ')}
                    </Text>

                    <Box minH="1.25rem" mt={1}>
                        {spoilersEnabled && match.duration_seconds ? (
                            <Text fontSize="sm" color="mea.muted">
                                ({formatDuration(match.duration_seconds)})
                            </Text>
                        ) : null}
                    </Box>
                </Box>

                {match.is_rateable ? (
                    <Flex direction="column" align="flex-end" gap={2}>
                        <RatingStars
                            rateableType="match"
                            rateableId={match.id}
                            label="Rate Match"
                        />
                    </Flex>
                ) : null}
            </Flex>
        </Box>
    );
}
