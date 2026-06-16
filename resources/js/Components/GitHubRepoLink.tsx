import { Image, Link, Text } from '@chakra-ui/react';
import githubMark from '../../images/third-party/github-mark.svg';

const GITHUB_REPO_URL = 'https://github.com/davidcrush/MainEventArchive';

export default function GitHubRepoLink() {
    return (
        <Link
            href={GITHUB_REPO_URL}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="View source code on GitHub (opens in new tab)"
            display="inline-flex"
            alignItems="center"
            gap={1.5}
            fontSize="sm"
            color="mea.muted"
            _hover={{ color: 'mea.gold', textDecoration: 'none' }}
        >
            <Image src={githubMark} alt="" h="16px" w="16px" aria-hidden />
            <Text>Source</Text>
        </Link>
    );
}
