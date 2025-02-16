<?php

namespace IXR;

/**
 * Mã hóa IXR Base64
 *
 * @package IXR
 */
class Base64
{
    /**
     * dữ liệu được mã hóa
     *
     * @var string
     */
    private $data;

    /**
     * dữ liệu khởi tạo
     *
     * @param string $data
     */
    public function __construct(string $data)
    {
        $this->data = $data;
    }

    /**
     * Nhận dữ liệu XML
     *
     * @return string
     */
    public function getXml()
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}
