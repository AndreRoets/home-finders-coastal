import { type Agent } from './agent-card';

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
