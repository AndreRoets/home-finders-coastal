export type ListingStatus = 'for-sale' | 'to-rent' | 'exclusive' | 'sold';

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
    image: string;
    url?: string | null;
}
