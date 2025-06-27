<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\RDataType;


class RDataValue {


    public function __construct( public RDataType $type, public mixed $value ) {}


}
