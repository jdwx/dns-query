<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Message;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


class Question implements \Stringable {


    public RecordType $type;

    public RecordClass $class;


    public function __construct( public string          $stName, int|string|RecordType $type,
                                 int|string|RecordClass $class ) {
        $this->type = RecordType::normalize( $type );
        $this->class = RecordClass::normalize( $class );
    }


    public static function fromBinary( string $binary, int &$io_uOffset ) : self {
        $stName = Binary::consumeName( $binary, $io_uOffset );
        $type = RecordType::from( Binary::consume16BitInt( $binary, $io_uOffset ) );
        $class = RecordClass::from( Binary::consume16BitInt( $binary, $io_uOffset ) );
        return new self( $stName, $type, $class );
    }


    public function __toString() : string {
        return $this->stName . ' ' . $this->class->name . ' ' . $this->type->name;
    }


    /** @param array<string, int> $io_rLabelMap */
    public function toBinary( array &$io_rLabelMap, int $i_uOffset ) : string {
        return Binary::packName( $this->stName, $io_rLabelMap, $i_uOffset )
            . $this->type->toBinary()
            . $this->class->toBinary();
    }


}
