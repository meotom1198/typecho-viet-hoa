<?php

namespace IXR;

use Typecho\Http\Client as HttpClient;

/**
 * khách hàng IXR
 * reload by typecho team(http://www.typecho.org)
 *
 * @package IXR
 */
class Client
{
    /** Máy khách mặc định */
    private const DEFAULT_USERAGENT = 'Typecho XML-RPC PHP Library';

    /**
     * Địa chỉ
     *
     * @var string
     */
    private $url;

    /**
     * nội dung tin nhắn
     *
     * @var Message
     */
    private $message;

    /**
     * Công tắc gỡ lỗi
     *
     * @var boolean
     */
    private $debug = false;

    /**
     * Tiền tố yêu cầu
     *
     * @var string|null
     */
    private $prefix;

    /**
     * @var Error
     */
    private $error;

    /**
     * nhà xây dựng khách hàng
     *
     * @param string $url Địa chỉ máy chủ
     * @param string|null $prefix
     * @return void
     */
    public function __construct(
        string $url,
        ?string $prefix = null
    ) {
        $this->url = $url;
        $this->prefix = $prefix;
    }

    /**
     * Đặt chế độ gỡ lỗi
     * @deprecated
     */
    public function setDebug()
    {
        $this->debug = true;
    }

    /**
     * Thực hiện yêu cầu
     *
     * @param string $method
     * @param array $args
     * @return bool
     */
    private function rpcCall(string $method, array $args): bool
    {
        $request = new Request($method, $args);
        $xml = $request->getXml();

        $client = HttpClient::get();
        if (!$client) {
            $this->error = new Error(-32300, 'transport error - could not open socket');
            return false;
        }

        try {
            $client->setHeader('Content-Type', 'text/xml')
                ->setHeader('User-Agent', self::DEFAULT_USERAGENT)
                ->setData($xml)
                ->send($this->url);
        } catch (HttpClient\Exception $e) {
            $this->error = new Error(-32700, $e->getMessage());
            return false;
        }

        $contents = $client->getResponseBody();

        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($contents) . "\n</pre>\n\n";
        }

        // Now parse what we've got back
        $this->message = new Message($contents);
        if (!$this->message->parse()) {
            // XML error
            $this->error = new Error(-32700, 'parse error. not well formed');
            return false;
        }

        // Is the message a fault?
        if ($this->message->messageType == 'fault') {
            $this->error = new Error($this->message->faultCode, $this->message->faultString);
            return false;
        }

        // Message must be OK
        return true;
    }

    /**
     * thêm tiền tố
     * <code>
     * $rpc->metaWeblog->newPost();
     * </code>
     *
     * @param string $prefix tiền tố
     * @return Client
     */
    public function __get(string $prefix): Client
    {
        return new self($this->url, $this->prefix . $prefix . '.');
    }

    /**
     * Thêm thuộc tính ma thuật
     * by 70
     *
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        $return = $this->rpcCall($this->prefix . $method, $args);

        if ($return) {
            return $this->getResponse();
        } else {
            throw new Exception($this->getErrorMessage(), $this->getErrorCode());
        }
    }

    /**
     * Nhận giá trị trả về
     *
     * @return mixed
     */
    public function getResponse()
    {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }

    /**
     * Có phải là lỗi không
     *
     * @return bool
     */
    public function isError(): bool
    {
        return isset($this->error);
    }

    /**
     * Nhận mã lỗi
     *
     * @return int
     */
    private function getErrorCode(): int
    {
        return $this->error->code;
    }

    /**
     * Nhận thông báo lỗi
     *
     * @return string
     */
    private function getErrorMessage(): string
    {
        return $this->error->message;
    }
}
