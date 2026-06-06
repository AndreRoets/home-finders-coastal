export interface TestimonialAgent {
    id: number | string;
    name: string;
}

export interface Testimonial {
    id: number | string;
    author: string;
    rating: number | null;
    body: string;
    date: string | null;
    agent: TestimonialAgent | null;
}
