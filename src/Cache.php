<?php

namespace RpLaravelBridge;

use Illuminate\Support\Facades\Log;
use RpContracts\Response;

class Cache extends \Illuminate\Support\Facades\Cache implements \RpContracts\Cache
{
    /**
     * @var string
     */
    protected string $prefix;

    /**
     * Cache constructor.
     * @param string $prefix
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $uri
     * @param Response $result
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $uri, Response $result, int $ttl = null): bool
    {
        return \Illuminate\Support\Facades\Cache::put($this->getkey($uri), $result, $ttl);
    }

    /**
     * @param string $uri
     * @return Response|null
     */
    public function get(string $uri): ?Response
    {
        return \Illuminate\Support\Facades\Cache::get($this->getkey($uri));
    }

    /**
     * @param string $uri
     * @return bool
     */
    public function has(string $uri): bool
    {
        return \Illuminate\Support\Facades\Cache::has($this->getkey($uri));
    }

    /**
     * @param string $uri
     * @return string
     */
    protected function getKey(string $uri) : string
    {
        return $this->prefix.'.'.$uri;
    }
}
