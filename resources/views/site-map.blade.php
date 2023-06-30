<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    @if (isset($pages) && !empty($pages))
        @foreach ($pages as $page)
            <url>
                <loc>{{ $page }}</loc>
            </url>
        @endforeach
    @endif

    @foreach ($products as $items)
        <url>
            <loc>https://www.gbssystems.com/products/{{ $items->product_url }}</loc>
            <lastmod>{{ $items->created_at->tz('UTC')->toAtomString() }}</lastmod>
        </url>
    @endforeach
    @foreach ($store_locator as $items)
        <url>
            <loc>https://www.gbssystems.com/store-location-details/{{ $items->slug }}</loc>
            <lastmod>{{ $items->created_at->tz('UTC')->toAtomString() }}</lastmod>
        </url>
    @endforeach
    @foreach ($store_center as $items)
        <url>
            <loc>https://www.gbssystems.com/service-center-details/{{ $items->slug }}</loc>
            <lastmod>{{ $items->created_at->tz('UTC')->toAtomString() }}</lastmod>
        </url>
    @endforeach
</urlset>
