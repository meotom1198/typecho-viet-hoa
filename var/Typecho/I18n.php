<?php

namespace Typecho;

use Typecho\I18n\GetTextMulti;

/**
 * Dịch ký tự quốc tế hóa
 *
 * @package I18n
 */
class I18n
{
    /**
     * Cờ đã được tải chưa
     *
     * @access private
     * @var GetTextMulti
     */
    private static $loaded;

    /**
     * tập tin ngôn ngữ
     *
     * @access private
     * @var string
     */
    private static $lang = null;

    /**
     * Dịch văn bản
     *
     * @access public
     *
     * @param string $string Văn bản cần dịch
     *
     * @return string
     */
    public static function translate(string $string): string
    {
        self::init();
        return self::$loaded ? self::$loaded->translate($string) : $string;
    }

    /**
     * Khởi tạo tập tin ngôn ngữ
     *
     * @access private
     */
    private static function init()
    {
        /** Hỗ trợ GetText */
        if (!isset(self::$loaded) && self::$lang && file_exists(self::$lang)) {
            self::$loaded = new GetTextMulti(self::$lang);
        }
    }

    /**
     * Hàm dịch cho dạng số nhiều
     *
     * @param string $single Dịch dạng số ít
     * @param string $plural Dịch dạng số nhiều
     * @param integer $number con số
     * @return string
     */
    public static function ngettext(string $single, string $plural, int $number): string
    {
        self::init();
        return self::$loaded ? self::$loaded->ngettext($single, $plural, $number) : ($number > 1 ? $plural : $single);
    }

    /**
     * thời gian từ vựng hóa
     *
     * @access public
     *
     * @param int $from thời gian bắt đầu
     * @param int $now thời gian kết thúc
     *
     * @return string
     */
    public static function dateWord(int $from, int $now): string
    {
        $between = $now - $from;

        /** nếu có một ngày */
        if ($between >= 0 && $between < 86400 && date('d', $from) == date('d', $now)) {
            /** nếu là một giờ */
            if ($between < 3600) {
                /** nếu là một phút */
                if ($between < 60) {
                    if (0 == $between) {
                        return _t('vừa xong');
                    } else {
                        return str_replace('%d', $between, _n('một giây trước', '%d giây trước', $between));
                    }
                }

                $min = floor($between / 60);
                return str_replace('%d', $min, _n('một phút trước', '%d phút trước', $min));
            }

            $hour = floor($between / 3600);
            return str_replace('%d', $hour, _n('một giờ trước', '%d giờ trước', $hour));
        }

        /** nếu là ngày hôm qua */
        if (
            $between > 0
            && $between < 172800
            && (date('z', $from) + 1 == date('z', $now)                             // trong cùng một năm
                || date('z', $from) + 1 == date('L') + 365 + date('z', $now))
        ) {    // Tình hình đêm giao thừa
            return _t('Hôm qua %s', date('H:i', $from));
        }

        /** nếu đó là một tuần */
        if ($between > 0 && $between < 604800) {
            $day = floor($between / 86400);
            return str_replace('%d', $day, _n('một ngày trước', '%d ngày trước', $day));
        }

        /** trong trường hợp */
        if (date('Y', $from) == date('Y', $now)) {
            return date(_t('n-j'), $from);
        }

        return date(_t('d-m-Y'), $from);
    }

    /**
     * Thêm mục ngôn ngữ
     *
     * @access public
     *
     * @param string $lang Tên ngôn ngữ
     *
     * @return void
     */
    public static function addLang(string $lang)
    {
        self::$loaded->addFile($lang);
    }

    /**
     * Nhận mục ngôn ngữ
     *
     * @access public
     * @return string
     */
    public static function getLang(): ?string
    {
        return self::$lang;
    }

    /**
     * Đặt mục ngôn ngữ
     *
     * @access public
     *
     * @param string $lang Thông tin cấu hình
     *
     * @return void
     */
    public static function setLang(string $lang)
    {
        self::$lang = $lang;
    }
}
