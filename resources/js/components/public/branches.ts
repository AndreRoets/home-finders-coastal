import { type Agent } from './agent-card';

/**
 * Generic fallback shown when a branch has no logo of its own and the agency has
 * no logo to fall back to either (the server already substitutes the agency logo
 * when the branch one is blank — see BranchMapper).
 */
export const DEFAULT_BRANCH_IMAGE = 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1200&q=70';

/** A branch option in the "All + branches" filter shown on the listing/agent pages. */
export interface BranchOption {
    id: number | string;
    name: string;
}

/**
 * A branch (physical office) as returned by the CoreX-backed BranchMapper.
 * Optional fields are null when the branch leaves them blank (logo/phone/email/
 * address already fall back to the agency defaults server-side).
 */
export interface Branch {
    id: number | string;
    tradingName: string;
    tagline: string | null;
    address: string | null;
    phone: string | null;
    phoneHref: string | null;
    phoneLabel: string | null;
    phoneSecondary: string | null;
    phoneSecondaryHref: string | null;
    phoneSecondaryLabel: string | null;
    email: string | null;
    ppraNumber: string | null;
    logo: string | null;
    agentCount: number;
    listingCount: number;
    agents: Agent[];
}
