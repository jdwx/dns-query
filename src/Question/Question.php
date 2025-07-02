<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Question;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Transport\BufferInterface;


class Question extends AbstractQuestion {


    private RecordType $type;

    private RecordClass $class;


    /** @param list<string>|string $i_name */
    public function __construct( array|string           $i_name, int|string|RecordType $i_type,
                                 int|string|RecordClass $i_class ) {
        parent::__construct( $i_name );
        $this->type = RecordType::normalize( $i_type );
        $this->class = RecordClass::normalize( $i_class );
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


    public function setClass( int|string|RecordClass $i_class ) : void {
        $this->class = RecordClass::normalize( $i_class );
    }


    public function setType( int|string|RecordType $i_type ) : void {
        $this->type = RecordType::normalize( $i_type );
    }


}
