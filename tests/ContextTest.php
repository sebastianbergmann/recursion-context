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
        $storage->attach($obj2);

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

    public function testAdd2(): void
    {
        $context = new Context;

        $a = [PHP_INT_MAX => 'foo'];

        $context->add($a);

        $this->assertIsInt($context->contains($a));
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
    public function testContainsNotFound(array|object $value): void
    {
        $context = new Context;

        $this->assertFalse($context->contains($value));
    }
}
