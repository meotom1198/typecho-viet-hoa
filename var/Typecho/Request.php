<?php

namespace Typecho;

/**
 * Lớp xử lý yêu cầu máy chủ
 *
 * @package Request
 */
class Request
{
    /**
     * Tay cầm đơn
     *
     * @access private
     * @var Request
     */
    private static $instance;

    /**
     * Thông số hộp cát
     *
     * @access private
     * @var Config|null
     */
    private $sandbox;

    /**
     * Thông số người dùng
     *
     * @access private
     * @var Config|null
     */
    private $params;

    /**
     * thông tin đường dẫn
     *
     * @access private
     * @var string
     */
    private $pathInfo = null;

    /**
     * requestUri
     *
     * @var string
     * @access private
     */
    private $requestUri = null;

    /**
     * requestRoot
     *
     * @var mixed
     * @access private
     */
    private $requestRoot = null;

    /**
     * Nhận cơ sở dữ liệu
     *
     * @var string
     * @access private
     */
    private $baseUrl = null;

    /**
     * địa chỉ IP của khách hàng
     *
     * @access private
     * @var string
     */
    private $ip = null;

    /**
     * Tiền tố tên miền
     *
     * @var string
     */
    private $urlPrefix = null;

    /**
     * Nhận xử lý đơn
     *
     * @access public
     * @return Request
     */
    public static function getInstance(): Request
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * khởi tạo biến
     *
     * @return $this
     */
    public function beginSandbox(Config $sandbox): Request
    {
        $this->sandbox = $sandbox;
        return $this;
    }

    /**
     * @return $this
     */
    public function endSandbox(): Request
    {
        $this->sandbox = null;
        return $this;
    }

    /**
     * @param Config $params
     * @return $this
     */
    public function proxy(Config $params): Request
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Nhận các tham số thực tế được thông qua
     *
     * @param string $key Chỉ định tham số
     * @param mixed $default Thông số mặc định (default: NULL)
     * @param bool|null $exists detect exists
     * @return mixed
     */
    public function get(string $key, $default = null, ?bool &$exists = true)
    {
        $exists = true;
        $value = null;

        switch (true) {
            case isset($this->params) && isset($this->params[$key]):
                $value = $this->params[$key];
                break;
            case isset($this->sandbox):
                if (isset($this->sandbox[$key])) {
                    $value = $this->sandbox[$key];
                } else {
                    $exists = false;
                }
                break;
            case $key === '@json':
                $exists = false;
                if ($this->isJson()) {
                    $body = file_get_contents('php://input');

                    if (false !== $body) {
                        $exists = true;
                        $value = json_decode($body, true, 16);
                        $default = $default ?? $value;
                    }
                }
                break;
            case isset($_GET[$key]):
                $value = $_GET[$key];
                break;
            case isset($_POST[$key]):
                $value = $_POST[$key];
                break;
            default:
                $exists = false;
                break;
        }

        // reset params
        if (isset($this->params)) {
            $this->params = null;
        }

        if (isset($value)) {
            return is_array($default) == is_array($value) ? $value : $default;
        } else {
            return $default;
        }
    }

    /**
     * Nhận các tham số thực tế được thông qua(magic)
     *
     * @param string $key Chỉ định tham số
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Xác định xem tham số có tồn tại không
     *
     * @param string $key Chỉ định tham số
     * @return boolean
     */
    public function __isset(string $key)
    {
        $this->get($key, null, $exists);
        return $exists;
    }

    /**
     * Nhận một mảng
     *
     * @param $key
     * @return array
     */
    public function getArray($key): array
    {
        $result = $this->get($key, [], $exists);

        if (!empty($result) || !$exists) {
            return $result;
        }

        return [$this->get($key)];
    }

    /**
     * Nhận tham số truyền http từ giá trị được chỉ định trong danh sách tham số
     *
     * @param mixed $params Thông số được chỉ định
     * @return array
     */
    public function from($params): array
    {
        $result = [];
        $args = is_array($params) ? $params : func_get_args();

        foreach ($args as $arg) {
            $result[$arg] = $this->get($arg);
        }

        return $result;
    }

    /**
     * getRequestRoot
     *
     * @return string
     */
    public function getRequestRoot(): string
    {
        if (null === $this->requestRoot) {
            $root = rtrim($this->getUrlPrefix() . $this->getBaseUrl(), '/') . '/';

            $pos = strrpos($root, '.php/');
            if ($pos) {
                $root = dirname(substr($root, 0, $pos));
            }

            $this->requestRoot = rtrim($root, '/');
        }

        return $this->requestRoot;
    }

