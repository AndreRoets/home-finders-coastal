import AgentCard, { type Agent } from '@/components/public/agent-card';
import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';

export default function Agents({ agents = [] }: { agents?: Agent[] }) {
    return (
        <PublicLayout title="Agents" tone="light">
            <PageHero
                eyebrow="Meet the Team"
                title="Our Agents"
                image="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto max-w-7xl px-4 pt-6 pb-12 sm:px-6 lg:px-8">
                {agents.length === 0 ? (
                    <p className="rounded-sm border border-dashed border-slate-300 bg-slate-50 p-12 text-center text-neutral-500">
                        Our team details are being updated. Please check back soon.
                    </p>
                ) : (
                    <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                        {agents.map((agent) => (
                            <AgentCard key={agent.id} agent={agent} />
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
