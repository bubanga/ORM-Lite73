<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ORMLite;


use DevLancer\ORMLite\Exception\BadEntityException;
use ReflectionClass;
use ReflectionProperty;

class ClassMetadata
{
    public function getReflectionClass($class)
    {
        $r = new ReflectionClass($class);
        $annot = $this->getAnnotations($r->getDocComment());

        $properties = $this->getReflectionProperity($class);
        foreach ($properties as $property) {
            if (isset($property['id'])) {
                $annot['id'] = [$property['column'], $property['type']];
                break;
            }
        }

        if (!isset($annot['id'])) {
            $annot['id'] = null;
        }

        return $annot;
    }

    public function getReflectionProperity($class)
    {
        $r = new ReflectionClass($class);

        $result = [];
        foreach ($r->getProperties() as $property) {
            $rp = new ReflectionProperty($class, $property->name);
            if ($rp->getDocComment() !== false)
                $result[$property->name] = $this->getAnnotations($rp->getDocComment());

            if (!isset($result[$property->name]['column'])) {
                $column = preg_split('/(?=[A-Z])/',$property->name);
                $column = strtolower(implode("_", $column));
                $result[$property->name]['column'] = $column;
            }

            if (!isset($result[$property->name]['type'])) {
                throw new BadEntityException("Not found annotation type for $property->name in $class");
            }
        }

        return $result;
    }

    private function getAnnotations(string $doc): array
    {
        preg_match_all('#@ORMLite\/(.*)\((.*)\)#', $doc, $annotations);
        $result = [];

        foreach ($annotations[1] as $index => $key) {
            $value = json_decode($annotations[2][$index], true);
            $value = (is_null($value))? $annotations[2][$index] : $value;
            $result[strtolower($key)] = $value;
        }

        return $result;
    }
}