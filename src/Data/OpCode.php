<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


use JDWX\Strict\OK;


enum OpCode: int {


    case QUERY  = 0; // RFC 1035

    case IQUERY = 1; // RFC 3425 (obsolete)

    case STATUS = 2; // RFC 1035

    case NOTIFY = 4; // RFC 1996

    case UPDATE = 5; // RFC 2136

    case DSO    = 6; // DNS Stateful Operations (DSO) RFC 8490


    public static function fromFlagWord( int $i_flagWord ) : self {
        $uOpCode = ( $i_flagWord >> 11 ) & 0x0F;
        return self::tryFrom( $uOpCode )
            ?? throw new \InvalidArgumentException( "Unknown opcode in flag word: {$uOpCode}" );
    }


    /**
     * Convert an opcode name to its enum value. This is case-insensitive.
     * Throws an exception if the name is not valid.
     *
     * @param string $i_stName the opcode name
     * @return self the enum value
     * @throws \InvalidArgumentException if the name is not valid
     */
    public static function fromName( string $i_stName ) : self {
        $opCode = self::tryFromName( $i_stName );
        if ( $opCode instanceof self ) {
            return $opCode;
        }
        throw new \InvalidArgumentException( "Unknown opcode name: {$i_stName}" );
    }


    /**
     * Convert an opcode numeric ID to its name. This converts valid values
     * not defined in the enum to OPCODE## names.
     *
     * @param int $i_uOpCode the numeric opcode ID
     * @return string the opcode name
     */
    public static function idToName( int $i_uOpCode ) : string {
        $nst = self::tryFrom( $i_uOpCode )?->name;
        if ( is_string( $nst ) ) {
            return $nst;
        }
        if ( $i_uOpCode >= 0 && $i_uOpCode <= 15 ) {
            return "OPCODE{$i_uOpCode}";
        }
        throw new \InvalidArgumentException( "Unknown opcode id: {$i_uOpCode}" );
    }


    /**
     * Convert an opcode name to its numeric ID. This works for valid
     * OPCODE## names even if they are not defined in the enum.
     *
     * @param string $i_stName the opcode name
     * @return int the numeric opcode ID
     */
    public static function nameToId( string $i_stName ) : int {
        $nu = self::opcodeName( trim( strtoupper( $i_stName ) ) );
        if ( is_int( $nu ) ) {
            return $nu;
        }
        return self::fromName( $i_stName )->value;
    }


    /**
     * Ensure that the given opcode is normalized to an OpCode enum value.
     * This is useful for functions that want to accept whatever the user
     * has without forcing them to do the conversion themselves.
     *
     * @param int|string|OpCode $i_opCode the opcode to normalize
     * @return self the normalized opcode
     */
    public static function normalize( int|string|self $i_opCode ) : self {
        if ( is_int( $i_opCode ) ) {
            return self::from( $i_opCode );
        }
        if ( is_string( $i_opCode ) ) {
            return self::fromName( $i_opCode );
        }
        return $i_opCode;
    }


    /**
     * Convert an opcode name to its enum value. This is case-insensitive.
     *
     * @param string $i_stName the opcode name
     * @return self|null the OpCode value, or null if the name is not valid
     */
    public static function tryFromName( string $i_stName ) : ?self {
        $i_stName = trim( strtoupper( $i_stName ) );
        $uOpCode = self::opcodeName( $i_stName );
        if ( $uOpCode !== null ) {
            return self::tryFrom( $uOpCode );
        }
        return match ( $i_stName ) {
            'QUERY' => self::QUERY,
            'IQUERY' => self::IQUERY,
            'STATUS' => self::STATUS,
            'NOTIFY' => self::NOTIFY,
            'UPDATE' => self::UPDATE,
            'DSO' => self::DSO,
            default => null,
        };
    }


    /**
     * Internal helper to extract numeric opcode from OPCODE## names.
     *
     * @param string $i_stName the opcode name
     * @return int|null the numeric opcode ID, or null if the name is not valid
     */
    private static function opcodeName( string $i_stName ) : ?int {
        if ( ! OK::preg_match( '/^OPCODE([0-9]{1,2})$/', $i_stName, $matches ) ) {
            return null;
        }

        /** @phpstan-ignore-next-line */
        assert( is_array( $matches ) );
        $uOpCode = intval( $matches[ 1 ] );
        if ( $uOpCode < 0 || $uOpCode > 15 ) {
            return null;
        }
        return $uOpCode;
    }


    public function toFlagWord() : int {
        return ( $this->value & 0x0F ) << 11;
    }


}
