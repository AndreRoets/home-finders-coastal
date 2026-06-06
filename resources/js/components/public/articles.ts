export interface Article {
    id: number | string;
    agentId: number | string | null;
    title: string;
    slug: string;
    excerpt: string | null;
    coverImage: string | null;
    body: string;
    linkUrl: string | null;
    tags: string[];
    readMinutes: number;
    wordCount: number;
    date: string | null;
}
