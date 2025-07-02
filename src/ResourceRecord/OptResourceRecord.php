<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Option;
use LogicException;


class OptResourceRecord extends ResourceRecord {


    public const int DEFAULT_PAYLOAD_SIZE = 4096;


    public function __construct( array|string                       $rName = [], RecordType|int|string $type = RecordType::OPT,
                                 RecordClass|int|string|null        $class = null, ?int $uTTL = 0,
                                 array|string|Option|RDataInterface $rData = [ 'options' => [] ] ) {
        if ( $rData instanceof Option ) {
            $rData = [ 'options' => [ $rData ] ];
        } elseif ( is_array( $rData ) && ! isset( $rData[ 'options' ] ) ) {
            $rData = [ 'options' => $rData ];
        }
        parent::__construct( $rName, $type, $class ?? self::DEFAULT_PAYLOAD_SIZE, $uTTL, $rData );
    }


    public static function fromString( string $i_string ) : self {
        throw new LogicException( 'OPT records cannot be created from a string.' );
    }


    public function __toString() : string {
        throw new LogicException( 'OPT records cannot be rendered to a string.' );
    }


    public function addOption( OptionCode|Option $i_option, ?string $i_stData = null ) : void {
        if ( ! $i_option instanceof Option ) {
            if ( ! is_string( $i_stData ) ) {
                throw new InvalidArgumentException( 'Option data is missing.' );
            }
            $i_option = new Option( $i_option->value, $i_stData );
        }
        $rData = $this->getRData();
        $options = $rData[ 'options' ];
        assert( is_array( $options ), 'Options must be an array.' );
        $options[] = $i_option;
        $rData[ 'options' ] = $options;
    }


    public function getClass() : RecordClass {
        throw new LogicException( 'OPT records do not have a class.' );
    }


    public function getDO() : DOK {
        return DOK::fromFlagTTL( $this->getTTL() );
    }


    public function getName() : array {
        return [];
    }


    public function getPayloadSize() : int {
        return $this->classValue();
    }


    public function getRData() : RData {
        $x = parent::getRData();
        assert( $x instanceof RData, 'RData has type: ' . get_debug_type( $x ) );
        return $x;
    }


    public function getType() : RecordType {
        return RecordType::OPT;
    }


    public function getVersion() : EDNSVersion {
        return EDNSVersion::fromFlagTTL( $this->getTTL() );
    }


    public function name() : string {
        return '';
    }


    public function option( int $i_uIndex ) : ?Option {
        $option = $this->tryOption( $i_uIndex );
        if ( $option instanceof Option ) {
            return $option;
        }
        throw new InvalidArgumentException( "Option at index {$i_uIndex} does not exist." );
    }


    /** @return list<Option> */
    public function options() : array {
        return $this->getRDataValue( 'options' );
    }


    public function payloadSize() : int {
        return $this->getPayloadSize();
    }


    public function setPayloadSize( int $i_size ) : void {
        if ( $i_size < 0 ) {
            throw new InvalidArgumentException( 'Payload size must be a non-negative integer.' );
        }
        if ( $i_size > 65535 ) {
            throw new InvalidArgumentException( 'Payload size must not exceed 65535.' );
        }
        $this->setClass( $i_size );
    }


    public function setVersion( int|EDNSVersion $i_version ) : void {
        $uTTL = $this->getTTL();
        $version = EDNSVersion::normalize( $i_version );
        $uVersion = $version->toFlagTTL();
        $uTTL &= 0xFF00FFFF; // Clear the version bits
        $uTTL |= $uVersion; // Set the new version bits
        $this->setTTL( $uTTL );
    }


    public function toArray( bool $i_bNameAsArray = false ) : array {
        return [
            'name' => $i_bNameAsArray ? [] : '',
            'type' => $this->type(),
            'ttl' => $this->getTTL(),
            'do' => $this->getDO()->name,
            'version' => $this->getVersion()->value,
            'payloadSize' => $this->getPayloadSize(),
        ];
    }


    public function tryOption( int $i_uIndex ) : ?Option {
        $options = $this->getRDataValue( 'options' );
        return $options[ $i_uIndex ] ?? null;
    }


    public function ttl() : int {
        return $this->getTTL();
    }


    public function type() : string {
        return RecordType::OPT->name;
    }


    public function version() : int {
        return $this->getVersion()->value;
    }


}
