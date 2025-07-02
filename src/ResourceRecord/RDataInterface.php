<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use Stringable;


interface RDataInterface extends Stringable {


    /** @return array<string, mixed> */
    public function toArray() : array;


}