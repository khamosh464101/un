<?php

namespace App\Helpers;

class Transliterator
{
    /**
     * Transliteration mapping for English to Persian/Dari
     */
    private static $map = [
        // Vowels
        'a' => 'ا', 'A' => 'ا',
        'e' => 'ی', 'E' => 'ی',
        'i' => 'ی', 'I' => 'ی',
        'o' => 'و', 'O' => 'و',
        'u' => 'و', 'U' => 'و',
        
        // Consonants
        'b' => 'ب', 'B' => 'ب',
        'p' => 'پ', 'P' => 'پ',
        't' => 'ت', 'T' => 'ت',
        'j' => 'ج', 'J' => 'ج',
        'ch' => 'چ', 'Ch' => 'چ', 'CH' => 'چ',
        'h' => 'ح', 'H' => 'ح',
        'kh' => 'خ', 'Kh' => 'خ', 'KH' => 'خ',
        'd' => 'د', 'D' => 'د',
        'z' => 'ز', 'Z' => 'ز',
        'r' => 'ر', 'R' => 'ر',
        's' => 'س', 'S' => 'س',
        'sh' => 'ش', 'Sh' => 'ش', 'SH' => 'ش',
        'gh' => 'غ', 'Gh' => 'غ', 'GH' => 'غ',
        'f' => 'ف', 'F' => 'ف',
        'q' => 'ق', 'Q' => 'ق',
        'k' => 'ک', 'K' => 'ک',
        'g' => 'گ', 'G' => 'گ',
        'l' => 'ل', 'L' => 'ل',
        'm' => 'م', 'M' => 'م',
        'n' => 'ن', 'N' => 'ن',
        'w' => 'و', 'W' => 'و',
        'y' => 'ی', 'Y' => 'ی',
        
        // Special combinations
        'aa' => 'آ', 'Aa' => 'آ', 'AA' => 'آ',
    ];

    /**
     * Common Afghan/Dari names mapping for better accuracy
     */
    private static $commonNames = [
        'Ahmad' => 'احمد',
        'Ahmed' => 'احمد',
        'Aman' => 'امان',
        'Amanullah' => 'امان الله',
        'Abdul' => 'عبدال',
        'Abdullah' => 'عبدالله',
        'Ali' => 'علی',
        'Hassan' => 'حسن',
        'Hussain' => 'حسین',
        'Hussein' => 'حسین',
        'Mohammad' => 'محمد',
        'Mohammed' => 'محمد',
        'Muhammad' => 'محمد',
        'Mahmood' => 'محمود',
        'Mahmud' => 'محمود',
        'Hamid' => 'حمید',
        'Hameed' => 'حمید',
        'Rashid' => 'رشید',
        'Khalid' => 'خالد',
        'Karim' => 'کریم',
        'Kareem' => 'کریم',
        'Nasir' => 'ناصر',
        'Naser' => 'ناصر',
        'Omar' => 'عمر',
        'Umar' => 'عمر',
        'Yusuf' => 'یوسف',
        'Yousef' => 'یوسف',
        'Ibrahim' => 'ابراهیم',
        'Ismail' => 'اسماعیل',
        'Ismael' => 'اسماعیل',
        'Jalal' => 'جلال',
        'Jamal' => 'جمال',
        'Jamil' => 'جمیل',
        'Fahim' => 'فهیم',
        'Farid' => 'فرید',
        'Fareed' => 'فرید',
        'Habib' => 'حبیب',
        'Hakim' => 'حکیم',
        'Hakeem' => 'حکیم',
        'Latif' => 'لطیف',
        'Majid' => 'مجید',
        'Majeed' => 'مجید',
        'Noor' => 'نور',
        'Nur' => 'نور',
        'Rahman' => 'رحمان',
        'Rahim' => 'رحیم',
        'Raheem' => 'رحیم',
        'Sami' => 'سمیع',
        'Samee' => 'سمیع',
        'Tariq' => 'طارق',
        'Tarek' => 'طارق',
        'Wahid' => 'وحید',
        'Zahir' => 'ظاهر',
        'Zaher' => 'ظاهر',
        'Aziz' => 'عزیز',
        'Azeem' => 'عظیم',
        'Bashir' => 'بشیر',
        'Basheer' => 'بشیر',
        'Ghulam' => 'غلام',
        'Gul' => 'گل',
        'Jan' => 'جان',
        'Khan' => 'خان',
        'Shah' => 'شاه',
        'Mir' => 'میر',
        'Sayed' => 'سید',
        'Saeed' => 'سعید',
        'Said' => 'سعید',
        'Fatima' => 'فاطمه',
        'Fatemah' => 'فاطمه',
        'Maryam' => 'مریم',
        'Mariam' => 'مریم',
        'Zainab' => 'زینب',
        'Zaynab' => 'زینب',
        'Khadija' => 'خدیجه',
        'Khadijah' => 'خدیجه',
        'Aisha' => 'عایشه',
        'Ayesha' => 'عایشه',
        'Bibi' => 'بی بی',
        'Nadia' => 'نادیه',
        'Nadya' => 'نادیه',
        'Razia' => 'رضیه',
        'Raazia' => 'رضیه',
        'Shirin' => 'شیرین',
        'Shireen' => 'شیرین',
        'Soraya' => 'ثریا',
        'Suraya' => 'ثریا',
        'Andam' => 'اندام',
        'Gul Andam' => 'گل اندام',
        'Gul Ahmad' => 'گل احمد',
        'Gul Mohammad' => 'گل محمد',
        'Gul Hassan' => 'گل حسن',
    ];

