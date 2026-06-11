import PageHero from '@/components/public/page-hero';
import PublicLayout from '@/layouts/public-layout';
import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="mt-10">
            <h2 className="text-2xl font-light text-navy">{title}</h2>
            <div className="mt-4 space-y-4 text-sm leading-relaxed text-neutral-600">{children}</div>
        </section>
    );
}

export default function PrivacyPolicy() {
    return (
        <PublicLayout title="Privacy Policy" tone="light">
            <PageHero eyebrow="Legal" title="Privacy Policy" />

            <article className="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="space-y-4 text-sm leading-relaxed text-neutral-600">
                    <p>
                        This Privacy Policy describes our policies and procedures on the collection, use and disclosure of your information
                        when you use our site and tells you about your privacy rights.
                    </p>
                    <p>
                        We use your Personal Data to provide and improve the service. By using our site, you agree to the collection and use
                        of information in accordance with this Privacy Policy.
                    </p>
                </div>

                <Section title="Personal Data">
                    <p>
                        While using our site, we may ask you to provide certain personally identifiable information that can be used to contact
                        or identify you. Personally identifiable information includes data like:
                    </p>
                    <ul className="list-disc space-y-1.5 pl-5">
                        <li>Email address</li>
                        <li>First name and last name</li>
                        <li>Phone number</li>
                        <li>Address, Province, Postal code, Suburb</li>
                        <li>Usage Data</li>
                    </ul>
                </Section>

                <Section title="Usage Data">
                    <p>Usage Data is collected automatically when using our site.</p>
                    <p>
                        Usage Data may include information such as your device's IP address, browser type, browser version, the pages of our
                        site that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and
                        other diagnostic data.
                    </p>
                    <p>
                        When you access the site by or through a mobile device, we may collect certain information automatically, including,
                        but not limited to, the type of mobile device you use, your mobile device unique ID, the IP address of your mobile
                        device, your mobile operating system, the type of mobile Internet browser you use, unique device identifiers and other
                        diagnostic data.
                    </p>
                    <p>
                        We may also collect information that your browser sends whenever you visit our site or when you access the site by or
                        through a mobile device.
                    </p>
                </Section>

                <Section title="Tracking Technologies and Cookies">
                    <p>
                        We use Cookies and similar tracking technologies to track the activity on our site and store certain information. A
                        cookie is a small file placed on your device. Unless you have adjusted your browser setting so that it will refuse
                        Cookies, our site may use Cookies.
                    </p>
                </Section>

                <Section title="Use of Your Personal Data">
                    <ul className="list-disc space-y-1.5 pl-5">
                        <li>To provide and maintain our site, including to monitor the usage of our site.</li>
                        <li>
                            To contact you by email, telephone, SMS, WhatsApp or other equivalent forms of electronic communication regarding
                            updates or informative communications related to real estate services.
                        </li>
                        <li>
                            To provide you with news and general information about real estate related topics unless you decide not to receive
                            such information and Opt-Out.
                        </li>
                        <li>
                            We may use your information for other purposes, such as data analysis, identifying usage trends, determining the
                            effectiveness of our promotional campaigns and to evaluate and improve our site, services, marketing and your
                            experience.
                        </li>
                    </ul>
                </Section>

                <Section title="Legal requirements">
                    <p>
                        Under certain circumstances, the company may be required to disclose your Personal Data if required to do so by law or
                        in response to valid requests by public authorities (e.g. a court or a law enforcement).
                    </p>
                </Section>

                <Section title="Security of Your Personal Data">
                    <p>
                        The security of your Personal Data is important to us — that is why we make use of industry leading security and hosting
                        solutions to protect your Personal Data.
                    </p>
                </Section>

                <Section title="Links to Other Websites">
                    <p>
                        Our Service may contain links to other websites that are not operated by us. If you click on a third-party link, you
                        will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you
                        visit.
                    </p>
                    <p>
                        We have no control over and assume no responsibility for the content, privacy policies or practices of any third-party
                        sites or services.
                    </p>
                </Section>

                <Section title="Changes to this Privacy Policy">
                    <p>
                        We may update our Privacy Policy from time to time which will be reflected in the "Last updated" date at the bottom of
                        this Privacy Policy.
                    </p>
                    <p>
                        You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective
                        when they are posted on this page. Your continued use of the site after we post any modifications will be seen as your
                        acknowledgment and your consent to abide by and be bound by the modified Privacy Policy.
                    </p>
                </Section>

                <Section title="Information Regulator">
                    <p>
                        The Information Regulator (South Africa) is an independent body empowered to monitor and enforce compliance by public
                        and private bodies with the provisions of the Promotion of Access to Information Act, 2000 (Act 2 of 2000), and the
                        Protection of Personal Information Act, 2013 (Act 4 of 2013).
                    </p>
                    <p>
                        To report any violation of POPIA (Protection of Personal Information) or PAIA (Promotion of Access to Information) you
                        can use the following resources:
                    </p>
                    <ul className="list-disc space-y-1.5 pl-5">
                        <li>
                            POPIA:{' '}
                            <a
                                href="https://inforegulator.org.za/popia-forms/"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-marine underline transition-colors hover:text-marine/80"
                            >
                                https://inforegulator.org.za/popia-forms/
                            </a>
                        </li>
                        <li>
                            PAIA:{' '}
                            <a
                                href="https://inforegulator.org.za/paia-forms/"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-marine underline transition-colors hover:text-marine/80"
                            >
                                https://inforegulator.org.za/paia-forms/
                            </a>
                        </li>
                    </ul>
                </Section>

                <p className="mt-10 text-sm text-neutral-500">Last Updated: 1 October 2025</p>

                <p className="mt-6 text-sm leading-relaxed text-neutral-600">
                    If you have any questions about this Privacy Policy, please{' '}
                    <Link href={route('contact')} className="text-marine underline transition-colors hover:text-marine/80">
                        contact us
                    </Link>
                    .
                </p>
            </article>
        </PublicLayout>
    );
}
