<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\ResourceRecord;


use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\DomainName;
use JDWX\DNSQuery\Exceptions\MessageException;
use JDWX\DNSQuery\Exceptions\RecordClassException;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\DNSQuery\Exceptions\RecordTypeException;
use JDWX\Quote\Exception as QuoteException;
use JDWX\Quote\Operators\DelimiterOperator;
use JDWX\Quote\Operators\QuoteOperator;
use JDWX\Quote\Parser;
use JDWX\Strict\Exceptions\TypeException;
use JDWX\Strict\TypeIs;


class ResourceRecord extends AbstractResourceRecord {


    protected const RecordClass DEFAULT_CLASS = RecordClass::IN;

    protected static int $uDefaultTTL = 86400;

    /** @var list<string> $rName */
    protected array $rName;

    protected int $uTTL;

    protected int $uType;

    protected int $uClass;

    protected RDataInterface $rData;


    /**
     * @param list<string>|string $rName
     * @param int|string|RecordType $type
     * @param int|string|RecordClass|null $class
     * @param int|null $uTTL
     * @param array<string,mixed>|string|RDataInterface $rData
     */
    public function __construct(
        array|string                $rName,
        int|string|RecordType       $type,
        int|string|RecordClass|null $class = null,
        ?int                        $uTTL = null,
        array|string|RDataInterface $rData = '',
    ) {
        if ( is_string( $rName ) ) {
            $rName = DomainName::parse( $rName );
        }
        $this->rName = $rName;
        $this->setType( $type );
        $this->setClass( $class ?? static::DEFAULT_CLASS );
        $this->setTTL( $uTTL ?? self::$uDefaultTTL );
        if ( is_string( $rData ) ) {
            $rData = new OpaqueRData( $rData );
        }
        if ( is_array( $rData ) ) {
            $map = RDataMaps::map( $this->uType );
            $rData = new RData( $map, $rData );
        }
        $this->rData = $rData;
    }


    /** @param array<string, mixed> $i_data */
    public static function fromArray( array $i_data ) : static {
        if ( ! isset( $i_data[ 'name' ] ) ) {
            throw new RecordException( 'Missing name in resource record array' );
        }
        if ( ! isset( $i_data[ 'type' ] ) ) {
            throw new RecordTypeException( 'Missing record type in resource record array' );
        }
        try {
            $rName = DomainName::normalize(
                TypeIs::stringOrListString( $i_data[ 'name' ], 'name in resource record array' )
            );
        } catch ( TypeException $e ) {
            $stType = get_debug_type( $i_data[ 'name' ] );
            throw new RecordException( "Invalid record name format: must be string or array: {$stType}", 0, $e );
        }
        $uType = RecordType::anyToId( $i_data[ 'type' ] );
        $uClass = RecordClass::anyToId( $i_data[ 'class' ] ?? RecordClass::IN );
        $uTTL = isset( $i_data[ 'ttl' ] )
            ? intval( $i_data[ 'ttl' ] )
            : null;

        $rData = $i_data[ 'rdata' ] ?? $i_data;
        if ( ! $rData instanceof RDataInterface ) {
            $map = RDataMaps::tryMap( $uType );
            if ( is_array( $map ) ) {
                $rData = new RData( $map, $rData );
            } elseif ( is_string( $rData ) ) {
                $rData = new OpaqueRData( $rData );
            } else {
                throw new RecordException( 'Invalid RData type: must be RDataInterface, array, or string' );
            }
        }

        /** @phpstan-ignore new.static */
        return new static(
            $rName,
            $uType,
            $uClass,
            $uTTL,
            $rData
        );
    }


