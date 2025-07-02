<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Data;


class FlagWord {


    public QR $qr;

    public OpCode $opCode;

    public AA $aa;

    public TC $tc;

    public RD $rd;

    public RA $ra;

    public ZBits $zBits;

    public ReturnCode $rCode;


    public function __construct(
        bool|int|string|QR    $i_qr = QR::QUERY,
        int|string|OpCode     $i_opCode = OpCode::QUERY,
        bool|int|string|AA    $i_aa = AA::NON_AUTHORITATIVE,
        bool|int|string|TC    $i_tc = TC::NOT_TRUNCATED,
        bool|int|string|RD    $i_rd = RD::RECURSION_DESIRED,
        bool|int|string|RA    $i_ra = RA::RECURSION_NOT_AVAILABLE,
        int|ZBits             $i_z = 0,
        int|string|ReturnCode $i_rc = ReturnCode::NOERROR
    ) {
        $this->qr = QR::normalize( $i_qr );
        $this->opCode = OpCode::normalize( $i_opCode );
        $this->aa = AA::normalize( $i_aa );
        $this->tc = TC::normalize( $i_tc );
        $this->rd = RD::normalize( $i_rd );
        $this->ra = RA::normalize( $i_ra );
        $this->zBits = ZBits::normalize( $i_z );
        $this->rCode = ReturnCode::normalize( $i_rc );
    }


    public static function fromFlagWord( int $i_uFlagWord ) : self {
        $qr = QR::fromFlagWord( $i_uFlagWord );
        $opCode = OpCode::fromFlagWord( $i_uFlagWord );
        $aa = AA::fromFlagWord( $i_uFlagWord );
        $tc = TC::fromFlagWord( $i_uFlagWord );
        $rd = RD::fromFlagWord( $i_uFlagWord );
        $ra = RA::fromFlagWord( $i_uFlagWord );
        $zBits = ZBits::fromFlagWord( $i_uFlagWord );
        $rCode = ReturnCode::fromFlagWord( $i_uFlagWord );
        return new self( $qr, $opCode, $aa, $tc, $rd, $ra, $zBits, $rCode );
    }


    public static function normalize( int|self $i_flagWord ) : self {
        if ( is_int( $i_flagWord ) ) {
            return self::fromFlagWord( $i_flagWord );
        }
        return $i_flagWord;
    }


    public function flagString() : string {
        return trim(
            $this->qr->toFlag()
            . $this->aa->toFlag()
            . $this->tc->toFlag()
            . $this->rd->toFlag()
            . $this->ra->toFlag()
        );
    }


    public function setQR( bool|int|string|QR $i_qr ) : self {
        $this->qr = QR::normalize( $i_qr );
        return $this;
    }


    public function setRCode( int|string|ReturnCode $i_rc ) : self {
        $this->rCode = ReturnCode::normalize( $i_rc );
        return $this;
    }


    public function value() : int {
        return $this->qr->toFlagWord()
            | $this->opCode->toFlagWord()
            | $this->aa->toFlagWord()
            | $this->tc->toFlagWord()
            | $this->rd->toFlagWord()
            | $this->ra->toFlagWord()
            | $this->zBits->toFlagWord()
            | $this->rCode->toFlagWord();
    }


}
