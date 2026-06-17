import { Link, Text } from '@chakra-ui/react';

const DEVELOPER_URL = 'https://davidcrush.dev';

export default function BuiltByLink() {
    return (
        <Text fontSize="sm" color="mea.muted">
            Built by{' '}
            <Link
                href={DEVELOPER_URL}
                target="_blank"
                rel="noopener noreferrer"
                color="inherit"
                _hover={{ color: 'mea.gold', textDecoration: 'none' }}
            >
                David Crush
            </Link>
        </Text>
    );
}
