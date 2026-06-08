export type ListingStatus = 'for-sale' | 'to-rent' | 'exclusive' | 'sold';

export interface ListingAgent {
    id: number | string;
    name: string;
    designation: string | null;
    photo: string;
}

export interface Listing {
    id: number | string;
    ref?: string | null;
    /** Canonical title-based path segment for property.show, e.g. "apartment-for-sale-in-margate-12345". */
    slug?: string | null;
    title: string;
    location: string;
    price: string;
    beds: number;
    baths: number;
    area: string;
    status: ListingStatus;
    /** True when the listing is a sole mandate — shows an "Exclusive" tag on the card. */
    exclusive?: boolean;
    image: string;
    url?: string | null;
    /** Primary agent, kept for backwards compatibility — prefer `agents`. */
    agent?: ListingAgent | null;
    /** Every agent attributed to the listing (a property may be co-listed). */
    agents?: ListingAgent[];
}
