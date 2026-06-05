export interface SiteNavChild {
    title: string;
    routeName: string;
    description?: string;
}

export interface SiteNavItem {
    title: string;
    routeName: string;
    children?: SiteNavChild[];
}

/**
 * Primary public navigation for the Home Finders Coastal site.
 * Each item maps to a named Inertia route registered in routes/web.php.
 * Items with `children` render as a dropdown menu.
 */
export const siteNavItems: SiteNavItem[] = [
    { title: 'Home', routeName: 'home' },
    { title: 'For Sale', routeName: 'for-sale' },
    { title: 'To Rent', routeName: 'to-rent' },
    { title: 'HFC Exclusive', routeName: 'hfc-exclusive' },
    { title: 'Sold', routeName: 'sold' },
    { title: 'Agents', routeName: 'agents' },
    { title: 'Contact', routeName: 'contact' },
];
