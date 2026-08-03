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
use function count;
use function spl_object_id;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;
use stdClass;

#[CoversClass(Context::class)]
#[Small]
final class ContextTest extends TestCase
{
    /**
     * @return non-empty-list<array{0: array<mixed>|object, 1: int}>
     */
    public static function valuesProvider(): array
    {
        $obj2      = new stdClass;
        $obj2->foo = 'bar';

        $obj3 = (object) [1, 2, "Test\r\n", 4, 5, 6, 7, 8];

        $obj = new stdClass;
        // @codingStandardsIgnoreStart
        $obj->null = null;
        // @codingStandardsIgnoreEnd
        $obj->boolean     = true;
        $obj->integer     = 1;
        $obj->double      = 1.2;
        $obj->string      = '1';
        $obj->text        = "this\nis\na\nvery\nvery\nvery\nvery\nvery\nvery\rlong\n\rtext";
        $obj->object      = $obj2;
        $obj->objectagain = $obj2;
        $obj->array       = ['foo' => 'bar'];
        $obj->array2      = [1, 2, 3, 4, 5, 6];
        $obj->array3      = [$obj, $obj2, $obj3];
        $obj->self        = $obj;

        $storage = new SplObjectStorage;
        $storage->offsetSet($obj2);

        return [
            [$obj, spl_object_id($obj)],
            [$obj2, spl_object_id($obj2)],
            [$obj3, spl_object_id($obj3)],
            [$storage, spl_object_id($storage)],
            [$obj->array, 0],
            [$obj->array2, 0],
            [$obj->array3, 0],
        ];
    }

    /**
     * @return non-empty-array<string, array{0: array<mixed>}>
     */
    public static function arraysProvider(): array
    {
        return [
            'empty'                 => [[]],
            'list'                  => [[1, 2, 3]],
            'string keys'           => [['foo' => 'bar']],
            'cannot be appended to' => [[PHP_INT_MAX => 'foo']],
        ];
    }

    /**
     * @param array<mixed>|object $value
     */
    #[DataProvider('valuesProvider')]
    public function testAdd(array|object $value, int $key): void
    {
        $context = new Context;

        $this->assertSame($key, $context->add($value));

        // Test we get the same key on subsequent adds
        $this->assertSame($key, $context->add($value));
    }

    public function testAddWorksForArrayThatCannotBeAppendedTo(): void
    {
        $context = new Context;

        $a = [PHP_INT_MAX => 'foo'];

        $key = $context->add($a);

        /* The key returned by add() must be the key that identifies the array
         * from then on: contains() as well as subsequent calls to add() have to
         * report the very same key. */
        $this->assertSame($key, $context->contains($a));
        $this->assertSame($key, $context->add($a));
    }

    public function testEmptyArrayCanBeAdded(): void
    {
        $context = new Context;

        $a = [];

        $this->assertFalse($context->contains($a));
        $this->assertSame(0, $context->add($a));
        $this->assertSame(0, $context->contains($a));
    }

    public function testArrayWithHolesCanBeAdded(): void
    {
        $context = new Context;

        $a = [0 => 'a', 1 => 'b', 2 => 'c'];

        unset($a[0], $a[2]);

        $this->assertSame(0, $context->add($a));
        $this->assertSame(0, $context->contains($a));
    }

    public function testSelfReferencingArrayCanBeAdded(): void
    {
        $context = new Context;

        $a      = [];
        $a['a'] = &$a;

        $this->assertSame(0, $context->add($a));
        $this->assertSame(0, $context->contains($a));
    }

    public function testDistinctArraysAreAssignedDistinctKeys(): void
    {
        $context = new Context;

        $a = ['a'];
        $b = ['b'];
        $c = ['c'];

        $this->assertSame(0, $context->add($a));
        $this->assertSame(1, $context->add($b));
        $this->assertSame(2, $context->add($c));

        $this->assertSame(0, $context->contains($a));
        $this->assertSame(1, $context->contains($b));
        $this->assertSame(2, $context->contains($c));
    }

    public function testDistinctObjectsAreAssignedDistinctKeys(): void
    {
        $context = new Context;

        $a = new stdClass;
        $b = new stdClass;

        $this->assertNotSame($context->add($a), $context->add($b));
    }

    public function testArrayAddedToOneContextIsNotContainedInAnotherContext(): void
    {
        $first  = new Context;
        $second = new Context;

        $a = ['a'];

        $first->add($a);

        $this->assertSame(0, $first->contains($a));
        $this->assertFalse($second->contains($a));
    }

    public function testObjectAddedToOneContextIsNotContainedInAnotherContext(): void
    {
        $first  = new Context;
        $second = new Context;

        $o = new stdClass;

        $first->add($o);

        $this->assertSame(spl_object_id($o), $first->contains($o));
        $this->assertFalse($second->contains($o));
    }

    public function testArrayThatOnlyLooksLikeItHasBeenAddedIsNotContained(): void
    {
        $context = new Context;

        /* An array cannot be mistaken for one that was added to the context
         * just because its last elements happen to have the shape that is used
         * to mark arrays that were added. */
        $a = [0, new SplObjectStorage];

        $this->assertFalse($context->contains($a));
    }

    public function testAddingAnArrayTwiceMarksItOnlyOnce(): void
    {
        $context = new Context;

        $a = ['a', 'b'];

        $context->add($a);

        $numberOfElements = count($a);

        $context->add($a);

        $this->assertCount($numberOfElements, $a);
    }

    /**
     * @param array<mixed> $value
     */
    #[DataProvider('arraysProvider')]
    public function testArrayIsRestoredWhenContextIsDestroyed(array $value): void
    {
        $original = $value;

        $context = new Context;
        $context->add($value);

        $this->assertNotSame($original, $value);

        unset($context);

        $this->assertSame($original, $value);
    }

    /**
     * @param array<mixed>|object $value
     */
    #[DataProvider('valuesProvider')]
    public function testContainsFound(array|object $value, int $key): void
    {
        $context = new Context;

        $context->add($value);

        $this->assertSame($key, $context->contains($value));

        // Test we get the same key on subsequent calls
        $this->assertSame($key, $context->contains($value));
    }

    /**
     * @param array<mixed>|object $value
     */
    #[DataProvider('valuesProvider')]
    public function testContainsNotFound(array|object $value, int $key): void
    {
        $context = new Context;

        $this->assertFalse($context->contains($value));
    }
}
