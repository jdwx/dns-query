<?php /** @noinspection SpellCheckingInspection */


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\DNSQuery\Exceptions\ReturnCodeException;


/** @suppress PhanInvalidConstantExpression */
enum ReturnCode: int {


    case NOERROR   = 0; # RFC 1035

    case FORMERR   = 1; # RFC 1035

    case SERVFAIL  = 2; # RFC 1035

    case NXDOMAIN  = 3; # RFC 1035

    case NOTIMP    = 4; # RFC 1035

    case REFUSED   = 5; # RFC 1035

    case YXDOMAIN  = 6; # RFC 2136

    case YXRRSET   = 7; # RFC 2136;

    case NXRRSET   = 8; # RFC 2136

    case NOTAUTH   = 9; # RFC 2136

    case NOTZONE   = 10; # RFC 2136

    case DSOTYPENI = 11; # RFC 8490

    # 12-15 reserved

    case BADSIG = 16; # RFC 2845

    public const self BADVERS = self::BADSIG; # RFC 6891

    case BADKEY                   = 17; # RFC 2845

    case BADTIME                  = 18; # RFC 2845

    case BADMODE                  = 19; # RFC 2930

    case BADNAME                  = 20; # RFC 2930

    case BADALG                   = 21; # RFC 2930

    case BADTRUNC                 = 22; # RFC 4635

    case BADCOOKIE                = 23; # RFC 7873

    case ZZZ_TEST_ONLY_DO_NOT_USE = 999_999_999; # Internal use only

    private const array MESSAGES = [
        self::NOERROR->value => 'The request completed successfully.',
        self::FORMERR->value => 'The name server was unable to interpret the query.',
        self::SERVFAIL->value => 'The name server was unable to process this query due to a problem with the name server.',
        self::NXDOMAIN->value => 'The domain name referenced in the query does not exist.',
        self::NOTIMP->value => 'The name server does not support the requested kind of query.',
        self::REFUSED->value => 'The name server refuses to perform the specified operation for policy reasons.',
        self::YXDOMAIN->value => 'Name Exists when it should not.',
        self::YXRRSET->value => 'RR Set Exists when it should not.',
        self::NXRRSET->value => 'RR Set that should exist does not.',
        self::NOTAUTH->value => 'Server Not Authoritative for zone.',
        self::NOTZONE->value => 'Name not contained in zone.',

        self::BADSIG->value => 'TSIG Signature Failure.',
        self::BADKEY->value => 'Key not recognized.',
        self::BADTIME->value => 'Signature out of time window.',
        self::BADMODE->value => 'Bad TKEY Mode.',
        self::BADNAME->value => 'Duplicate key name.',
        self::BADALG->value => 'Algorithm not supported.',
        self::BADTRUNC->value => 'Bad truncation.',
        self::DSOTYPENI->value => 'DSO-TYPE-NI (DNSSEC OK) not implemented.',
        self::BADCOOKIE->value => 'Bad DNS Cookie.',
    ];


    public static function fromFlagWord( int $i_flagWord ) : self {
        $i_flagWord = $i_flagWord & 0x0F;
        return self::tryFrom( $i_flagWord )
            ?? throw new ReturnCodeException( "Invalid return code ID in flag word: {$i_flagWord}" );
    }


    public static function fromName( string $i_stName ) : self {
        $x = self::tryFromName( $i_stName );
        if ( $x instanceof self ) {
            return $x;
        }
        throw new ReturnCodeException( "Invalid return code name: {$i_stName}" );
    }


    public static function normalize( int|string|ReturnCode $i_returnCode ) : self {
        if ( is_int( $i_returnCode ) ) {
            return self::tryFrom( $i_returnCode ) ?? throw new ReturnCodeException( "Invalid return code ID: {$i_returnCode}" );
        }
        if ( is_string( $i_returnCode ) ) {
            return self::fromName( $i_returnCode );
        }
        return $i_returnCode;
    }


    public static function tryFromName( string $i_stName ) : ?self {
        $i_stName = strtoupper( trim( $i_stName ) );
        if ( 'BADVERS' === $i_stName ) {
            return self::BADVERS;
        }
        static $cache = [];
        if ( empty( $cache ) ) {
            foreach ( self::cases() as $case ) {
                $cache[ $case->name ] = $case;
            }
        }

        return $cache[ $i_stName ] ?? null;
    }


    public function decode() : string {
        return self::MESSAGES[ $this->value ] ?? "Return code {$this->name} ({$this->value})";
    }


    public function toFlagTTL() : int {
        return ( $this->value >> 4 ) << 24;
    }


    public function toFlagWord() : int {
        return $this->value & 0x0F;
    }


}
