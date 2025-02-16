<?php

namespace Utils;

/**
 * AutoP
 *
 * @copyright Copyright (c) 2012 Typecho Team. (http://typecho.org)
 * @author Joyqi <magike.net@gmail.com>
 * @license GNU General Public License 2.0
 */
class AutoP
{
    // dưới dạng thẻ đoạn
    private const BLOCK = 'p|pre|div|blockquote|form|ul|ol|dd|table|ins|h1|h2|h3|h4|h5|h6';

    /**
     * id duy nhất
     *
     * @access private
     * @var integer
     */
    private $uniqueId = 0;

    /**
     * đoạn đã lưu
     *
     * @access private
     * @var array
     */
    private $blocks = [];

    /**
     * Chức năng gọi lại để thay thế đoạn văn
     *
     * @param array $matches giá trị phù hợp
     * @return string
     */
    public function replaceBlockCallback(array $matches): string
    {
        $tagMatch = '|' . $matches[1] . '|';
        $text = $matches[4];

        switch (true) {
            /** Sử dụng br để xử lý ngắt dòng */
            case false !== strpos(
                '|li|dd|dt|td|p|a|span|cite|strong|sup|sub|small|del|u|i|b|ins|h1|h2|h3|h4|h5|h6|',
                $tagMatch
            ):
                $text = nl2br(trim($text));
                break;
            /** Xử lý ngắt dòng với đoạn văn */
            case false !== strpos('|div|blockquote|form|', $tagMatch):
                $text = $this->cutByBlock($text);
                if (false !== strpos($text, '</p><p>')) {
                    $text = $this->fixParagraph($text);
                }
                break;
            default:
                break;
        }

        /** Thẻ không có khả năng đoạn văn */
        if (false !== strpos('|a|span|font|code|cite|strong|sup|sub|small|del|u|i|b|', $tagMatch)) {
            $key = '<b' . $matches[2] . '/>';
        } else {
            $key = '<p' . $matches[2] . '/>';
        }

        $this->blocks[$key] = "<{$matches[1]}{$matches[3]}>{$text}</{$matches[1]}>";
        return $key;
    }

    /**
     * Sử dụng phương pháp đoạn văn để xử lý ngắt dòng
     *
     * @param string $text
     * @return string
     */
    private function cutByBlock(string $text): string
    {
        $space = "( |　)";
        $text = str_replace("\r\n", "\n", trim($text));
        $text = preg_replace("/{$space}*\n{$space}*/is", "\n", $text);
        $text = preg_replace("/\s*<p:([0-9]{4})\/>\s*/is", "</p><p:\\1/><p>", $text);
        $text = preg_replace("/\n{2,}/", "</p><p>", $text);
        $text = nl2br($text);
        $text = preg_replace("/(<p>)?\s*<p:([0-9]{4})\/>\s*(<\/p>)?/is", "<p:\\2/>", $text);
        $text = preg_replace("/<p>{$space}*<\/p>/is", '', $text);
        $text = preg_replace("/\s*<p>\s*$/is", '', $text);
        $text = preg_replace("/^\s*<\/p>\s*/is", '', $text);
        return $text;
    }

    /**
     * Sửa đầu và cuối đoạn
     *
     * @param string $text
     * @return string
     */
    private function fixParagraph(string $text): string
    {
        $text = trim($text);
        if (!preg_match("/^<(" . self::BLOCK . ")(\s|>)/i", $text)) {
            $text = '<p>' . $text;
        }

        if (!preg_match("/<\/(" . self::BLOCK . ")>$/i", $text)) {
            $text = $text . '</p>';
        }

        return $text;
    }

    /**
     * phân đoạn tự động
     *
     * @param string $text
     * @return string
     */
    public function parse(string $text): string
    {
        /** đặt lại bộ đếm */
        $this->uniqueId = 0;
        $this->blocks = [];

        /** Loại bỏ ngắt dòng sau đoạn văn hiện có */
        $text = preg_replace(["/<\/p>\s+<p(\s*)/is", "/\s*<br\s*\/?>\s*/is"], ["</p><p\\1", "<br />"], trim($text));

        /** Phân tích tất cả các thẻ không tự đóng thành các chuỗi duy nhất */
        $foundTagCount = 0;
        $textLength = strlen($text);
        $uniqueIdList = [];

        if (preg_match_all("/<\/\s*([a-z0-9]+)>/is", $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $key => $match) {
                $tag = $matches[1][$key][0];

                $leftOffset = $match[1] - $textLength;
                $posSingle = strrpos($text, '<' . $tag . '>', $leftOffset);
                $posFix = strrpos($text, '<' . $tag . ' ', $leftOffset);
                $pos = false;

                switch (true) {
                    case (false !== $posSingle && false !== $posFix):
                        $pos = max($posSingle, $posFix);
                        break;
                    case false === $posSingle && false !== $posFix:
                        $pos = $posFix;
                        break;
                    case false !== $posSingle && false === $posFix:
                        $pos = $posSingle;
                        break;
                    default:
                        break;
                }

                if (false !== $pos) {
                    $uniqueId = $this->makeUniqueId();
                    $uniqueIdList[$uniqueId] = $tag;
                    $tagLength = strlen($tag);

                    $text = substr_replace($text, $uniqueId, $pos + 1 + $tagLength, 0);
                    $text = substr_replace(
                        $text,
                        $uniqueId,
                        $match[1] + 7 + $foundTagCount * 10 + $tagLength,
                        0
                    ); // 7 = 5 + 2
                    $foundTagCount++;
                }
            }
        }

        foreach ($uniqueIdList as $uniqueId => $tag) {
            $text = preg_replace_callback(
                "/<({$tag})({$uniqueId})([^>]*)>(.*)<\/\\1\\2>/is",
                [$this, 'replaceBlockCallback'],
                $text,
                1
            );
        }

        $text = $this->cutByBlock($text);
        $blocks = array_reverse($this->blocks);

        foreach ($blocks as $blockKey => $blockValue) {
            $text = str_replace($blockKey, $blockValue, $text);
        }

        return $this->fixParagraph($text);
    }

    /**
     * Tạo một ID duy nhất và hỗ trợ xử lý tối đa 10.000 thẻ để tăng tốc.
     *
     * @return string
     */
    private function makeUniqueId(): string
    {
        return ':' . str_pad($this->uniqueId ++, 4, '0', STR_PAD_LEFT);
    }
}

