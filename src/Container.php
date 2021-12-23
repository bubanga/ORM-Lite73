<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ORMLite;


/**
 * Class Container
 * @package DevLancer\ORMLite
 */
class Container
{

    /**
     * @var array
     */
    private static $container = [];

    /**
     *
     */
    public static function reset()
    {
        self::$container = [];
    }

    /**
     * @param $value
     */
    public static function add($value)
    {
        self::$container[] = $value;
    }

    /**
     * @param $value
     */
    public static function remove($value)
    {
        foreach (self::$container as $index => $item) {
            if ($item === $value) {
                unset(self::$container[$index]);
            }
        }
    }

    /**
     * @return array
     */
    public static function get(): array
    {
        return self::$container;
    }

    /**
     * @param $value
     * @param false $strict
     * @return array
     */
    public static function search($value, $strict = false)
    {
        $result = [];

        foreach (self::get() as $key => $item) {
            if ($strict) {
                if ($item === $value)
                    $result[] = $key;
            } else {
                if ($item == $value)
                    $result[] = $key;
            }
        }

        return $result;
    }
}