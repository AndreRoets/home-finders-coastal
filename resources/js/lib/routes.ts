/**
 * Centralised URL helpers for the public site. Keeping the "link to an agent"
 * URL in one place means every card, testimonial and listing links to the same
 * agent detail route, and the route name only appears once.
 */

/**
 * Build a name-based URL slug, mirroring Laravel's Str::slug for ASCII names
 * (e.g. "Elize Reichel" → "elize-reichel"). Diacritics are stripped and
 * punctuation dropped so it matches the slug the backend resolves the agent by.
 */
function slugify(value: string): string {
    return value
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]+/g, '')
        .trim()
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * URL of an agent's detail page. Uses the agent's name as the path segment
 * (e.g. /agents/elize-reichel), falling back to the id when the name yields no
 * slug. The backend resolves the slug back to the CoreX id.
 */
export function agentUrl(agent: { id: number | string; name: string }): string {
    return route('agents.show', slugify(agent.name) || String(agent.id));
}

/**
 * URL of an article's full page.
 */
export function articleUrl(slug: string): string {
    return route('articles.show', slug);
}
