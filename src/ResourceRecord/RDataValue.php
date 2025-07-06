<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RDataType;


class RDataValue implements RDataValueInterface {


    public function __construct( public RDataType $type, public mixed $value ) {}


    public function type() : RDataType {
        return $this->type;
    }


    public function value() : mixed {
        return $this->value;
    }


}
