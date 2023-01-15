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
use function spl_object_hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;
use stdClass;

#[CoversClass(Context::class)]
final class ContextTest extends TestCase
{
    private Context $context;

    public static function valuesProvider(): array
    {
        $obj2      = new stdClass;
        $obj2->foo = 'bar';

        $obj3 = (object) [1, 2, "Test\r\n", 4, 5, 6, 7, 8];

        $obj = new stdClass;
        //@codingStandardsIgnoreStart
        $obj->null = null;
        //@codingStandardsIgnoreEnd
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
        $storage->foo = $obj2;

        return [
            [$obj, spl_object_hash($obj)],
            [$obj2, spl_object_hash($obj2)],
            [$obj3, spl_object_hash($obj3)],
            [$storage, spl_object_hash($storage)],
            [$obj->array, 0],
            [$obj->array2, 0],
            [$obj->array3, 0],
        ];
    }

    protected function setUp(): void
    {
        $this->context = new Context;
    }

    #[DataProvider('valuesProvider')]
    public function testAdd($value, $key): void
    {
        $this->assertEquals($key, $this->context->add($value));

        // Test we get the same key on subsequent adds
        $this->assertEquals($key, $this->context->add($value));
    }

    public function testAdd2(): void
    {
        $a = [PHP_INT_MAX => 'foo'];

        $this->context->add($a);

        $this->assertIsInt($this->context->contains($a));
    }

    #[DataProvider('valuesProvider')]
    public function testContainsFound($value, $key): void
    {
        $this->context->add($value);
        $this->assertEquals($key, $this->context->contains($value));

        // Test we get the same key on subsequent calls
        $this->assertEquals($key, $this->context->contains($value));
    }

    #[DataProvider('valuesProvider')]
    public function testContainsNotFound($value): void
    {
        $this->assertFalse($this->context->contains($value));
    }
}
