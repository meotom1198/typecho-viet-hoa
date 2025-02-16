<?php

namespace Typecho;

/**
 * Lớp cơ sở ngoại lệ Typecho
 * Chức năng in ngoại lệ chủ yếu bị quá tải
 *
 * @package Exception
 */
class Exception extends \Exception
{

    public function __construct($message, $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
    }
}
