<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use InvalidArgumentException;


enum OptionCode : int {


    case LLQ = 1;

    case UL = 2;

    case NSID = 3;

    case DAU = 5;

    case DHU = 6;

    case N3U = 7;

    case CLIENT_SUBNET = 8;

    case EXPIRE = 9;

    case COOKIE = 10;

    case TCP_KEEPALIVE = 11;

    case PADDING = 12;

    case CHAIN = 13;

    case KEY_TAG = 14;

    case ERROR = 15;

    case CLIENT_TAG = 16;

    case SERVER_TAG = 17;

    case REPORT_CHANNEL = 18;

    case ZONE_VERSION = 19;


    public static function fromName( string $i_stName ) : self {
        $x = self::tryFromName( $i_stName );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new InvalidArgumentException( "Unknown option code name: {$i_stName}" );
    }


    public static function normalize( int|string|self $i_optionCode ) : self {
        if ( is_int( $i_optionCode ) ) {
            return self::tryFrom( $i_optionCode )
                ?? throw new InvalidArgumentException( "Invalid option code ID: {$i_optionCode}" );
        }
        if ( is_string( $i_optionCode ) ) {
            return self::fromName( $i_optionCode );
        }
        return $i_optionCode;
    }


    /**
     * @param string $i_stName
     * @param bool   $i_bDropCache For testing only; set to true to drop the internal cache
     * @return self|null
     */
    public static function tryFromName( string $i_stName, bool $i_bDropCache = false ) : ?self {
        $i_stName = strtoupper( trim( $i_stName ) );
        static $cache = [];
        if ( $i_bDropCache ) {
            $cache = [];
        }
        if ( empty( $cache ) ) {
            foreach ( self::cases() as $case ) {
                $cache[ $case->name ] = $case;
            }
        }

        return $cache[ $i_stName ] ?? null;
    }


}