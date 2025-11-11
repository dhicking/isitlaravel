<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = url('/');
        $routes = [
            [
                'loc' => $baseUrl,
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($routes as $route) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($route['loc']).'</loc>'."\n";
            $xml .= '    <lastmod>'.htmlspecialchars($route['lastmod']).'</lastmod>'."\n";
            $xml .= '    <changefreq>'.htmlspecialchars($route['changefreq']).'</changefreq>'."\n";
            $xml .= '    <priority>'.htmlspecialchars($route['priority']).'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
