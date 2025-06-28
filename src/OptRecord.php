<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\RDataType;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Data\ReturnCode;
use LogicException;


class OptRecord extends AbstractResourceRecord {


    public const int DEFAULT_PAYLOAD_SIZE = 4096;


    private ReturnCode $rCode;

    private DOK $do;


    private EDNSVersion $edns;


    private int $uPayloadSize;


    public function __construct( int|string|ReturnCode $rCode = ReturnCode::NOERROR,
                                 bool|DOK              $i_do = DOK::DNSSEC_NOT_SUPPORTED,
                                 int                   $uPayloadSize = self::DEFAULT_PAYLOAD_SIZE,
                                 int|EDNSVersion       $version = 0 ) {
        parent::__construct( RecordType::OPT );
        $this->rCode = ReturnCode::normalize( $rCode );
        $this->do = DOK::normalize( $i_do );
        $this->edns = EDNSVersion::normalize( $version );
        $this->setPayloadSize( $uPayloadSize );
    }


    public static function fromArray( array $i_data ) : self {
        $rCode = $i_data[ 'rCode' ] ?? ReturnCode::NOERROR;
        $do = $i_data[ 'do' ] ?? DOK::DNSSEC_NOT_SUPPORTED;
        $uPayloadSize = $i_data[ 'payloadSize' ] ?? self::DEFAULT_PAYLOAD_SIZE;
        $version = $i_data[ 'version' ] ?? 0;
        return new self(
            $rCode,
            $do,
            $uPayloadSize,
            $version instanceof EDNSVersion ? $version : new EDNSVersion( (int) $version )
        );
    }


    public static function fromString( string $i_string ) : self {
        throw new LogicException( 'OPT records cannot be created from a string.' );
    }


    public function __toString() : string {
        throw new LogicException( 'OPT records cannot be rendered to a string.' );
    }


    public function classValue() : int {
        return $this->payloadSize();
    }


    public function getClass() : RecordClass {
        throw new LogicException( 'OPT records do not have a class.' );
    }


    public function getName() : array {
        return [];
    }


    public function getPayloadSize() : int {
        return $this->uPayloadSize;
    }


    public function getRData() : array {
        return [
            'rCode' => $this->rCode,
            'do' => $this->do,
            'options' => [],
            'version' => $this->edns,
        ];
    }


    public function getRDataValue( string $stKey ) : ?RDataValue {
        if ( 'options' !== $stKey ) {
            return null;
        }
        return new RDataValue( RDataType::OptionList, $this->getRData()[ 'options' ] );
    }


    public function getTTL() : int {
        return $this->rCode->toFlagTTL() | $this->do->toFlagTTL() | $this->edns->toFlagTTL();
    }


    public function getType() : RecordType {
        return RecordType::OPT;
    }


    public function getVersion() : EDNSVersion {
        return $this->edns;
    }


    public function name() : string {
        return '';
    }


    public function payloadSize() : int {
        return $this->getPayloadSize();
    }


    public function setPayloadSize( int $i_size ) : void {
        if ( $i_size < 0 ) {
            throw new \InvalidArgumentException( 'Payload size must be a non-negative integer.' );
        }
        if ( $i_size > 65535 ) {
            throw new \InvalidArgumentException( 'Payload size must not exceed 65535.' );
        }
        $this->uPayloadSize = $i_size;
    }


    public function toArray( bool $i_bNameAsArray = false ) : array {
        $uSize = $this->payloadSize();
        return [
            'name' => $i_bNameAsArray ? [] : '',
            'type' => $this->type(),
            'class' => $uSize,
            'ttl' => $this->ttl(),
            'do' => $this->do->name,
            'version' => $this->getVersion(),
            'payloadSize' => $uSize,
        ];
    }


    public function ttl() : int {
        return $this->getTTL();
    }


    public function type() : string {
        return RecordType::OPT->name;
    }


}
