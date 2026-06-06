<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed the manageable public pages. Keys must match the route names /
     * controller actions registered in routes/web.php.
     */
    public function run(): void
    {
        $pages = [
            [
                'key' => 'home',
                'name' => 'Home',
                'slug' => '/',
                'meta_title' => 'Home Finders Coastal — The Mandate Company',
                'meta_description' => 'Coastal property specialists. Browse homes for sale and to rent along the coast with Home Finders Coastal.',
                'sitemap_priority' => '1.0',
                'sitemap_frequency' => 'daily',
            ],
            [
                'key' => 'for-sale',
                'name' => 'Properties For Sale',
                'slug' => 'for-sale',
                'meta_title' => 'Properties For Sale | Home Finders Coastal',
                'meta_description' => 'Discover coastal properties for sale. Filter by suburb, price, bedrooms and more.',
                'sitemap_priority' => '0.9',
                'sitemap_frequency' => 'daily',
            ],
            [
                'key' => 'to-rent',
                'name' => 'Properties To Rent',
                'slug' => 'to-rent',
                'meta_title' => 'Properties To Rent | Home Finders Coastal',
                'meta_description' => 'Browse coastal rental properties. Find your next home to rent with Home Finders Coastal.',
                'sitemap_priority' => '0.9',
                'sitemap_frequency' => 'daily',
            ],
            [
                'key' => 'hfc-exclusive',
                'name' => 'HFC Exclusive',
                'slug' => 'hfc-exclusive',
                'meta_title' => 'HFC Exclusive Sole-Mandate Listings | Home Finders Coastal',
                'meta_description' => 'Exclusive sole-mandate coastal properties available only through Home Finders Coastal.',
                'sitemap_priority' => '0.8',
                'sitemap_frequency' => 'weekly',
            ],
            [
                'key' => 'sold',
                'name' => 'Recently Sold',
                'slug' => 'sold',
                'meta_title' => 'Recently Sold Properties | Home Finders Coastal',
                'meta_description' => 'A track record of coastal properties recently sold by Home Finders Coastal.',
                'sitemap_priority' => '0.6',
                'sitemap_frequency' => 'weekly',
            ],
            [
                'key' => 'agents',
                'name' => 'Our Agents',
                'slug' => 'agents',
                'meta_title' => 'Meet Our Agents | Home Finders Coastal',
                'meta_description' => 'Meet the experienced coastal property agents at Home Finders Coastal.',
                'sitemap_priority' => '0.7',
                'sitemap_frequency' => 'monthly',
            ],
            [
                'key' => 'contact',
                'name' => 'Contact Us',
                'slug' => 'contact',
                'meta_title' => 'Contact Us | Home Finders Coastal',
                'meta_description' => 'Get in touch with Home Finders Coastal for all your coastal property needs.',
                'sitemap_priority' => '0.5',
                'sitemap_frequency' => 'monthly',
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->updateOrCreate(['key' => $page['key']], $page);
        }
    }
}
