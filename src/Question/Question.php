<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Binary;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Transport\BufferInterface;


class Question extends AbstractQuestion {


    private RecordType $type;

    private RecordClass $class;


    public function __construct( array|string           $name, int|string|RecordType $type,
                                 int|string|RecordClass $class ) {
        parent::__construct( $name );
        $this->type = RecordType::normalize( $type );
        $this->class = RecordClass::normalize( $class );
    }


    public static function fromBinary( BufferInterface $i_buffer ) : self {
        $rName = $i_buffer->consumeNameArray();
        $type = RecordType::from( $i_buffer->consumeUINT16() );
        $class = RecordClass::from( $i_buffer->consumeUINT16() );
        return new self( $rName, $type, $class );
    }


    public function getClass() : RecordClass {
        return $this->class;
    }


    public function getType() : RecordType {
        return $this->type;
    }


    public function setClass( int|string|RecordClass $i_rClass ) : void {
        $this->class = RecordClass::normalize( $i_rClass );
    }


    public function setType( int|string|RecordType $i_rType ) : void {
        $this->type = RecordType::normalize( $i_rType );
    }


    /** @param array<string, int> $io_rLabelMap */
    public function toBinary( array &$io_rLabelMap, int $i_uOffset ) : string {
        return Binary::packLabels( $this->rName, $io_rLabelMap, $i_uOffset )
            . $this->type->toBinary()
            . $this->class->toBinary();
    }


}
