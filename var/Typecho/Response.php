<?php

namespace Typecho;

use Typecho\Widget\Terminal;

/**
 * Phương pháp công khai Typecho
 *
 * @category typecho
 * @package Response
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Response
{
    /**
     * http code
     *
     * @access private
     * @var array
     */
    private const HTTP_CODE = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    // Mã hóa ký tự mặc định
    /**
     * Tay cầm đơn
     *
     * @access private
     * @var Response
     */
    private static $instance;

    /**
     * mã hóa ký tự
     *
     * @var string
     */
    private $charset = 'UTF-8';

    /**
     * @var string
     */
    private $contentType = 'text/html';

    /**
     * @var callable[]
     */
    private $responders = [];

    /**
     * @var array
     */
    private $cookies = [];

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var int
     */
    private $status = 200;

    /**
     * @var bool
     */
    private $enableAutoSendHeaders = true;

    /**
     * @var bool
     */
    private $sandbox = false;

    /**
     * init responder
     */
    public function __construct()
    {
        $this->clean();
    }

    /**
     * Nhận xử lý đơn
     *
     * @return Response
     */
    public static function getInstance(): Response
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return $this
     */
    public function beginSandbox(): Response
    {
        $this->sandbox = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function endSandbox(): Response
    {
        $this->sandbox = false;
        return $this;
    }

    /**
     * @param bool $enable
     */
    public function enableAutoSendHeaders(bool $enable = true)
    {
        $this->enableAutoSendHeaders = $enable;
    }

    /**
     * clean all
     */
    public function clean()
    {
        $this->headers = [];
        $this->cookies = [];
        $this->status = 200;
        $this->responders = [];
        $this->setContentType('text/html');
    }

    /**
     * send all headers
     */
    public function sendHeaders()
    {
        if ($this->sandbox) {
            return;
        }

        $sentHeaders = [];
        foreach (headers_list() as $header) {
            [$key] = explode(':', $header, 2);
            $sentHeaders[] = strtolower(trim($key));
        }

        header('HTTP/1.1 ' . $this->status . ' ' . self::HTTP_CODE[$this->status], true, $this->status);

        // set header
        foreach ($this->headers as $name => $value) {
            if (!in_array(strtolower($name), $sentHeaders)) {
                header($name . ': ' . $value, true);
            }
        }

        // set cookie
        foreach ($this->cookies as $cookie) {
            [$key, $value, $timeout, $path, $domain, $secure, $httponly] = $cookie;

            if ($timeout > 0) {
                $now = time();
                $timeout += $timeout > $now - 86400 ? 0 : $now;
            } elseif ($timeout < 0) {
                $timeout = 1;
            }

            setrawcookie($key, rawurlencode($value), $timeout, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * respond data
     * @throws Terminal
     */
    public function respond()
    {
        if ($this->sandbox) {
            throw new Terminal();
        }

        if ($this->enableAutoSendHeaders) {
            $this->sendHeaders();
        }

        foreach ($this->responders as $responder) {
            call_user_func($responder, $this);
        }

        exit;
    }

    /**
     * Đặt trạng thái HTTP
     *
     * @access public
     * @param integer $code mã http
     * @return $this
     */
    public function setStatus(int $code): Response
    {
        if (!$this->sandbox) {
            $this->status = $code;
        }

        return $this;
    }

    /**
     * Đặt tiêu đề http
     *
     * @param string $name tên
     * @param string $value Giá trị tương ứng
     * @return $this
     */
    public function setHeader(string $name, string $value): Response
    {
        if (!$this->sandbox) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Đặt giá trị COOKIE được chỉ định
     *
     * @param string $key Thông số được chỉ định
     * @param mixed $value đặt giá trị
     * @param integer $timeout Thời gian hết hạn, mặc định là 0, nghĩa là kết thúc theo thời gian của phiên
     * @param string $path thông tin đường dẫn
     * @param string|null $domain Thông tin tên miền
     * @param bool $secure Liệu nó chỉ có thể được chuyển đến máy khách qua kết nối HTTPS an toàn hay không
     * @param bool $httponly Có phải nó chỉ có thể truy cập được qua giao thức HTTP?
     * @return $this
     */
    public function setCookie(
        string $key,
        $value,
        int $timeout = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false
    ): Response {
        if (!$this->sandbox) {
            $this->cookies[] = [$key, $value, $timeout, $path, $domain, $secure, $httponly];
        }

        return $this;
    }

    /**
     * Khai báo loại và bộ ký tự trong yêu cầu tiêu đề http
     *
     * @param string $contentType Loại tài liệu
     * @return $this
     */
    public function setContentType(string $contentType = 'text/html'): Response
    {
        if (!$this->sandbox) {
            $this->contentType = $contentType;
            $this->setHeader('Content-Type', $this->contentType . '; charset=' . $this->charset);
        }

        return $this;
    }

    /**
     * Nhận bộ ký tự
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Đặt mã hóa biên nhận trả lại mặc định
     *
     * @param string $charset bộ ký tự
     * @return $this
     */
    public function setCharset(string $charset): Response
    {
        if (!$this->sandbox) {
            $this->charset = $charset;
            $this->setHeader('Content-Type', $this->contentType . '; charset=' . $this->charset);
        }

        return $this;
    }

    /**
     * add responder
     *
     * @param callable $responder
     * @return $this
     */
    public function addResponder(callable $responder): Response
    {
        if (!$this->sandbox) {
            $this->responders[] = $responder;
        }

        return $this;
    }
}