    /**
     * Nhận url yêu cầu hiện tại
     *
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->getUrlPrefix() . $this->getRequestUri();
    }

    /**
     * Xây dựng một uri với các tham số được chỉ định dựa trên uri hiện tại
     *
     * @param mixed $parameter Thông số được chỉ định
     * @return string
     */
    public function makeUriByRequest($parameter = null): string
    {
        /** địa chỉ khởi tạo */
        $requestUri = $this->getRequestUrl();
        $parts = parse_url($requestUri);

        /** Thông số khởi tạo */
        if (is_string($parameter)) {
            parse_str($parameter, $args);
        } elseif (is_array($parameter)) {
            $args = $parameter;
        } else {
            return $requestUri;
        }

        /** Xây dựng truy vấn */
        if (isset($parts['query'])) {
            parse_str($parts['query'], $currentArgs);
            $args = array_merge($currentArgs, $args);
        }
        $parts['query'] = http_build_query($args);

        /** địa chỉ trả lại */
        return Common::buildUrl($parts);
    }

    /**
     * Nhận thông tin đường dẫn hiện tại
     *
     * @return string
     */
    public function getPathInfo(): ?string
    {
        /** thông tin bộ nhớ đệm */
        if (null !== $this->pathInfo) {
            return $this->pathInfo;
        }

        // Tham khảo cách xử lý thông tin đường dẫn của Zend Framework để tương thích tốt hơn
        $pathInfo = null;

        // Xử lý yêu cầuUri
        $requestUri = $this->getRequestUri();
        $finalBaseUrl = $this->getBaseUrl();

        // Remove the query string from REQUEST_URI
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if (
            (null !== $finalBaseUrl)
            && (false === ($pathInfo = substr($requestUri, strlen($finalBaseUrl))))
        ) {
            // If substr() returns false then PATH_INFO is set to an empty string
            $pathInfo = '/';
        } elseif (null === $finalBaseUrl) {
            $pathInfo = $requestUri;
        }

        if (!empty($pathInfo)) {
            // Buộc chuyển đổi để mã hóa utf8 của iis
            $pathInfo = defined('__TYPECHO_PATHINFO_ENCODING__') ?
                mb_convert_encoding($pathInfo, 'UTF-8', __TYPECHO_PATHINFO_ENCODING__) : $pathInfo;
        } else {
            $pathInfo = '/';
        }

        // fix issue 456
        return ($this->pathInfo = '/' . ltrim(urldecode($pathInfo), '/'));
    }

    /**
     * Nhận các biến môi trường
     *
     * @param string $name Lấy tên biến môi trường
     * @param string|null $default
     * @return string|null
     */
    public function getServer(string $name, string $default = null): ?string
    {
        return $_SERVER[$name] ?? $default;
    }

    /**
     * lấy địa chỉ IP
     *
     * @return string
     */
    public function getIp(): string
    {
        if (null === $this->ip) {
            $header = defined('__TYPECHO_IP_SOURCE__') ? __TYPECHO_IP_SOURCE__ : 'X-Forwarded-For';
            $ip = $this->getHeader($header, $this->getHeader('Client-Ip', $this->getServer('REMOTE_ADDR')));

            if (!empty($ip)) {
                [$ip] = array_map('trim', explode(',', $ip));
                $ip = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
            }

            if (!empty($ip)) {
                $this->ip = $ip;
            } else {
                $this->ip = 'unknown';
            }
        }

        return $this->ip;
    }

    /**
     * get header value
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getHeader(string $key, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->getServer($key, $default);
    }

    /**
     * Nhận khách hàng
     *
     * @return string
     */
    public function getAgent(): ?string
    {
        return $this->getHeader('User-Agent');
    }