    /**
     * Transliterate English name to Persian/Dari
     * 
     * @param string|null $name
     * @return string
     */
    public static function toPersian(?string $name): string
    {
        if (empty($name)) {
            return '';
        }

        $name = trim($name);

        // Check if already in Persian/Dari (contains Persian characters)
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $name)) {
            return $name;
        }

        // Check common names first for better accuracy (exact match)
        if (isset(self::$commonNames[$name])) {
            return self::$commonNames[$name];
        }

        // Check case-insensitive
        foreach (self::$commonNames as $english => $persian) {
            if (strcasecmp($english, $name) === 0) {
                return $persian;
            }
        }

        // Handle multi-word names (e.g., "Gul Andam" -> "گل اندام")
        if (strpos($name, ' ') !== false) {
            $words = explode(' ', $name);
            $transliteratedWords = [];
            
            foreach ($words as $word) {
                $word = trim($word);
                if (empty($word)) {
                    continue;
                }
                
                // Check if individual word is in common names
                if (isset(self::$commonNames[$word])) {
                    $transliteratedWords[] = self::$commonNames[$word];
                } else {
                    // Check case-insensitive
                    $found = false;
                    foreach (self::$commonNames as $english => $persian) {
                        if (strcasecmp($english, $word) === 0) {
                            $transliteratedWords[] = $persian;
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        // Fallback to character-by-character
                        $transliteratedWords[] = self::transliterateByCharacter($word);
                    }
                }
            }
            
            return implode(' ', $transliteratedWords);
        }

        // Fallback to character-by-character transliteration
        return self::transliterateByCharacter($name);
    }

    /**
     * Transliterate character by character
     * 
     * @param string $text
     * @return string
     */
    private static function transliterateByCharacter(string $text): string
    {
        $result = '';
        $length = strlen($text);
        $i = 0;

        while ($i < $length) {
            $matched = false;

            // Try to match 2-character combinations first
            if ($i < $length - 1) {
                $twoChar = substr($text, $i, 2);
                if (isset(self::$map[$twoChar])) {
                    $result .= self::$map[$twoChar];
                    $i += 2;
                    $matched = true;
                }
            }

            // If no 2-char match, try single character
            if (!$matched) {
                $oneChar = $text[$i];
                if (isset(self::$map[$oneChar])) {
                    $result .= self::$map[$oneChar];
                } else {
                    // Keep unmapped characters as-is
                    $result .= $oneChar;
                }
                $i++;
            }
        }

        return $result;
    }

    /**
     * Transliterate multiple names at once
     * 
     * @param array $names
     * @return array
     */
    public static function batchToPersian(array $names): array
    {
        $results = [];
        foreach ($names as $key => $name) {
            $results[$key] = self::toPersian($name);
        }
        return $results;
    }
}
