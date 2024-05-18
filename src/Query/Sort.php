<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Csv\Query;

use Iterator;

/**
 * Enable sorting a record based on its value.
 *
 * The class can be used directly with PHP's
 * <ol>
 * <li>usort and uasort.</li>
 * <li>ArrayIterator::uasort.</li>
 * <li>ArrayObject::uasort.</li>
 * </ol>
 */
interface Sort
{
    public function __invoke(mixed $valueA, mixed $valueB): int;

    public function sort(iterable $value): Iterator;

    public function sortArray(iterable $value): array;
}