    /**
     * Nhận khách hàng
     *
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $this->getHeader('Referer');
    }

    /**
     * Xác định xem đó có phải là https không
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && !strcasecmp('https', $_SERVER['HTTP_X_FORWARDED_PROTO']))
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && !strcasecmp('quic', $_SERVER['HTTP_X_FORWARDED_PROTO']))
            || (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && 443 == $_SERVER['HTTP_X_FORWARDED_PORT'])
            || (!empty($_SERVER['HTTPS']) && 'off' != strtolower($_SERVER['HTTPS']))
            || (!empty($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT'])
            || (defined('__TYPECHO_SECURE__') && __TYPECHO_SECURE__);
    }

    /**
     * @return bool
     */
    public function isCli(): bool
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Xác định xem đó có phải là phương thức get không
     *
     * @return boolean
     */
    public function isGet(): bool
    {
        return 'GET' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * Xác định xem đó có phải là một phương pháp đăng bài hay không
     *
     * @return boolean
     */
    public function isPost(): bool
    {
        return 'POST' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * Xác định xem đó có phải là phương thức đặt không
     *
     * @return boolean
     */
    public function isPut(): bool
    {
        return 'PUT' == $this->getServer('REQUEST_METHOD');
    }

    /**
     * Xác định xem đó có phải là ajax không
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return 'XMLHttpRequest' == $this->getHeader('X-Requested-With');
    }

    /**
     * Xác định xem đó có phải là yêu cầu Json không
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return !!preg_match("/^\s*application\/json(;|$)/i", $this->getHeader('Content-Type', ''));
    }

    /**
     * Xác định xem đầu vào có đáp ứng yêu cầu hay không
     *
     * @param mixed $query tình trạng
     * @return boolean
     */
    public function is($query): bool
    {
        $validated = false;

        /** chuỗi phân tích */
        if (is_string($query)) {
            parse_str($query, $params);
        } elseif (is_array($query)) {
            $params = $query;
        }

        /** Chuỗi xác minh */
        if (!empty($params)) {
            $validated = true;
            foreach ($params as $key => $val) {
                $param = $this->get($key, null, $exists);
                $validated = empty($val) ? $exists : ($val == $param);

                if (!$validated) {
                    break;
                }
            }
        }

        return $validated;
    }

    /**
     * Nhận địa chỉ tài nguyên được yêu cầu
     *
     * @return string|null
     */
    public function getRequestUri(): ?string
    {
        if (!empty($this->requestUri)) {
            return $this->requestUri;
        }

        // Xử lý yêu cầuUri
        $requestUri = '/';

        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            isset($_SERVER['IIS_WasUrlRewritten'])
            && $_SERVER['IIS_WasUrlRewritten'] == '1'
            && isset($_SERVER['UNENCODED_URL'])
            && $_SERVER['UNENCODED_URL'] != ''
        ) {
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $parts = @parse_url($requestUri);

            if (isset($_SERVER['HTTP_HOST']) && strstr($requestUri, $_SERVER['HTTP_HOST'])) {
                if (false !== $parts) {
                    $requestUri = (empty($parts['path']) ? '' : $parts['path'])
                        . ((empty($parts['query'])) ? '' : '?' . $parts['query']);
                }
            } elseif (!empty($_SERVER['QUERY_STRING']) && empty($parts['query'])) {
                // fix query missing
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        return $this->requestUri = $requestUri;
    }

    /**
     * Nhận tiền tố url
     *
     * @return string|null
     */
    public function getUrlPrefix(): ?string
    {
        if (empty($this->urlPrefix)) {
            if (defined('__TYPECHO_URL_PREFIX__')) {
                $this->urlPrefix = __TYPECHO_URL_PREFIX__;
            } elseif (php_sapi_name() != 'cli') {
                $this->urlPrefix = ($this->isSecure() ? 'https' : 'http') . '://'
                    . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']);
            }
        }

        return $this->urlPrefix;
    }

    /**
     * getBaseUrl
     *
     * @return string
     */
    private function getBaseUrl(): ?string
    {
        if (null !== $this->baseUrl) {
            return $this->baseUrl;
        }

        // Xử lý Url cơ sở
        $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

        if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['SCRIPT_NAME'];
        } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
            $baseUrl = $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
            $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $_SERVER['PHP_SELF'] ?? '';
            $file = $_SERVER['SCRIPT_FILENAME'] ?? '';
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $finalBaseUrl = null;
        $requestUri = $this->getRequestUri();

        if (0 === strpos($requestUri, $baseUrl)) {
            // full $baseUrl matches
            $finalBaseUrl = $baseUrl;
        } elseif (0 === strpos($requestUri, dirname($baseUrl))) {
            // directory portion of $baseUrl matches
            $finalBaseUrl = rtrim(dirname($baseUrl), '/');
        } elseif (!strpos($requestUri, basename($baseUrl))) {
            // no match whatsoever; set it blank
            $finalBaseUrl = '';
        } elseif (
            (strlen($requestUri) >= strlen($baseUrl))
            && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0))
        ) {
            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return ($this->baseUrl = (null === $finalBaseUrl) ? rtrim($baseUrl, '/') : $finalBaseUrl);
    }
}
