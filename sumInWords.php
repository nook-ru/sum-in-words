<?php

declare(strict_types=1);

/**
 * Возвращает нужную форму множественного числа в зависимости от количества
 *
 * Пример вызова:
 *     pluralForm(1, ["рубль", "рубля", "рублей"]);
 *
 * @param float|int $number Число
 * @param string[] $titles Подписи для 1, 3 и 5
 * @param bool $includeNumber Включать число в результат
 * @return string
 */
function pluralForm($number, array $titles, bool $includeNumber = false): string
{
    static $cases = [2, 0, 1, 1, 1, 2];

    return ($includeNumber ? $number . ' ' : '') . $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

/**
 * Сумма прописью
 *
 * Пример вызова:
 *     sumInWords(123);             // 123 рубля
 *     sumInWords(123.12);          // 123 рубля 12 копеек
 *     sumInWords(123.12, true);    // 123 рубля
 *
 * @param float|int|string $sum Сумма
 * @param bool $roundSum Округлять и не выводить копейки?
 * @throws OverflowException
 * @return string
 */
function sumInWords($sum, bool $roundSum = false): string
{
    #region Справочники
    $str[100] = [
        '',
        'сто',
        'двести',
        'триста',
        'четыреста',
        'пятьсот',
        'шестьсот',
        'семьсот',
        'восемьсот',
        'девятьсот',
        'тысяча',
    ];
    $str[11] = [
        10 => 'десять',
        11 => 'одиннадцать',
        12 => 'двенадцать',
        13 => 'тринадцать',
        14 => 'четырнадцать',
        15 => 'пятнадцать',
        16 => 'шестнадцать',
        17 => 'семнадцать',
        18 => 'восемнадцать',
        19 => 'девятнадцать',
    ];
    $str[10] = [
        '',
        '',
        'двадцать',
        'тридцать',
        'сорок',
        'пятьдесят',
        'шестьдесят',
        'семьдесят',
        'восемьдесят',
        'девяносто',
        'сто',
    ];
    $sex[1] = [
        '',
        'один',
        'два',
        'три',
        'четыре',
        'пять',
        'шесть',
        'семь',
        'восемь',
        'девять',
    ];
    $sex[2] = [
        '',
        'одна',
        'две',
        'три',
        'четыре',
        'пять',
        'шесть',
        'семь',
        'восемь',
        'девять',
    ];
    $forms = [
        -1 => ['копейка', 'копейки', 'копеек', 2],
        0 => ['рубль', 'рубля', 'рублей', 1], // 10^0
        1 => ['тысяча', 'тысячи', 'тысяч', 2], // 10^3
        2 => ['миллион', 'миллиона', 'миллионов', 1], // 10^6
        3 => ['миллиард', 'миллиарда', 'миллиардов', 1], // 10^9
        4 => ['триллион', 'триллиона', 'триллионов', 1], // 10^12
        5 => ['квадриллион', 'квадриллиона', 'квадриллионов', 1], // 10^15
        6 => ['квинтиллион', 'квинтиллиона', 'квинтиллионов', 1], // 10^18
        7 => ['секстиллион', 'секстиллиона', 'секстиллионов', 1], // 10^21
        8 => ['септиллион', 'септиллиона', 'септиллионов', 1], // 10^24
        9 => ['октиллион', 'октиллиона', 'октиллионов', 1], // 10^27
        10 => ['нониллион', 'нониллиона', 'нониллионов', 1], // 10^30
        11 => ['дециллион', 'дециллиона', 'дециллионов', 1], // 10^33
    ];
    #endregion
    $noDigitsAfter = static function ($index) use (&$levels) {
        return (int)implode('', array_slice($levels, $index)) === 0;
    };
    $splitThousands = static function ($value) {
        $grouped = strrev(rtrim(chunk_split(strrev($value), 3, '-'), '-'));
        return explode('-', $grouped);
    };
    $out = [];
    // Поехали!
    $tmp = explode('.', str_replace(',', '.', $sum));
    // нормализация копеек
    $kop = isset($tmp[1]) ? str_pad(substr($tmp[1], 0, 2), 2, '0', STR_PAD_LEFT) : '00';
    $levels = $splitThousands($tmp[0]);
    $offset = sizeof($levels) - 1;
    foreach ($levels as $k => $lev) {
        $lev = str_pad($lev, 3, '0', STR_PAD_LEFT); // нормализация
        $ind = $offset - $k; // индекс для $forms
        if (!isset($forms[$ind])) {
            throw new OverflowException();
        }
        if ($lev[0] != '0') {
            $out[] = $str[100][$lev[0]];
        } // сотни
        $lev = $lev[1] . $lev[2];
        $lev = (int)$lev;
        if ($lev > 19) { // больше девятнадцати
            $lev = '' . $lev;
            $out[] = $str[10][$lev[0]];
            $out[] = $sex[$forms[$ind][3]][$lev[1]];
        } else {
            if ($lev > 9) {
                $out[] = $str[11][$lev];
            } else {
                if ($lev > 0) {
                    $out[] = $sex[$forms[$ind][3]][$lev];
                }
            }
        }
        if ($lev > 0 || $ind == 0 || $noDigitsAfter($k + 1)) {
            if ($ind == 0 && $out == []) {
                $out[] = 'ноль';
            }
            if ($noDigitsAfter($k)) {
                $out[] = 'рублей';
                break;
            } else {
                $out[] = pluralForm($lev, $forms[$ind]);
            }
        }
    }
    if (!$roundSum) {
        $out[] = $kop; // копейки
        $out[] = pluralForm($kop, $forms[-1]);
    }

    return implode(' ', array_filter($out));
}
