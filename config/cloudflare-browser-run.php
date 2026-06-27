<?php

// config for Ziming/LaravelCloudflareBrowserRun
return [

    /*
     * Your Cloudflare account ID. Found in the Cloudflare dashboard URL and on
     * the account home page. Required to build the Browser Rendering endpoint.
     */
    'account_id' => env('CLOUDFLARE_BROWSER_RUN_ACCOUNT_ID'),

    /*
     * An API token with the "Browser Rendering: Edit" permission.
     * Sent as a Bearer token on every request.
     */
    'api_token' => env('CLOUDFLARE_BROWSER_RUN_API_TOKEN'),

    /*
     * The Cloudflare API root. The account + browser-rendering path is appended
     * automatically, so leave this as the v4 client base unless you proxy it.
     */
    'base_url' => env('CLOUDFLARE_BROWSER_RUN_BASE_URL', 'https://api.cloudflare.com/client/v4'),

    /*
     * Request timeout in seconds for the underlying Laravel HTTP client.
     */
    'timeout' => (int) env('CLOUDFLARE_BROWSER_RUN_TIMEOUT', 60),

    /*
     * Default cacheTTL (seconds) sent as a query parameter. 0 disables caching,
     * max 86400. Override per request with ->cacheTtl(). Set to null to omit.
     */
    'cache_ttl' => (int) env('CLOUDFLARE_BROWSER_RUN_CACHE_TTL', 5),

];
