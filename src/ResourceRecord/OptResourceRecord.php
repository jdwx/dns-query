<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Option;
use JDWX\DNSQuery\RDataValue;
use LogicException;


class OptResourceRecord extends ResourceRecord {


    public const int DEFAULT_PAYLOAD_SIZE = 4096;


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
        $rData[ 'options' ] = new RDataValue( RDataType::OptionList, $options );
    }


    public function classValue() : int {
        return $this->payloadSize();
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
        assert( $x instanceof RData );
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
