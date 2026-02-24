<?php

namespace Silber\PageCache\Middleware;

use Closure;
use Silber\PageCache\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * The cache instance.
     *
     * @var \Silber\PageCache\Cache
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @var \Silber\PageCache\Cache  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldCache($request, $response)) {
            $minified = $this->minify($response->getContent());
            $response->setContent($minified);
            $this->cache->cache($request, $response);
        }

        return $response;
    }

    private function minify(string $content): string
    {
        // Remove HTML comments (except IE conditionals)
        $content = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content);

        // Remove whitespace between tags
        $content = preg_replace('/>\s+</', '><', $content);

        // Collapse multiple whitespace characters into one
        $content = preg_replace('/\s{2,}/', ' ', $content);

        return trim($content);
    }

    /**
     * Determines whether the given request/response pair should be cached.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function shouldCache(Request $request, Response $response)
    {
        return $request->isMethod('GET') && $response->getStatusCode() == 200;
    }
}
