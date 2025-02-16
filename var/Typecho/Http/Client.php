<?php

namespace Typecho\Http;

use Typecho\Common;
use Typecho\Http\Client\Exception;

/**
 * Máy khách HTTP
 *
 * @category typecho
 * @package Http
 */
class Client
{
    /** phương thức POST */
    public const METHOD_POST = 'POST';

    /** phương pháp GET */
    public const METHOD_GET = 'GET';

    /** phương pháp PUT */
    public const METHOD_PUT = 'PUT';

    /** phương pháp DELETE */
    public const METHOD_DELETE = 'DELETE';

    /**
     * tên phương thức
     *
     * @var string
     */
    private $method = self::METHOD_GET;

    /**
     * Truyền tham số
     *
     * @var string
     */
    private $query;

    /**
     * User Agent
     *
     * @var string
     */
    private $agent;

    /**
     * Đặt thời gian chờ
     *
     * @var string
     */
    private $timeout = 3;

    /**
     * @var bool
     */
    private $multipart = true;

    /**
     * Giá trị cần được truyền trong phần thân
     *
     * @var array|string
     */
    private $data = [];

    /**
     * Thông số thông tin tiêu đề
     *
     * @access private
     * @var array
     */
    private $headers = [];

    /**
     * cookies
     *
     * @var array
     */
    private $cookies = [];

    /**
     * @var array
     */
    private $options = [];

    /**
     * Trả lại thông tin tiêu đề biên nhận
     *
     * @var array
     */
    private $responseHeader = [];

    /**
     * mã biên nhận
     *
     * @var integer
     */
    private $responseStatus;

    /**
     * cơ quan nhận
     *
     * @var string
     */
    private $responseBody;

    /**
     * Đặt giá trị COOKIE được chỉ định
     *
     * @param string $key Thông số được chỉ định
     * @param mixed $value đặt giá trị
     * @return $this
     */
    public function setCookie(string $key, $value): Client
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * Đặt thông số truyền
     *
     * @param mixed $query Truyền tham số
     * @return $this
     */
    public function setQuery($query): Client
    {
        $query = is_array($query) ? http_build_query($query) : $query;
        $this->query = empty($this->query) ? $query : $this->query . '&' . $query;
        return $this;
    }

    /**
     * Đặt dữ liệu cần được POST
     *
     * @param array|string $data Dữ liệu cần thiết cho POST
     * @param string $method
     * @return $this
     */
    public function setData($data, string $method = self::METHOD_POST): Client
    {
        if (is_array($data) && is_array($this->data)) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data = $data;
        }

        $this->setMethod($method);
        return $this;
    }

    /**
     * Đặt dữ liệu Json được yêu cầu
     *
     * @param $data
     * @param string $method
     * @return $this
     */
    public function setJson($data, string $method = self::METHOD_POST): Client
    {
        $this->setData(json_encode($data), $method)
            ->setMultipart(true)
            ->setHeader('Content-Type', 'application/json');

        return $this;
    }

    /**
     * Đặt tên phương thức
     *
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): Client
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Đặt các tập tin cần được POST
     *
     * @param array $files Các tệp yêu cầu POST
     * @param string $method
     * @return $this
     */
    public function setFiles(array $files, string $method = self::METHOD_POST): Client
    {
        if (is_array($this->data)) {
            foreach ($files as $name => $file) {
                $this->data[$name] = new \CURLFile($file);
            }
        }

        $this->setMethod($method);
        return $this;
    }

    /**
     * Đặt thời gian chờ
     *
     * @param integer $timeout hết thời gian
     * @return $this
     */
    public function setTimeout(int $timeout): Client
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * setAgent
     *
     * @param string $agent
     * @return $this
     */
    public function setAgent(string $agent): Client
    {
        $this->agent = $agent;
        return $this;
    }

    /**
     * @param bool $multipart
     * @return $this
     */
    public function setMultipart(bool $multipart): Client
    {
        $this->multipart = $multipart;
        return $this;
    }

    /**
     * @param int $key
     * @param mixed $value
     * @return $this
     */
    public function setOption(int $key, $value): Client
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Đặt tham số thông tin tiêu đề
     *
     * @param string $key Tên tham số
     * @param string $value Giá trị tham số
     * @return $this
     */
    public function setHeader(string $key, string $value): Client
    {
        $key = str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));

        if ($key == 'User-Agent') {
            $this->setAgent($value);
        } else {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * Gửi yêu cầu
     *
     * @param string $url Địa chỉ yêu cầu
     * @throws Exception
     */
    public function send(string $url)
    {
        $params = parse_url($url);
        $query = empty($params['query']) ? '' : $params['query'];

        if (!empty($this->query)) {
            $query = empty($query) ? $this->query : '&' . $this->query;
        }

        if (!empty($query)) {
            $params['query'] = $query;
        }

        $url = Common::buildUrl($params);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

        if (isset($this->agent)) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        }

        /** Đặt thông tin tiêu đề */
        if (!empty($this->headers)) {
            $headers = [];

            foreach ($this->headers as $key => $val) {
                $headers[] = $key . ': ' . $val;
            }

            if (!empty($this->cookies)) {
                $headers[] = 'Cookie: ' . str_replace('&', '; ', http_build_query($this->cookies));
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($this->data)) {
            $data = $this->data;

            if (!$this->multipart) {
                curl_setopt($ch, CURLOPT_POST, true);
                $data = is_array($data) ? http_build_query($data) : $data;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
            $parts = explode(':', $header, 2);

            if (count($parts) == 2) {
                [$key, $value] = $parts;
                $this->responseHeader[strtolower(trim($key))] = trim($value);
            }

            return strlen($header);
        });

        foreach ($this->options as $key => $val) {
            curl_setopt($ch, $key, $val);
        }

        $response = curl_exec($ch);
        if (false === $response) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error, 500);
        }

        $this->responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->responseBody = $response;
        curl_close($ch);
    }

    /**
     * Lấy thông tin tiêu đề của biên nhận
     *
     * @param string $key Tên thông tin tiêu đề
     * @return string
     */
    public function getResponseHeader(string $key): ?string
    {
        $key = strtolower($key);
        return $this->responseHeader[$key] ?? null;
    }

    /**
     * Nhận mã biên nhận
     *
     * @return integer
     */
    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    /**
     * Nhận cơ thể nhận
     *
     * @return string
     */
    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    /**
     * Nhận các kết nối có sẵn
     *
     * @return ?Client
     */
    public static function get(): ?Client
    {
        return extension_loaded('curl') ? new static() : null;
    }
}
