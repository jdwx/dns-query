<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery;


use ArrayAccess;
use InvalidArgumentException;
use JDWX\DNSQuery\Data\RDataMaps;
use JDWX\DNSQuery\Data\RecordClass;
use JDWX\DNSQuery\Data\RecordType;
use JDWX\DNSQuery\Exceptions\RecordException;
use JDWX\Quote\Operators\DelimiterOperator;
use JDWX\Quote\Operators\QuoteOperator;
use JDWX\Quote\Parser;


/** @implements ArrayAccess<string, mixed> */
class ResourceRecord extends AbstractResourceRecord {


    protected const RecordClass DEFAULT_CLASS = RecordClass::IN;


    protected static int $uDefaultTTL = 86400;


    protected RecordType $type;

    protected RecordClass $class;

    protected int $uTTL;

    /** @var list<string> $rName */
    protected array $rName;


    /**
     * @param list<string>|string $rName
     * @param int|string|RecordType $type
     * @param int|string|RecordClass|null $class
     * @param int|null $uTTL
     * @param array<string, mixed> $rData
     */
    public function __construct(
        array|string                $rName,
        int|string|RecordType       $type,
        int|string|RecordClass|null $class = null,
        ?int                        $uTTL = null,
        array                       $rData = [],
    ) {
        if ( is_string( $rName ) ) {
            $rName = DomainName::parse( $rName );
        }
        $this->rName = $rName;
        $this->setType( $type );
        $this->setClass( $class ?? static::DEFAULT_CLASS );
        $this->setTTL( $uTTL ?? self::$uDefaultTTL );

        $this->rDataMap = RDataMaps::map( $this->type );

        foreach ( array_keys( $this->rDataMap ) as $stKey ) {
            if ( ! isset( $rData[ $stKey ] ) ) {
                throw new InvalidArgumentException( "Missing required RData key: {$stKey}" );
            }
            $this->setRDataValueAlreadyChecked( $stKey, $rData[ $stKey ] );
        }
    }


    public static function fromArray( array $i_data ) : self {
        $rRequiredFields = [ 'type', 'class' ];
        foreach ( $rRequiredFields as $stField ) {
            if ( ! isset( $i_data[ $stField ] ) ) {
                throw new InvalidArgumentException( "Missing required field: {$stField}" );
            }
        }
        if ( is_array( $i_data[ 'name' ] ) ) {
            $rName = $i_data[ 'name' ];
        } elseif ( is_string( $i_data[ 'name' ] ) ) {
            $rName = explode( '.', $i_data[ 'name' ] );
        } else {
            throw new InvalidArgumentException( 'Invalid name format: must be string or array' );
        }
        $type = RecordType::normalize( $i_data[ 'type' ] );
        $class = isset( $i_data[ 'class' ] )
            ? RecordClass::normalize( $i_data[ 'class' ] )
            : null;
        $uTTL = isset( $i_data[ 'ttl' ] )
            ? intval( $i_data[ 'ttl' ] )
            : null;

        $rData = $i_data[ 'rdata' ] ?? $i_data;

        return new self(
            $rName,
            $type,
            $class,
            $uTTL,
            $rData
        );
    }


    public static function fromString( string $i_string ) : self {
        $lineParser = new Parser(
            hardQuote: QuoteOperator::double(),
            delimiter: DelimiterOperator::whitespace(),
        );
        $r = iterator_to_array( $lineParser( $i_string ) );
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
                throw new InvalidArgumentException( "Multiple TTL values found in record: {$i_string}" );
            }

            if ( RecordClass::isValidName( $stField ) ) {
                if ( ! $class instanceof RecordClass ) {
                    $class = RecordClass::normalize( $stField );
                    continue;
                }
                throw new InvalidArgumentException( "Multiple class values found in record: {$i_string}" );
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
            throw new InvalidArgumentException( "Missing or invalid record type in record: {$i_string}" );
        }

        $rData = [];
        foreach ( RDataMaps::map( $type->value ) as $stName => $rdt ) {
            if ( empty( $r ) ) {
                throw new InvalidArgumentException( "Missing RData value for {$stName} in record: {$i_string}" );
            }
            $rData[ $stName ] = $rdt->consume( $r );
        }
        if ( ! empty( $r ) ) {
            throw new InvalidArgumentException( 'Extra data found in record: ' . implode( ' ', $r ) );
        }

        return new self( $rName, $type, $class, $uTTL, $rData );
    }


    public static function setDefaultTTL( int $uTTL ) : void {
        self::$uDefaultTTL = $uTTL;
    }


    public function __toString() : string {
        $st = $this->name() . ' ' . $this->getTTL() . ' IN ' . $this->type() . ' ' . $this->class();
        foreach ( $this->rData as $value ) {
            $st .= ' ' . $value->type->format( $value->value );
        }
        return $st;
    }


    public function classValue() : int {
        return $this->class->value;
    }


    public function getClass() : RecordClass {
        return $this->class;
    }


    public function getName() : array {
        return $this->rName;
    }


    /** @return array<string, RDataValue> */
    public function getRData() : array {
        return $this->rData;
    }


    public function getRDataValue( string $stKey ) : ?RDataValue {
        return $this->rData[ $stKey ] ?? null;
    }


    public function getTTL() : int {
        return $this->uTTL;
    }


    public function getType() : RecordType {
        return $this->type;
    }


    public function setClass( int|string|RecordClass $class ) : void {
        $this->class = RecordClass::normalize( $class );
    }


    public function setRDataValue( string $i_stName, mixed $i_value ) : void {
        if ( ! array_key_exists( $i_stName, $this->rDataMap ) ) {
            throw new InvalidArgumentException( "Invalid RData key: {$i_stName}" );
        }
        $this->setRDataValueAlreadyChecked( $i_stName, $i_value );
    }


    public function setTTL( int $uTTL ) : void {
        if ( $uTTL < 0 || $uTTL > 2147483647 ) {
            throw new RecordException( "Invalid TTL {$uTTL}" );
        }
        $this->uTTL = $uTTL;
    }


    public function setType( int|string|RecordType $type ) : void {
        $this->type = RecordType::normalize( $type );
    }


    /** @return array<string, mixed> */
    public function toArray( bool $i_bNameAsArray = false ) : array {
        $rOut = [
            'name' => $i_bNameAsArray ? $this->getName() : $this->name(),
            'type' => $this->type(),
            'class' => $this->class(),
            'ttl' => $this->uTTL,
        ];
        foreach ( $this->rData as $stKey => $value ) {
            $rOut[ 'rdata' ][ $stKey ] = $value->value;
        }
        return $rOut;
    }


    protected function setRDataValueAlreadyChecked( string $i_stName, mixed $i_value ) : void {
        if ( ! $i_value instanceof RDataValue ) {
            $i_value = new RDataValue( $this->rDataMap[ $i_stName ], $i_value );
        } elseif ( $i_value->type !== $this->rDataMap[ $i_stName ] ) {
            throw new InvalidArgumentException(
                "RData type mismatch for {$i_stName}: wanted {$this->rDataMap[$i_stName]->name}, got {$i_value->type->name}"
            );
        }
        $this->rData[ $i_stName ] = $i_value;
    }


}
