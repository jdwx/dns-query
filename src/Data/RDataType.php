<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use InvalidArgumentException;
use JDWX\DNSQuery\DomainName;
use JDWX\DNSQuery\RDataValue;
use JDWX\Strict\TypeIs;
use LogicException;


enum RDataType : int {


    case DomainName = 0;

    case IPv4Address = 1;

    case IPv6Address = 2;

    case CharacterString = 3;

    case CharacterStringList = 4;

    case UINT16 = 5;

    case UINT32 = 6;


    public static function normalize( int|RDataType $i_type ) : RDataType {
        if ( $i_type instanceof RDataType ) {
            return $i_type;
        }
        return RDataType::from( $i_type );
    }


    protected static function escapeString( string $i_value ) : string {
        if ( ! str_contains( $i_value, ' ' ) ) {
            return $i_value;
        }
        $i_value = str_replace( '\\', '\\\\', $i_value );
        $i_value = str_replace( '"', '\\"', $i_value );
        return '"' . $i_value . '"';
    }


    /** @param list<string> $io_rArgs */
    public function consume( array &$io_rArgs ) : RDataValue {
        if ( self::CharacterStringList === $this ) {
            $out = new RDataValue( $this, array_merge( $io_rArgs, [] ) );
            $io_rArgs = [];
            return $out;
        }
        return new RDataValue( $this, $this->parse( array_shift( $io_rArgs ) ) );
    }


    public function format( mixed $i_value ) : string {
        return match ( $this ) {
            self::DomainName => DomainName::format( $i_value ),
            self::CharacterString => self::escapeString( $i_value ),
            self::CharacterStringList => implode( ' ', array_map(
                [ self::class, 'escapeString' ],
                TypeIs::array( $i_value )
            ) ),
            default => strval( $i_value )
        };
    }


    public function parse( string $i_stValue ) : mixed {
        switch ( $this ) {
            case self::DomainName:
                return DomainName::parse( $i_stValue );

            case self::IPv4Address:
                $st = filter_var( $i_stValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
                if ( false === $st ) {
                    throw new InvalidArgumentException( "Invalid IPv4 address: {$i_stValue}" );
                }
                return $st;

            case self::IPv6Address:
                $st = filter_var( $i_stValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
                if ( false === $st ) {
                    throw new InvalidArgumentException( "Invalid IPv6 address: {$i_stValue}" );
                }
                return $st;

            case self::CharacterString:
                return $i_stValue;

            case self::CharacterStringList:
                throw new LogicException( 'Cannot parse CharacterStringList directly.' );

            case self::UINT16:
                $uValue = filter_var( $i_stValue, FILTER_VALIDATE_INT, [
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 65535,
                    ],
                ] );
                if ( false === $uValue ) {
                    throw new InvalidArgumentException( "Invalid UINT16 value: $i_stValue" );
                }
                return $uValue;

            case self::UINT32:
                $uValue = filter_var( $i_stValue, FILTER_VALIDATE_INT, [
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 4294967295,
                    ],
                ] );
                if ( false === $uValue ) {
                    throw new InvalidArgumentException( "Invalid UINT32 value: $i_stValue" );
                }
                return $uValue;

        }
        // @codeCoverageIgnoreStart
        /** @phpstan-ignore deadCode.unreachable */
        throw new LogicException( "Unhandled RDataType: {$this->name}" );
        // @codeCoverageIgnoreEnd
    }


}
