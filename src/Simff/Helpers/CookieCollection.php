<?php

namespace Simff\Helpers;


class CookieCollection extends Collection
{
    public function add($key, $value, $expire = 0, $path = '/', $domain = false, $secure = false, $httponly = false)
    {
        setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_COOKIE[$key]);
            setcookie($key, "", time()-3600, '/');
        }
    }

    public function clear()
    {
        foreach (array_keys($_COOKIE) as $key) {
            unset($_COOKIE[$key]);
            setcookie($key, "", time()-3600, '/');
        }
    }
}