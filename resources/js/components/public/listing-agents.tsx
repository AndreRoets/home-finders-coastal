import { agentUrl } from '@/lib/routes';
import { Link } from '@inertiajs/react';
import { type Listing, type ListingAgent } from './listings';

/**
 * The agent(s) attributed to a listing, shown beneath a property card. A
 * property may be co-listed, so every agent is rendered and links to their
 * profile. Falls back to the legacy single `agent` field and renders nothing
 * when the listing has no attributed agent.
 */
export default function ListingAgents({ listing }: { listing: Listing }) {
    const agents: ListingAgent[] = listing.agents?.length ? listing.agents : listing.agent ? [listing.agent] : [];

    if (agents.length === 0) {
        return null;
    }

    return (
        <div className="mt-4 space-y-3 border-t border-slate-200 pt-4">
            {agents.map((agent) => (
                <Link
                    key={agent.id}
                    href={agentUrl(agent.id)}
                    className="flex items-center gap-3 text-sm text-neutral-600 transition-colors hover:text-marine"
                >
                    <img src={agent.photo} alt={agent.name} className="h-8 w-8 rounded-full object-cover object-top" />
                    <span>
                        <span className="block leading-tight">{agent.name}</span>
                        {agent.designation && <span className="block text-xs leading-tight text-neutral-400">{agent.designation}</span>}
                    </span>
                </Link>
            ))}
        </div>
    );
}
