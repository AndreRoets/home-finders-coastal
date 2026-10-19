/**
 * Listing photos are served through the `/media/trimmed?u=…` proxy, which can
 * also return downscaled copies via a `w` parameter. These helpers pick the
 * right size so thumbnails and cards don't download the full-size photo.
 * Anything that isn't a proxy URL (placeholders, other hosts) passes through.
 */

/** Widths the server will produce; must match TrimmedImageStore::WIDTHS. */
export const IMAGE_WIDTHS = [480, 960, 1600] as const;

const isProxied = (src: string): boolean => src.includes('/media/trimmed?');

/** The photo at the given width (the server snaps it to the nearest allowed size). */
export function imageAt(src: string, width: number): string {
    return isProxied(src) ? `${src}&w=${width}` : src;
}

/** A `srcSet` over the given widths, or undefined when the URL can't be resized. */
export function imageSrcSet(src: string, widths: readonly number[] = IMAGE_WIDTHS): string | undefined {
    return isProxied(src) ? widths.map((width) => `${imageAt(src, width)} ${width}w`).join(', ') : undefined;
}
