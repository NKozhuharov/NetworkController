<?php

namespace Nevestul4o\NetworkController\Database;

class RandomDataGenerator
{
    /**
     * Gets a random sequence of letters and numbers with the specified length, with appended model name in front
     *
     * @param string $modelName
     * @param int $hashLength
     * @return string
     */
    public static function getRandomString(string $modelName = '', int $hashLength = 10): string
    {
        return $modelName . (!empty($modelName) ? '_' : '') . substr(strtoupper(sha1(uniqid())), 0, $hashLength);
    }

    /**
     * Gets a random sequence of numbers with the specified length
     *
     * @param int $hashLength
     * @return string
     */
    public static function getRandomNumericString(int $hashLength = 10): string
    {
        $result = '';
        for ($i = 0; $i < $hashLength; $i++) {
            $result .= rand(0, 9);
        }
        return $result;
    }

    /**
     * Gets a random sequence of letters and numbers and appends the current locale abbreviation in front
     *
     * @param string $modelName
     * @param int $hashLength
     * @return string
     */
    public static function getLocalizedRandomString(string $modelName = '', int $hashLength = 10): string
    {
        return strtoupper(app()->getLocale()) . '_' . self::getRandomString($modelName, $hashLength);
    }

    /**
     * Gets a string, representing IP address
     *
     * @return string
     */
    public static function getRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
    }

    /**
     * Gets a string, representing an User Agent
     *
     * @return string
     */
    public static function getRandomUserAgent(): string
    {
        $userAgentsList = [
            'Firefox',
            'Chrome',
            'Safari',
            'IE',
            'Opera',
        ];

        return $userAgentsList[rand(1, count($userAgentsList)) - 1];
    }

    /**
     * Gets a random element of the provided array
     *
     * @param array $array
     * @return mixed
     */
    public static function getRandomElementFromArray(array $array)
    {
        $array = array_values($array);

        return $array[rand(0, count($array) - 1)];
    }

    /**
     * Gets a random date between the provided dates. Dates should be Y-m-d.
     * Returned date is also Y-m-d.
     *
     * @param string $before
     * @param string $after
     * @return false|string
     */
    public static function getRandomDate(string $before = '', string $after = '')
    {
        $min = 1;
        $max = time();

        if (!empty($before)) {
            $max = strtotime($before);
        }

        if (!empty($after)) {
            $min = strtotime($after);
        }

        $timestamp = mt_rand($min, $max);

        return date("Y-m-d", $timestamp);
    }

    /**
     * Gets a random date between the provided dates. Dates should be Y-m-d H:i:s.
     * Returned date is also Y-m-d H:i:s.
     *
     * @param string $before
     * @param string $after
     * @return false|string
     */
    public static function getRandomDateTime(string $before = '', string $after = '')
    {
        $min = 1;
        $max = time();

        if (!empty($before)) {
            $max = strtotime($before);
        }

        if (!empty($after)) {
            $min = strtotime($after);
        }

        $timestamp = mt_rand($min, $max);

        return date("Y-m-d H:i:s", $timestamp);
    }

    /**
     * Generates an email with the specified length
     *
     * @param int $hashLength
     * @return string
     */
    public static function getRandomEmail(int $hashLength = 10): string
    {
        $domains = [
            'gmail.com',
            'yahoo.com',
            'mail.bg',
            'gmx.at',
            'abv.bg',
        ];

        return strtolower(self::getRandomString('', $hashLength)) . '@' . self::getRandomElementFromArray($domains);
    }
}
