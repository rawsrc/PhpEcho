<?php declare(strict_types=1);

namespace rawsrc\PhpEcho;

use ArrayAccess;

/**
 * PhpEcho : Native PHP Template engine: One class to rule them all ;-)
 *
 * @author      rawsrc
 * @copyright   MIT License
 *
 *              Copyright (c) 2020-2023+ rawsrc
 *
 *              Permission is hereby granted, free of charge, to any person obtaining a copy
 *              of this software and associated documentation files (the "Software"), to deal
 *              in the Software without restriction, including without limitation the rights
 *              to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *              copies of the Software, and to permit persons to whom the Software is
 *              furnished to do so, subject to the following conditions:
 *
 *              The above copyright notice and this permission notice shall be included in all
 *              copies or substantial portions of the Software.
 *
 *              THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *              IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *              FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *              AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *              LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *              OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *              SOFTWARE.
 *
 * Class that represents a view as an object
*/
abstract
class ViewBuilder
implements ArrayAccess
{
    abstract public function build(): PhpEcho;

    public array $data = [];

    public function getData(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return (string)$this->build();
    }

    //region ARRAY ACCESS INTERFACE
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset];
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset ($this->data[$offset]);
    }
    //endregion
}
