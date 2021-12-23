<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ORMLite;


trait EntityTrait
{
    public function __construct()
    {
        Container::add($this);
    }

    public function __destruct()
    {
        Container::remove($this);
    }

    private function getProperty(string $property): string
    {
        $property = explode("_", $property);
        $first = false;
        foreach ($property as &$item) {
            if (!$first) {
                $first = true;
                continue;
            }

            $item = ucfirst($item);
        }

        return implode("",$property);
    }

    public function __isset($name) {
        echo "Non-existent property '$name'";
    }

    public function __get($property) {
        $property = $this->getProperty($property);
        if (property_exists($this, $property)) {
            return $this->{$property};
        }
    }

    public function __set($property, $value) {
        $property = $this->getProperty($property);
        if (property_exists($this, $property)) {
            $this->{$property} = $value;
        }
    }
}