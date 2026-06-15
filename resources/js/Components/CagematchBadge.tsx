import { Link } from '@chakra-ui/react';

export default function CagematchBadge({ url }: { url: string }) {
    return (
        <Link
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            display="inline-flex"
            alignItems="center"
            px={5}
            py={2}
            bg="mea.red"
            color="white"
            borderRadius="md"
            fontWeight="semibold"
            fontSize="sm"
            _hover={{ opacity: 0.9, textDecoration: 'none' }}
        >
            View on Cagematch
        </Link>
    );
}
