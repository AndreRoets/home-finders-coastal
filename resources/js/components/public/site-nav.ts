import { type Agency } from '@/types';

export interface SiteNavChild {
    title: string;
    routeName: string;
    description?: string;
}

export interface SiteNavItem {
    title: string;
    routeName: string;
    children?: SiteNavChild[];
    /** When set, the item only renders if the matching agency feature toggle is on. */
    gatedBy?: keyof NonNullable<Agency['show']>;
}

/**
 * Primary public navigation for the Home Finders Coastal site.
 * Each item maps to a named Inertia route registered in routes/web.php.
 * Items with `children` render as a dropdown menu. Items with `gatedBy` only
 * appear when the agency has that feature enabled (e.g. "Offices" requires
 * `show.branches`).
 */
export const siteNavItems: SiteNavItem[] = [
    { title: 'Home', routeName: 'home' },
    { title: 'For Sale', routeName: 'for-sale' },
    { title: 'To Rent', routeName: 'to-rent' },
    { title: 'HFC Exclusive', routeName: 'hfc-exclusive' },
    { title: 'Sold', routeName: 'sold' },
    { title: 'Agents', routeName: 'agents' },
    { title: 'Branches', routeName: 'branches', gatedBy: 'branches' },
    { title: 'Bond Calculator', routeName: 'bond-calculator' },
    { title: 'Contact', routeName: 'contact' },
];

/**
 * The nav items visible for the given agency — drops any item whose `gatedBy`
 * feature toggle is off (or whose agency is unavailable).
 */
export function visibleNavItems(agency: Agency | null): SiteNavItem[] {
    return siteNavItems.filter((item) => !item.gatedBy || agency?.show?.[item.gatedBy] === true);
}
