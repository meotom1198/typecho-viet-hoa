<?php

namespace Typecho\I18n;

/**
 * Được sử dụng để giải quyết các vấn đề về đọc và ghi do nhiều tệp mo gây ra
 * Chúng tôi viết lại lớp đọc file
 *
 * @author qining
 * @category typecho
 * @package I18n
 */
class GetTextMulti
{
    /**
     * Tất cả các thẻ điều khiển đọc và ghi tập tin
     *
     * @access private
     * @var GetText[]
     */
    private $handlers = [];

    /**
     * Người xây dựng
     *
     * @access public
     * @param string $fileName Tên tập tin ngôn ngữ
     * @return void
     */
    public function __construct(string $fileName)
    {
        $this->addFile($fileName);
    }

    /**
     * Thêm tập tin ngôn ngữ
     *
     * @access public
     * @param string $fileName Tên tập tin ngôn ngữ
     * @return void
     */
    public function addFile(string $fileName)
    {
        $this->handlers[] = new GetText($fileName, true);
    }

    /**
     * Translates a string
     *
     * @access public
     * @param string string to be translated
     * @return string translated string (or original, if not found)
     */
    public function translate(string $string): string
    {
        foreach ($this->handlers as $handle) {
            $string = $handle->translate($string, $count);
            if (- 1 != $count) {
                break;
            }
        }

        return $string;
    }

    /**
     * Plural version of gettext
     *
     * @access public
     * @param string single
     * @param string plural
     * @param string number
     * @return string translated plural form
     */
    public function ngettext($single, $plural, $number): string
    {
        $count = - 1;

        foreach ($this->handlers as $handler) {
            $string = $handler->ngettext($single, $plural, $number, $count);
            if (- 1 != $count) {
                break;
            }
        }

        return $string;
    }

    /**
     * 关闭所有句柄
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->handlers as $handler) {
            /** 显示的释放内存 */
            unset($handler);
        }
    }
}
