<?php

namespace Simff\Request;

use Countable;

class Session implements Countable
{
    public $debug = false;

    public $autoStart = true;

    public function init()
    {
        register_shutdown_function([$this, 'close']);
        $this->open();
    }

    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }

        @session_start();
    }

    public function close()
    {
        if ($this->getIsActive()) {
            $this->debug ? session_write_close() : @session_write_close();
        }
    }

    public function getIsActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getId()
    {
        return session_id();
    }

    public function destroy()
    {
        if ($this->getIsActive()) {
            $this->debug ? session_unset() : @session_unset();
            $sessionId = session_id();
            $this->debug ? session_destroy() : @session_destroy();
            $this->debug ? session_id($sessionId) : @session_id($sessionId);
        }
    }

    public function regenerateID($deleteOldSession = false)
    {
        if ($this->getIsActive()) {
            if ($this->debug && !headers_sent()) {
                session_regenerate_id($deleteOldSession);
            } else {
                @session_regenerate_id($deleteOldSession);
            }
        }
    }

    public function getName()
    {
        return session_name();
    }

    public function setName($value)
    {
        session_name($value);
    }


    public function add($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $_SESSION[$key] : $default;
    }

    public function all()
    {
        return $_SESSION;
    }


    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function clear()
    {
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    public function count()
    {
        count($_SESSION);
    }
}