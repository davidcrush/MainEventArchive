import AppLayout from '@/Layouts/AppLayout';
import { Box, Heading, Link, Text } from '@chakra-ui/react';
import { Head } from '@inertiajs/react';

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <Box>
            <Heading size="sm" color="white" mb={2}>
                {title}
            </Heading>
            {children}
        </Box>
    );
}

function BulletList({ children }: { children: React.ReactNode }) {
    return (
        <Box as="ul" mt={2} pl={5} display="flex" flexDirection="column" gap={1}>
            {children}
        </Box>
    );
}

function BulletItem({ children }: { children: React.ReactNode }) {
    return (
        <Box as="li" color="mea.muted">
            {children}
        </Box>
    );
}

export default function KnownLimitations() {
    return (
        <AppLayout>
            <Head title="Known Limitations" />
            <Heading size="lg" mb={2}>
                Known Limitations
            </Heading>
            <Text color="mea.muted" mb={6} maxW="3xl">
                Main Event Archive is under active development. This page documents gaps and
                trade-offs we already know about — so a missing show or match is less likely to feel
                like a surprise bug. We update it as the catalog grows.
            </Text>

            <Box display="flex" flexDirection="column" gap={6} color="mea.muted" maxW="3xl">
                <Section title="What the catalog includes today">
                    <Text>
                        We are building a spoiler-safe index of major wrestling events, not a
                        complete mirror of every promotion feed.
                    </Text>
                    <BulletList>
                        <BulletItem>
                            <strong style={{ color: 'white' }}>WCW</strong> — pay-per-views (1990s
                            onward), Clash of the Champions, and Monday Nitro episodes (in progress).
                        </BulletItem>
                        <BulletItem>
                            <strong style={{ color: 'white' }}>WWE</strong> — pay-per-view and
                            premium live events (including many NXT TakeOver / NXT PLE entries). We
                            do <strong style={{ color: 'white' }}>not</strong> yet catalog weekly{' '}
                            <strong style={{ color: 'white' }}>Raw</strong> or{' '}
                            <strong style={{ color: 'white' }}>SmackDown</strong> episodes.
                        </BulletItem>
                        <BulletItem>
                            Other promotions (AEW, TNA, etc.) are not in scope yet unless listed on
                            the Promotions page.
                        </BulletItem>
                    </BulletList>
                </Section>

                <Section title="One main event per calendar date">
                    <Text>
                        Our WWE PPV seed data is keyed by <strong style={{ color: 'white' }}>air
                        date</strong>. When two separate events share the same date, we currently
                        keep one catalog row — usually the main-roster pay-per-view.
                    </Text>
                    <Text mt={2}>
                        <strong style={{ color: 'white' }}>Example:</strong> On WrestleMania
                        weekend, WWE often runs <em>NXT Stand &amp; Deliver</em> the same day as
                        WrestleMania. We list WrestleMania; Stand &amp; Deliver for 2022–2025 is
                        intentionally omitted for now. Stand &amp; Deliver <em>is</em> listed when it
                        has its own date (e.g. 2021 Nights 1 &amp; 2, 2026).
                    </Text>
                    <Text mt={2}>
                        YouTube may still host those missing events; we simply do not have a show
                        page to attach them to yet.
                    </Text>
                </Section>

                <Section title="Match cards and metadata">
                    <BulletList>
                        <BulletItem>
                            Match cards are enriched from Wikipedia when a matching article exists.
                            Newer specials, future-dated events, or oddly titled pages may have a
                            show shell but no card until we add a manual override or the source
                            page matures.
                        </BulletItem>
                        <BulletItem>
                            Multi-venue events (e.g. some Saudi or multi-city specials) stay as
                            text on the show — we do not link them to a single venue record.
                        </BulletItem>
                        <BulletItem>
                            With spoilers off, surprise entrants and some battle royal pairings are
                            hidden by design. Turn spoilers on to see full results.
                        </BulletItem>
                    </BulletList>
                </Section>

                <Section title="Where to watch">
                    <BulletList>
                        <BulletItem>
                            <strong style={{ color: 'white' }}>YouTube</strong> — Linked when we
                            can match a show to an official full-event upload. NXT premium events
                            are matched from a dedicated playlist; not every catalog row has a link
                            yet.
                        </BulletItem>
                        <BulletItem>
                            <strong style={{ color: 'white' }}>Netflix</strong> — Many WWE PPVs
                            open a Netflix <em>search</em> for the show title rather than a direct
                            episode link, so results may vary. A &quot;Video&quot; badge means we
                            have a catalog link on file, not a guarantee the title streams in your
                            region.
                        </BulletItem>
                        <BulletItem>
                            Per-match timestamps and in-page playback are planned for a later
                            release; show pages are index cards today.
                        </BulletItem>
                    </BulletList>
                </Section>

                <Section title="Ratings and accounts">
                    <Text>
                        Star ratings you enter are stored in your browser for now. They are not
                        synced to an account or shared as community averages yet. Sign-in features
                        for saved ratings are on the roadmap.
                    </Text>
                </Section>

                <Section title="Something still wrong?">
                    <Text>
                        If you do not see an issue here, it may be a bug or a gap we have not
                        catalogued yet. The project is open source — see{' '}
                        <Link href={route('attribution')} color="mea.gold">
                            Attribution
                        </Link>{' '}
                        for data sources, or open an issue on GitHub from the footer link.
                    </Text>
                </Section>
            </Box>
        </AppLayout>
    );
}
