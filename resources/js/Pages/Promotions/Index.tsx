import AppLayout from '@/Layouts/AppLayout';
import PromotionCard, { PromotionCardData } from '@/Components/PromotionCard';
import { Box, Flex, Heading, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';

export default function PromotionsIndex({
    promotions,
}: {
    promotions: PromotionCardData[];
}) {
    return (
        <AppLayout>
            <Head title="Promotions" />
            <Heading size="xl" mb={2} fontWeight="bold">
                Promotions
            </Heading>
            <Text color="mea.muted" mb={8} fontSize="md">
                Wrestling companies in the Main Event Archive catalog. Select a promotion to browse its shows.
            </Text>
            {promotions.length === 0 ? (
                <Box
                    bg="mea.surface"
                    borderWidth="1px"
                    borderColor="mea.border"
                    borderRadius="xl"
                    p={8}
                    textAlign="center"
                >
                    <Text color="mea.muted">No promotions are listed yet.</Text>
                </Box>
            ) : (
                <Flex direction="column" gap={5} maxW="3xl" mx="auto">
                    {promotions.map((promotion) => (
                        <PromotionCard key={promotion.id} promotion={promotion} />
                    ))}
                </Flex>
            )}
        </AppLayout>
    );
}
