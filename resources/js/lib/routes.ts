/**
 * Centralised URL helpers for the public site. Keeping the "link to an agent"
 * URL in one place means every card, testimonial and listing links to the same
 * agent detail route, and the route name only appears once.
 */

/**
 * URL of an agent's detail page.
 */
export function agentUrl(id: number | string): string {
    return route('agents.show', id);
}
