import BondCalculator from '@/components/public/bond-calculator';
import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';

export default function BondCalculatorPage() {
    return (
        <PublicLayout title="Bond Calculator" tone="light">
            <PageHero
                eyebrow="Plan Your Purchase"
                title="Bond Calculator"
                description="Estimate your monthly bond repayment, the total you'll repay over the term, and how a rate hike would affect affordability."
                image="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=2400&q=70"
            />

            <section className="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
                <BondCalculator />
            </section>
        </PublicLayout>
    );
}
