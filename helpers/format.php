<?php

/**
 * Format Class
 */
class Format
{
    /**
     * Định dạng ngày giờ an toàn.
     * - Chấp nhận: string (Y-m-d, Y-m-d H:i:s, ...), timestamp (int), DateTimeInterface
     * - Nếu rỗng/không hợp lệ => trả chuỗi rỗng để tránh 1970-01-01.
     */
    public function formatDate($date, $format = 'F j, Y, g:i a')
    {
        if ($date instanceof \DateTimeInterface) {
            $ts = $date->getTimestamp();
        } elseif (is_numeric($date)) {
            $ts = (int)$date;
        } elseif (is_string($date) && trim($date) !== '') {
            $ts = strtotime($date);
            if ($ts === false) {
                return ''; // không parse được
            }
        } else {
            return ''; // null, '', hoặc kiểu không hỗ trợ
        }

        return date($format, $ts);
    }

    public function textShorten($text, $limit = 400)
    {
        $text = (string)$text . " ";
        $text = substr($text, 0, $limit);
        $pos  = strrpos($text, ' ');
        if ($pos !== false) {
            $text = substr($text, 0, $pos);
        }
        return $text . ".....";
    }

    public function validation($data)
    {
        if ($data === null) {
            return '';
        }
        $data = trim((string)$data);
        $data = stripslashes($data); // sửa stripcslashes -> stripslashes
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $data;
    }

    public function title()
    {
        $path  = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $title = basename($path, '.php');
        if ($title === 'index') {
            $title = 'home';
        } elseif ($title === 'contact') {
            $title = 'contact';
        }
        return ucfirst($title);
    }

    /**
     * Định dạng tiền tệ an toàn (không dùng preg_replace).
     * - $fractional: true => 2 chữ số thập phân
     * - $dec_point / $thousands_sep: có thể đổi theo nhu cầu
     *   Mặc định: kiểu EN (1,234.56). Muốn kiểu VN => dec_point ',', thousands '.'.
     */
    public function formatMoney($number, $fractional = false, $dec_point = '.', $thousands_sep = ',')
    {
        if ($number === null || $number === '') {
            $number = 0;
        }
        // ép về số
        $num = is_numeric($number) ? (float)$number : 0.0;

        $decimals = $fractional ? 2 : 0;
        return number_format($num, $decimals, $dec_point, $thousands_sep);
    }
}
