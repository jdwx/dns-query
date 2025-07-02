<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use ArrayAccess;
use Countable;
use Stringable;


/**
 * @extends ArrayAccess<string, mixed>
 * @suppress PhanAccessWrongInheritanceCategoryInternal
 */
interface RDataInterface extends ArrayAccess, Countable, Stringable {


    /** @return array<string, mixed> */
    public function toArray() : array;


}