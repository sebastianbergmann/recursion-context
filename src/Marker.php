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

use SplObjectStorage;

/**
 * Marks an array as being known to a Context.
 *
 * Exactly one instance of this class is added to each array that is added to a
 * Context. Because both the identity of the Context that created the marker as
 * well as the key that was assigned to the array are stored in it, looking at
 * the array's last element is enough to recognise the array again.
 *
 * @internal This class is not covered by the backward compatibility promise
 *
 * @codeCoverageIgnore
 */
final readonly class Marker
{
    /**
     * The object storage of the Context that created this marker.
     *
     * The identity of that object is what distinguishes markers created by one
     * Context from those created by another. It is used instead of the Context
     * itself so that no reference cycle back to the Context is created: such a
     * cycle would keep the Context alive, and thereby delay the removal of this
     * marker from the array, until the cycle collector runs.
     *
     * @var SplObjectStorage<object, null>
     */
    public SplObjectStorage $owner;

    /**
     * The key that the Context assigned to the array this marker was added to.
     */
    public int $key;

    /**
     * @param SplObjectStorage<object, null> $owner
     */
    public function __construct(SplObjectStorage $owner, int $key)
    {
        $this->owner = $owner;
        $this->key   = $key;
    }
}
