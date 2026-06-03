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
    {
        title: 'Home',
        routeName: 'home',
        children: [
            { title: 'Classic', routeName: 'home', description: 'The original coastal landing page' },
            { title: 'Luxe Editorial', routeName: 'home.luxe', description: 'Dark, premium magazine style' },
            { title: 'Vibrant Modern', routeName: 'home.vibrant', description: 'Bold gradients & glassmorphism' },
            { title: 'Minimal', routeName: 'home.minimal', description: 'Clean architectural minimalism' },
            { title: 'Portal', routeName: 'home.portal', description: 'Industry best-practice search portal' },
            { title: 'Motion', routeName: 'home.motion', description: 'Scroll-driven animated story' },
            { title: 'Coastal', routeName: 'home.coastal', description: 'Beach theme with animated waves' },
        ],
    },
    { title: 'For Sale', routeName: 'for-sale' },
    { title: 'To Rent', routeName: 'to-rent' },
    { title: 'HFC Exclusive', routeName: 'hfc-exclusive' },
    { title: 'Sold', routeName: 'sold' },
    { title: 'Agents', routeName: 'agents' },
    { title: 'Contact', routeName: 'contact' },
];
