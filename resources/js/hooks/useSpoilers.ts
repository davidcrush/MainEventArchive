const GLOBAL_KEY = 'mea.spoilers.global';

export function showSpoilerKey(slug: string): string {
    return `mea.spoilers.show.${slug}`;
}

export function getGlobalSpoilers(): boolean {
    return localStorage.getItem(GLOBAL_KEY) === 'true';
}

export function setGlobalSpoilers(enabled: boolean): void {
    localStorage.setItem(GLOBAL_KEY, enabled ? 'true' : 'false');
}

export function getShowSpoilers(slug: string): boolean | null {
    const value = localStorage.getItem(showSpoilerKey(slug));
    if (value === null) {
        return null;
    }

    return value === 'true';
}

export function setShowSpoilers(slug: string, enabled: boolean): void {
    localStorage.setItem(showSpoilerKey(slug), enabled ? 'true' : 'false');
}

export function resolveSpoilersForShow(slug: string): boolean {
    const perShow = getShowSpoilers(slug);
    if (perShow !== null) {
        return perShow;
    }

    return getGlobalSpoilers();
}

export function spoilerQueryParam(enabled: boolean): string {
    return enabled ? '?spoilers=1' : '';
}

export function appendSpoilersToUrl(url: string, enabled: boolean): string {
    if (!enabled) {
        return url.split('?')[0];
    }

    const base = url.split('?')[0];
    return `${base}?spoilers=1`;
}
