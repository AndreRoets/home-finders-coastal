/**
 * The XSRF token Laravel sets as a cookie, ready for a fetch() header — the
 * upload endpoint is called outside Inertia (it answers with JSON), so the
 * token Inertia's own requests send automatically has to be added by hand.
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Upload an image to the admin media endpoint and resolve with its public URL.
 */
export async function uploadImage(file: File): Promise<string> {
    const body = new FormData();
    body.append('file', file);

    const response = await fetch('/admin/media', {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(payload?.errors?.file?.[0] ?? payload?.message ?? 'The image could not be uploaded.');
    }

    return payload.url as string;
}
