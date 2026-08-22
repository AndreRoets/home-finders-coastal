<?php

namespace App\Http\Controllers;

use App\Support\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(protected SitemapBuilder $sitemap) {}

    /**
     * The XML sitemap submitted to Google Search Console.
     */
    public function index(): Response
    {
        return response($this->sitemap->toXml(), 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * robots.txt — admin-managed, falling back to a default that points
     * crawlers at the sitemap and keeps the admin panel out of the index.
     */
    public function robots(): Response
    {
        return response($this->sitemap->robotsTxt(), 200, ['Content-Type' => 'text/plain']);
    }
}