    public static function fromString( string $i_string ) : self {
        $lineParser = new Parser(
            hardQuote: QuoteOperator::double(),
            delimiter: DelimiterOperator::whitespace(),
        );
        try {
            $r = iterator_to_array( $lineParser( $i_string ) );
        } catch ( QuoteException $e ) {
            throw new RecordException( "Failed to parse record string: {$i_string}", 0, $e );
        }
        if ( empty( $r ) ) {
            throw new RecordException( 'Empty record string' );
        }
        $rName = DomainName::parse( array_shift( $r ) );

        # The next few fields could be the TTL, class, or type
        # in any order. (And TTL and class are optional.)
        $uTTL = null;
        $class = null;
        $type = null;

        while ( ( is_null( $class ) || is_null( $type ) ) && ! empty( $r ) ) {
            $stField = array_shift( $r );
            if ( is_numeric( $stField ) ) {
                if ( ! is_int( $uTTL ) ) {
                    $uTTL = intval( $stField );
                    continue;
                }
                throw new MessageException( "Multiple TTL values found in record: {$i_string}" );
            }

            if ( RecordClass::isValidName( $stField ) ) {
                if ( ! $class instanceof RecordClass ) {
                    $class = RecordClass::normalize( $stField );
                    continue;
                }
                throw new MessageException( "Multiple class values found in record: {$i_string}" );
            }
            if ( RecordType::isValidName( $stField ) ) {
                $type = RecordType::normalize( $stField );
                continue;
            }
            array_unshift( $r, $stField );
            break;
        }
        $class ??= RecordClass::IN;
        $uTTL ??= self::$uDefaultTTL;

        if ( ! $type instanceof RecordType ) {
            throw new RecordTypeException( "Missing or invalid record type in record: {$i_string}" );
        }

        $map = RDataMaps::map( $type );
        $rData = RData::fromParsedString( $map, $r );
        return new self( $rName, $type, $class, $uTTL, $rData );
    }


    public static function setDefaultTTL( int $uTTL ) : void {
        self::$uDefaultTTL = $uTTL;
    }


    public function __toString() : string {
        $st = $this->name() . ' ' . $this->getTTL() . ' ' . $this->class() . ' ' . $this->type() . ' ';
        $st .= $this->rData;
        return $st;
    }


    public function classValue() : int {
        return $this->uClass;
    }


    public function getClass() : RecordClass {
        return RecordClass::tryFrom( $this->uClass ) ??
            throw new RecordClassException( "Invalid record class: {$this->uClass}" );
    }


    public function getName() : array {
        return $this->rName;
    }


    public function getRData() : RDataInterface {
        return $this->rData;
    }


    public function getTTL() : int {
        return $this->uTTL;
    }


    public function getType() : RecordType {
        return RecordType::tryFrom( $this->uType ) ??
            throw new RecordTypeException( "Invalid record type: {$this->uType}" );
    }


    public function setClass( int|string|RecordClass $i_class ) : void {
        $this->uClass = RecordClass::anyToId( $i_class );
    }


    /** @param list<string>|string $i_name */
    public function setName( array|string $i_name ) : void {
        if ( is_string( $i_name ) ) {
            $i_name = DomainName::parse( $i_name );
        }
        $this->rName = $i_name;
    }


    public function setRData( string|RDataInterface $i_rData ) : void {
        if ( is_string( $i_rData ) ) {
            $i_rData = new OpaqueRData( $i_rData );
        }
        /** @phpstan-ignore instanceof.alwaysTrue */
        if ( ! $i_rData instanceof RDataInterface ) {
            throw new RecordException( 'Invalid RData type' );
        }
        $this->rData = $i_rData;
    }


    public function setTTL( int $i_uTTL ) : void {
        if ( $i_uTTL < 0 || $i_uTTL > 2147483647 ) {
            throw new RecordException( "Invalid TTL {$i_uTTL}" );
        }
        $this->uTTL = $i_uTTL;
    }


    public function setType( int|string|RecordType $i_type ) : void {
        $this->uType = RecordType::anyToId( $i_type );
    }


    /** @return array<string, mixed> */
    public function toArray( bool $i_bNameAsArray = false ) : array {
        $rOut = [
            'name' => $i_bNameAsArray ? $this->getName() : $this->name(),
            'type' => $this->type(),
            'class' => $this->class(),
            'ttl' => $this->uTTL,
        ];
        return array_merge( $rOut, $this->rData->toArray() );
    }


    public function typeValue() : int {
        return $this->uType;
    }


}
