<?php

namespace Widget\Users;

use Typecho\Db\Exception;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Các thành phần nội dung liên quan (được liên kết dựa trên thẻ)
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Author extends Users
{
    /**
     * Thực thi hàm, khởi tạo dữ liệu
     *
     * @throws Exception
     */
    public function execute()
    {
        if ($this->parameter->uid) {
            $this->db->fetchRow($this->select()
                ->where('uid = ?', $this->parameter->uid), [$this, 'push']);
        }
    }
}
