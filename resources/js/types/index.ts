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

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: { success?: string | null; error?: string | null };
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
