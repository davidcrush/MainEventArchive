const STORAGE_KEY = 'mea.ratings';

type RateableType = 'show' | 'match';

type RatingsStore = Record<string, number>;

function ratingKey(rateableType: RateableType, rateableId: number): string {
    return `${rateableType}:${rateableId}`;
}

function readStore(): RatingsStore {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return {};
        }

        const parsed = JSON.parse(raw) as unknown;
        if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
            return {};
        }

        return Object.fromEntries(
            Object.entries(parsed).filter(
                ([, value]) => typeof value === 'number' && value >= 1 && value <= 5,
            ),
        );
    } catch {
        return {};
    }
}

function writeStore(store: RatingsStore): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
}

export function getLocalRating(rateableType: RateableType, rateableId: number): number | null {
    const stars = readStore()[ratingKey(rateableType, rateableId)];

    return stars ?? null;
}

export function setLocalRating(rateableType: RateableType, rateableId: number, stars: number): void {
    const store = readStore();
    store[ratingKey(rateableType, rateableId)] = stars;
    writeStore(store);
}
