<?php

namespace Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Db\Exception as DbException;

/**
 * Lớp ngoại lệ kết nối cơ sở dữ liệu
 *
 * @package Db
 */
class SQLException extends DbException
{
}
