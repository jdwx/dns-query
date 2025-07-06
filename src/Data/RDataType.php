<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\DomainName;
use JDWX\DNSQuery\Exceptions\RecordDataException;
use LogicException;


enum RDataType {


    case DomainName;

    case IPv4Address;

    case IPv6Address;

    case CharacterString;

    case CharacterStringList;

    case HexBinary;

    case UINT8;

    case UINT16;

    case UINT32;

    case Option;

    case OptionList;


    /** @param list<string> $io_rArgs */
    public function consume( array &$io_rArgs ) : mixed {
        if ( self::CharacterStringList === $this ) {
            $r = array_merge( $io_rArgs, [] );
            $io_rArgs = [];
            return $r;
        }
        return $this->parse( array_shift( $io_rArgs ) );
    }


    public function parse( string $i_stValue ) : mixed {
        switch ( $this ) {

            case self::DomainName:
                return DomainName::parse( $i_stValue );


            case self::IPv4Address:
                $st = filter_var( $i_stValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
                if ( false === $st ) {
                    throw new RecordDataException( "Invalid IPv4 address: {$i_stValue}" );
                }
                return $st;


            case self::IPv6Address:
                $st = filter_var( $i_stValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
                if ( false === $st ) {
                    throw new RecordDataException( "Invalid IPv6 address: {$i_stValue}" );
                }
                return $st;


            case self::HexBinary:
                if ( ! preg_match( '/^[0-9a-fA-F]+$/', $i_stValue ) ) {
                    throw new RecordDataException( "Invalid HexBinary value: {$i_stValue}" );
                }
                return $i_stValue;

            case self::CharacterString:
                return $i_stValue;


            case self::CharacterStringList:
                throw new LogicException( 'Cannot parse CharacterStringList directly.' );


            case self::UINT8:
                $uValue = filter_var( $i_stValue, FILTER_VALIDATE_INT, [
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 255,
                    ],
                ] );
                if ( false === $uValue ) {
                    throw new RecordDataException( "Invalid UINT8 value: $i_stValue" );
                }
                return $uValue;


            case self::UINT16:
                $uValue = filter_var( $i_stValue, FILTER_VALIDATE_INT, [
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 65535,
                    ],
                ] );
                if ( false === $uValue ) {
                    throw new RecordDataException( "Invalid UINT16 value: $i_stValue" );
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
                    throw new RecordDataException( "Invalid UINT32 value: $i_stValue" );
                }
                return $uValue;


            default:
                throw new LogicException( "Unhandled RDataType: {$this->name}" );
        }
    }


}
