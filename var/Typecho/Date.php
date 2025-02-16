<?php

namespace Typecho;

/**
 * Xử lý ngày
 *
 * @author qining
 * @category typecho
 * @package Date
 */
class Date
{
    /**
     * Độ lệch múi giờ mong muốn
     *
     * @access public
     * @var integer
     */
    public static $timezoneOffset = 0;

    /**
     * Độ lệch múi giờ của máy chủ
     *
     * @access public
     * @var integer
     */
    public static $serverTimezoneOffset = 0;

    /**
     * Dấu thời gian của máy chủ hiện tại
     *
     * @access public
     * @var integer
     */
    public static $serverTimeStamp;

    /**
     * Dấu thời gian có thể được chuyển đổi trực tiếp
     *
     * @access public
     * @var integer
     */
    public $timeStamp = 0;

    /**
     * @var string
     */
    public $year;

    /**
     * @var string
     */
    public $month;

    /**
     * @var string
     */
    public $day;

    /**
     * Thông số khởi tạo
     *
     * @param integer|null $time Dấu thời gian
     */
    public function __construct(?int $time = null)
    {
        $this->timeStamp = (null === $time ? self::time() : $time)
            + (self::$timezoneOffset - self::$serverTimezoneOffset);

        $this->year = date('Y', $this->timeStamp);
        $this->month = date('m', $this->timeStamp);
        $this->day = date('d', $this->timeStamp);
    }

    /**
     * Đặt độ lệch múi giờ mong muốn hiện tại
     *
     * @param integer $offset
     */
    public static function setTimezoneOffset(int $offset)
    {
        self::$timezoneOffset = $offset;
        self::$serverTimezoneOffset = idate('Z');
    }

    /**
     * Nhận thời gian định dạng
     *
     * @param string $format định dạng thời gian
     * @return string
     */
    public function format(string $format): string
    {
        return date($format, $this->timeStamp);
    }

    /**
     * Nhận thời gian bù đắp quốc tế hóa
     *
     * @return string
     */
    public function word(): string
    {
        return I18n::dateWord($this->timeStamp, self::time() + (self::$timezoneOffset - self::$serverTimezoneOffset));
    }

    /**
     * Nhận giờ GMT
     *
     * @deprecated
     * @return int
     */
    public static function gmtTime(): int
    {
        return self::time();
    }

    /**
     * Nhận thời gian của máy chủ
     *
     * @return int
     */
    public static function time(): int
    {
        return self::$serverTimeStamp ?: (self::$serverTimeStamp = time());
    }
}
