export type ListingStatus = 'for-sale' | 'to-rent' | 'exclusive' | 'sold';

export interface Listing {
    id: number;
    title: string;
    location: string;
    price: string;
    beds: number;
    baths: number;
    area: string;
    status: ListingStatus;
    image: string;
}

/**
 * Placeholder listings used to scaffold the public pages. These will be
 * replaced by data from the database / an Eloquent resource later on.
 */
export const sampleListings: Listing[] = [
    {
        id: 1,
        title: 'Oceanfront Villa with Infinity Pool',
        location: 'Camps Bay',
        price: 'R 24 500 000',
        beds: 5,
        baths: 4,
        area: '480 m²',
        status: 'for-sale',
        image: 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=60',
    },
    {
        id: 2,
        title: 'Modern Apartment Steps from the Beach',
        location: 'Sea Point',
        price: 'R 18 500 / month',
        beds: 2,
        baths: 2,
        area: '95 m²',
        status: 'to-rent',
        image: 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=60',
    },
    {
        id: 3,
        title: 'Clifftop Estate with Panoramic Views',
        location: 'Llandudno',
        price: 'R 42 000 000',
        beds: 6,
        baths: 6,
        area: '720 m²',
        status: 'exclusive',
        image: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=60',
    },
    {
        id: 4,
        title: 'Coastal Family Home with Garden',
        location: 'Hout Bay',
        price: 'R 9 750 000',
        beds: 4,
        baths: 3,
        area: '320 m²',
        status: 'for-sale',
        image: 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=60',
    },
    {
        id: 5,
        title: 'Penthouse with Wraparound Terrace',
        location: 'Mouille Point',
        price: 'R 28 000 / month',
        beds: 3,
        baths: 2,
        area: '160 m²',
        status: 'to-rent',
        image: 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=60',
    },
    {
        id: 6,
        title: 'Restored Beach Cottage',
        location: 'Kommetjie',
        price: 'R 6 200 000',
        beds: 3,
        baths: 2,
        area: '210 m²',
        status: 'sold',
        image: 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?auto=format&fit=crop&w=1200&q=60',
    },
];
