<?php

namespace Typecho\Http\Client;

use Typecho\Exception as TypechoException;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Lớp ngoại lệ của máy khách http
 *
 * @package Http
 */
class Exception extends TypechoException
{
}
