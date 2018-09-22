<?php

namespace Simff\Request;


use Simff\Request\Session;
use Simff\Helpers\Collection;
use Simff\Helpers\CookieCollection;
use Simff\Main\Simff;

class HttpRequest
{
    protected $_hostInfo;

    protected $_baseUrl;

    protected $_scriptUrl;

    protected $_url;

    protected $_port;

    protected $_securePort;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var CookieCollection
     */
    public $cookie;

    /**
     * @var Collection
     */
    public $get;

    /**
     * @var Collection
     */
    public $post;


    public function getHeaderValue($key)
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($key));
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    public function __construct()
    {
        $this->get = new Collection($_GET);
        $this->post = new Collection($_POST);
        $this->cookie = new CookieCollection($_COOKIE);
    }

    public function setSession($session)
    {
        if ($session instanceof Session) {
            $this->_session = $session;
        } elseif (is_array($session) || is_string($session)) {
            $this->_session = Configurator::create($session);
        }
    }

    public function getSession()
    {
        return $this->_session;
    }

    public function getMethod()
    {
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }

        return 'GET';
    }

    /**
     * Returns whether this is a GET request.
     * @return boolean whether this is a GET request.
     */
    public function getIsGet()
    {
        return $this->getMethod() === 'GET';
    }
    /**
     * Returns whether this is an OPTIONS request.
     * @return boolean whether this is a OPTIONS request.
     */
    public function getIsOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }
    /**
     * Returns whether this is a HEAD request.
     * @return boolean whether this is a HEAD request.
     */
    public function getIsHead()
    {
        return $this->getMethod() === 'HEAD';
    }
    /**
     * Returns whether this is a POST request.
     * @return boolean whether this is a POST request.
     */
    public function getIsPost()
    {
        return $this->getMethod() === 'POST';
    }
    /**
     * Returns whether this is a DELETE request.
     * @return boolean whether this is a DELETE request.
     */
    public function getIsDelete()
    {
        return $this->getMethod() === 'DELETE';
    }
    /**
     * Returns whether this is a PUT request.
     * @return boolean whether this is a PUT request.
     */
    public function getIsPut()
    {
        return $this->getMethod() === 'PUT';
    }
    /**
     * Returns whether this is a PATCH request.
     * @return boolean whether this is a PATCH request.
     */
    public function getIsPatch()
    {
        return $this->getMethod() === 'PATCH';
    }
    /**
     * Returns whether this is an AJAX (XMLHttpRequest) request.
     *
     * Note that jQuery doesn't set the header in case of cross domain
     * requests: https://stackoverflow.com/questions/8163703/cross-domain-ajax-doesnt-send-x-requested-with-header
     *
     * @return boolean whether this is an AJAX (XMLHttpRequest) request.
     */
    public function getIsAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    /**
     * Returns whether this is a PJAX request
     * @return boolean whether this is a PJAX request
     */
    public function getIsPjax()
    {
        return $this->getIsAjax() && !empty($_SERVER['HTTP_X_PJAX']);
    }


    /**
     * Returns the schema and host part of the current request URL.
     * The returned URL does not have an ending slash.
     * By default this is determined based on the user request information.
     * You may explicitly specify it by setting the [[setHostInfo()|hostInfo]] property.
     * @return string schema and hostname part (with port number if needed) of the request URL (e.g. `http://www.yiiframework.com`),
     * null if can't be obtained from `$_SERVER` and wasn't set.
     * @see setHostInfo()
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            $secure = $this->getIsSecureConnection();
            $http = $secure ? 'https' : 'http';
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
                $port = $secure ? $this->getSecurePort() : $this->getPort();
                if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                    $this->_hostInfo .= ':' . $port;
                }
            }
        }
        return $this->_hostInfo;
    }


    public function getAbsoluteUrl()
    {
        return $this->getHostInfo() . $this->getUrl();
    }


    public function getUrl()
    {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }
        return $this->_url;
    }


    public function getPath()
    {
        return strtok($this->getUrl(), '?');
    }

    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string|boolean the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws \Exception if the request URI cannot be determined due to unusual server configuration
     */
    protected function resolveRequestUri()
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new \Exception('Unable to determine the request URI.');
        }
        return $requestUri;
    }

    /**
     * Returns part of the request URL that is after the question mark.
     * @return string part of the request URL that is after the question mark
     */
    public function getQueryString()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * Returns array representation part of the request URL that is after the question mark.
     * @return array part of the request URL that is after the question mark
     */
    public function getQueryArray()
    {
        $string = $this->getQueryString();
        parse_str($string, $data);
        return $data;
    }

    /**
     * Return if the request is sent via secure channel (https).
     * @return boolean if the request is sent via secure channel (https)
     */
    public function getIsSecureConnection()
    {
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
            || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }
    /**
     * Returns the server name.
     * @return string server name, null if not available
     */
    public function getServerName()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }
    /**
     * Returns the server port number.
     * @return integer|null server port number, null if not available
     */
    public function getServerPort()
    {
        return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
    }
    /**
     * Returns the URL referrer.
     * @return string|null URL referrer, null if not available
     */
    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }
    /**
     * Returns the user agent.
     * @return string|null user agent, null if not available
     */
    public function getUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }
    /**
     * Returns the user IP address.
     * @return string|null user IP address, null if not available
     */
    public function getUserIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }
    /**
     * Returns the user host name.
     * @return string|null user host name, null if not available
     */
    public function getUserHost()
    {
        return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
    }
    /**
     * @return string|null the username sent via HTTP authentication, null if the username is not given
     */
    public function getAuthUser()
    {
        return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
    }
    /**
     * @return string|null the password sent via HTTP authentication, null if the password is not given
     */
    public function getAuthPassword()
    {
        return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
    }

    /**
     * Returns the port to use for insecure requests.
     * Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     * @return integer port number for insecure requests.
     * @see setPort()
     */
    public function getPort()
    {
        if ($this->_port === null) {
            $this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
        }
        return $this->_port;
    }
    /**
     * Sets the port to use for insecure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param integer $value port number.
     */
    public function setPort($value)
    {
        if ($value != $this->_port) {
            $this->_port = (int) $value;
            $this->_hostInfo = null;
        }
    }

    /**
     * Returns the port to use for secure requests.
     * Defaults to 443, or the port specified by the server if the current
     * request is secure.
     * @return integer port number for secure requests.
     * @see setSecurePort()
     */
    public function getSecurePort()
    {
        if ($this->_securePort === null) {
            $this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
        }
        return $this->_securePort;
    }
    /**
     * Sets the port to use for secure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param integer $value port number.
     */
    public function setSecurePort($value)
    {
        if ($value != $this->_securePort) {
            $this->_securePort = (int) $value;
            $this->_hostInfo = null;
        }
    }


    /**
     * Returns request content-type
     * The Content-Type header field indicates the MIME type of the data
     * contained in [[getRawBody()]] or, in the case of the HEAD method, the
     * media type that would have been sent had the request been a GET.
     * For the MIME-types the user expects in response, see [[acceptableContentTypes]].
     * @return string request content-type. Null is returned if this information is not available.
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.17
     * HTTP 1.1 header field definitions
     */
    public function getContentType()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            return $_SERVER['CONTENT_TYPE'];
        } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            //fix bug https://bugs.php.net/bug.php?id=66606
            return $_SERVER['HTTP_CONTENT_TYPE'];
        }
        return null;
    }

    /**
     * Redirects the browser to the specified URL.
     * @param string $url URL to be redirected to. Note that when URL is not
     * absolute (not starting with "/") it will be relative to current request URL.
     * @param array $data Data for create url
     * @param integer $statusCode the HTTP status code. Defaults to 302. See {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
     * for details about HTTP status code.
     */
    public function redirect($url, $data = [], $statusCode = 302)
    {
        if (is_object($url) && method_exists($url, 'getAbsoluteUrl')) {
            $url = $url->getAbsoluteUrl();
        } elseif (strpos($url, ':') !== false) {
            $url = Simff::app()->router->url($url, $data);
        }

        header('Location: '.$url, true, $statusCode);
        Simff::app()->end();
    }

    public function refresh()
    {
        $this->redirect($this->getUrl());
    }
}