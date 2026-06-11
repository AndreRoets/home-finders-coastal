import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';

export default function Terms() {
    return (
        <PublicLayout title="Terms" tone="light">
            <PageHero eyebrow="Legal" title="Terms" />

            <article className="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="space-y-4 text-sm leading-relaxed text-neutral-600">
                    <p>
                        These Terms govern your use of our website. Our full terms of service will be published here shortly.
                    </p>
                    <p>
                        If you have any questions in the meantime, please{' '}
                        <Link href={route('contact')} className="text-marine underline transition-colors hover:text-marine/80">
                            contact us
                        </Link>
                        .
                    </p>
                </div>
            </article>
        </PublicLayout>
    );
}
