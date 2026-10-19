/**
 * The current public page's server-resolved SEO title (the admin-managed meta
 * title). It is already the full document title, so the Inertia title callback
 * must render it verbatim rather than suffixing the app name again.
 */
let verbatimTitle: string | null = null;

/**
 * Register a title to be rendered exactly as given. Returns it for inline use.
 */
export function markVerbatimTitle(title: string): string {
    verbatimTitle = title;

    return title;
}

/**
 * The Inertia `title` callback: server SEO titles pass through untouched;
 * any other page title (admin, auth, settings) gets the "- App" suffix.
 */
export function formatDocumentTitle(title: string, appName: string): string {
    if (!title) {
        return appName;
    }

    return title === verbatimTitle || title === appName ? title : `${title} - ${appName}`;
}
