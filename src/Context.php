<?php declare(strict_types=1);
/*
 * This file is part of sebastian/recursion-context.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\RecursionContext;

use const PHP_INT_MAX;
use const PHP_INT_MIN;
use function array_key_exists;
use function array_key_last;
use function array_pop;
use function count;
use function is_array;
use function random_int;
use function spl_object_id;
use SplObjectStorage;

final class Context
{
    /**
     * @var list<array<mixed>>
     */
    private array $arrays = [];

    /**
     * @var SplObjectStorage<object, null>
     */
    private SplObjectStorage $objects;

    public function __construct()
    {
        $this->objects = new SplObjectStorage;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __destruct()
    {
        foreach ($this->arrays as &$array) {
            if (is_array($array) && $this->containsArray($array) !== false) {
                array_pop($array);
            }
        }
    }

    /**
     * @template T of object|array
     *
     * @param T $value
     *
     * @param-out T $value
     */
    public function add(array|object &$value): int
    {
        if (is_array($value)) {
            /* @phpstan-ignore paramOut.type */
            return $this->addArray($value);
        }

        return $this->addObject($value);
    }

    /**
     * @template T of object|array
     *
     * @param T $value
     *
     * @param-out T $value
     */
    public function contains(array|object &$value): false|int
    {
        if (is_array($value)) {
            return $this->containsArray($value);
        }

        return $this->containsObject($value);
    }

    /**
     * @param array<mixed> $array
     */
    private function addArray(array &$array): int
    {
        $key = $this->containsArray($array);

        if ($key !== false) {
            return $key;
        }

        $key            = count($this->arrays);
        $this->arrays[] = &$array;
        $marker         = new Marker($this->objects, $key);

        if (!array_key_exists(PHP_INT_MAX, $array)) {
            $array[] = $marker;
        } else {
            /* Cover the improbable case, too: an element cannot be appended to
             * an array that already has an element with the largest possible
             * integer key.
             *
             * Note that containsArray() looks at the last element of the array,
             * which is the element written below regardless of the key used for
             * it. Therefore, the actual key is not important. */
            do {
                /** @noinspection PhpUnhandledExceptionInspection */
                $markerKey = random_int(PHP_INT_MIN, PHP_INT_MAX);
            } while (array_key_exists($markerKey, $array));

            $array[$markerKey] = $marker;
        }

        return $key;
    }

    private function addObject(object $object): int
    {
        $this->objects->offsetSet($object);

        return spl_object_id($object);
    }

    /**
     * @param array<mixed> $array
     */
    private function containsArray(array $array): false|int
    {
        $key = array_key_last($array);

        if ($key === null) {
            return false;
        }

        $last = $array[$key];

        if ($last instanceof Marker && $last->owner === $this->objects) {
            return $last->key;
        }

        return false;
    }

    private function containsObject(object $value): false|int
    {
        if ($this->objects->offsetExists($value)) {
            return spl_object_id($value);
        }

        return false;
    }
}
