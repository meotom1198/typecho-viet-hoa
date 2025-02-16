<?php

namespace IXR;

/**
 * lỗi IXR
 *
 * @package IXR
 */
class Error
{
    /**
     * mã lỗi
     *
     * @access public
     * @var integer
     */
    public $code;

    /**
     * thông báo lỗi
     *
     * @access public
     * @var string|null
     */
    public $message;

    /**
     * Người xây dựng
     *
     * @param integer $code mã lỗi
     * @param string|null $message thông báo lỗi
     */
    public function __construct(int $code, ?string $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Nhận xml
     *
     * @return string
     */
    public function getXml(): string
    {
        return <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;
    }
}
