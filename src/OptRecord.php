<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use InvalidArgumentException;
use JDWX\DNSQuery\Data\DOK;
use JDWX\DNSQuery\Data\EDNSVersion;
use JDWX\DNSQuery\Data\OptionCode;
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


    /**
     * @param list<Option>|RDataValue $options
     */
    public function __construct( int|string|ReturnCode $rCode = ReturnCode::NOERROR,
                                 bool|DOK              $i_do = DOK::DNSSEC_NOT_SUPPORTED,
                                 int                   $uPayloadSize = self::DEFAULT_PAYLOAD_SIZE,
                                 int|EDNSVersion       $version = 0,
                                 array|RDataValue      $options = [] ) {
        parent::__construct( RecordType::OPT, [ 'options' => $options ] );
        $this->rCode = ReturnCode::normalize( $rCode );
        $this->do = DOK::normalize( $i_do );
        $this->edns = EDNSVersion::normalize( $version );
        $this->setPayloadSize( $uPayloadSize );
    }


    public static function fromArray( array $i_data ) : self {
        $rCode = $i_data[ 'rCode' ] ?? ReturnCode::NOERROR;
        $do = $i_data[ 'do' ] ?? DOK::fromFlagTTL( $i_data[ 'ttl' ] ) ?? DOK::DNSSEC_NOT_SUPPORTED;
        $uPayloadSize = $i_data[ 'payloadSize' ] ?? $i_data[ 'class' ] ?? self::DEFAULT_PAYLOAD_SIZE;
        $version = $i_data[ 'version' ] ?? EDNSVersion::fromFlagTTL( $i_data[ 'ttl' ] );

        // Extract options from either direct key or under rdata
        $options = $i_data[ 'options' ] ?? $i_data[ 'rdata' ][ 'options' ] ?? [];

        return new self(
            $rCode,
            $do,
            $uPayloadSize,
            $version instanceof EDNSVersion ? $version : new EDNSVersion( (int) $version ),
            $options
        );
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
            $i_option = new Option( $i_option->code, $i_stData );
        }
        $this->rData[ 'options' ][] = $i_option;
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
            'options' => $this->rData[ 'options' ]->value ?? [],
            'version' => $this->edns,
        ];
    }


    public function getRDataValue( string $stKey ) : ?RDataValue {
        if ( 'options' !== $stKey ) {
            return null;
        }
        return $this->rData[ 'options' ] ?? null;
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
            throw new InvalidArgumentException( 'Payload size must be a non-negative integer.' );
        }
        if ( $i_size > 65535 ) {
            throw new InvalidArgumentException( 'Payload size must not exceed 65535.' );
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


    public function version() : int {
        return $this->getVersion()->value;
    }


}
