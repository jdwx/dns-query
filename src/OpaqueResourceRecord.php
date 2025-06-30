<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;


/**
 * Represents a DNS resource record that contains opaque data. This
 * allows safe handling of resource records with an unknown type or
 * possibly invalid values for type and class.
 */
class OpaqueResourceRecord implements ResourceRecordInterface {


    use ResourceRecordTrait;


    /** @param list<string> $rName */
    public function __construct( public array  $rName, public int $uType, public int $uClass, public int $uTtl,
                                 public string $stData ) {}


    /** @param array<string, mixed> $i_data */
    public static function fromArray( array $i_data ) : self {
        return new self(
            $i_data[ 'name' ] ?? [],
            $i_data[ 'type' ] ?? 0,
            $i_data[ 'class' ] ?? 0,
            $i_data[ 'ttl' ] ?? 0,
            $i_data[ 'rdata' ] ?? ''
        );
    }


    public static function fromBuffer( BufferInterface $i_buffer ) : self {
        $rName = $i_buffer->consumeNameArray();
        $uType = $i_buffer->consumeUInt16();
        $uClass = $i_buffer->consumeUInt16();
        $uTtl = $i_buffer->consumeUInt32();
        $uRDLength = $i_buffer->consumeUInt16();
        $stData = $i_buffer->consume( $uRDLength );
        return new self( $rName, $uType, $uClass, $uTtl, $stData );
    }


    public static function fromString( string $i_string ) : ResourceRecordInterface {
        $buffer = new Buffer( $i_string );
        return self::fromBuffer( $buffer );
    }


    public function __toString() : string {
        throw new \LogicException( 'OpaqueResourceRecord does not support __toString()' );
    }


    public function classValue() : int {
        return $this->uClass;
    }


    public function getClass() : RecordClass {
        $class = RecordClass::tryFrom( $this->uClass );
        if ( $class instanceof RecordClass ) {
            return $class;
        }
        throw new \InvalidArgumentException( "Unknown record class: {$this->uClass}" );
    }


    public function getName() : array {
        return $this->rName;
    }


    public function getRData() : array {
        throw new \LogicException( 'OpaqueResourceRecord does not support getRData()' );
    }


    public function getRDataValue( string $stKey ) : ?RDataValue {
        throw new \LogicException( 'OpaqueResourceRecord does not support getRDataValue()' );
    }


    public function getRDataValueEx( string $stKey ) : RDataValue {
        throw new \LogicException( 'OpaqueResourceRecord does not support getRDataValueEx()' );
    }


    public function getTTL() : int {
        return $this->uTtl;
    }


    public function getType() : RecordType {
        $type = RecordType::tryFrom( $this->uType );
        if ( $type instanceof RecordType ) {
            return $type;
        }
        throw new \InvalidArgumentException( "Unknown record type: {$this->uType}" );
    }


    public function hasRDataValue( string $i_stName ) : bool {
        return false;
    }


    public function isClass( RecordClass|int|string $i_class ) : bool {
        $class = RecordClass::normalize( $i_class );
        return $class->is( $this->uClass );
    }


    public function isType( RecordType|int|string $i_type ) : bool {
        $type = RecordType::normalize( $i_type );
        return $type->is( $this->uType );
    }


    public function setRDataValue( string $i_stName, mixed $i_value ) : void {
        throw new \LogicException( 'OpaqueResourceRecord does not support setRDataValue()' );
    }


    public function toArray( bool $i_bNameAsArray = false ) : array {
        return [
            'name' => $i_bNameAsArray ? $this->getName() : $this->name(),
            'type' => $this->type(),
            'class' => $this->class(),
            'ttl' => $this->ttl(),
            'rdata' => $this->stData,
        ];
    }


    public function typeValue() : int {
        return $this->uType;
    }


}
