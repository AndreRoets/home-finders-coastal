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
    agent?: ListingAgent | null;
}
