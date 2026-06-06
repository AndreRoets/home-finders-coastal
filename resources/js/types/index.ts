import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

/** Public contact details pulled from CoreX. Keys are only present when set. */
export interface AgencyContact {
    email?: string;
    phone?: string;
    /** Whitespace-stripped phone for the tel: href; `phone` holds the display value. */
    phoneHref?: string;
    address?: string;
}

export interface AgencyOpenHours {
    days: string;
    hours: string;
}

/** Agency profile from CoreX, shared with the footer/header on every page. */
export interface Agency {
    name: string | null;
    tradingName: string | null;
    logo: string | null;
    branding: Partial<Record<'sidebarColor' | 'iconColor' | 'defaultColor' | 'buttonColor', string>> | null;
    contact: AgencyContact | null;
    /** Absolute social profile URLs, keyed by platform; only present networks appear. */
    socials: Partial<Record<'facebook' | 'instagram' | 'linkedin' | 'youtube', string>>;
    openHours: AgencyOpenHours[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success?: string | null; error?: string | null };
    /** Agency contact/social/hours from CoreX; null on a fetch error. */
    agency: Agency | null;
    [key: string]: unknown;
}

export interface PageRecord {
    id: number;
    key: string;
    name: string;
    slug: string;
    is_active: boolean;
    meta_title: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    canonical_url: string | null;
    robots_index: boolean;
    robots_follow: boolean;
    og_title: string | null;
    og_description: string | null;
    og_image: string | null;
    og_type: string | null;
    twitter_card: string | null;
    twitter_title: string | null;
    twitter_description: string | null;
    twitter_image: string | null;
    json_ld: string | null;
    head_scripts: string | null;
    sitemap_priority: string;
    sitemap_frequency: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
