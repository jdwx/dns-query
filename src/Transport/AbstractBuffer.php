<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use InvalidArgumentException;
use JDWX\DNSQuery\Binary;
use OutOfBoundsException;


abstract class AbstractBuffer implements BufferInterface {


    public function __construct( protected string $stData = '', private int $uOffset = 0 ) {}


    public function atEnd() : bool {
        while ( true ) {
            if ( $this->uOffset < strlen( $this->stData ) ) {
                return false;
            }
            if ( ! $this->tryFill() ) {
                return true;
            }
        }
    }


    public function consume( int $i_uLength ) : string {
        $this->fillTo( $this->uOffset + $i_uLength );
        $stOut = substr( $this->stData, $this->uOffset, $i_uLength );
        $this->uOffset += $i_uLength;
        return $stOut;
    }


    public function consumeIPv4() : string {
        return Binary::unpackIPv4( $this->consume( 4 ) );
    }


    public function consumeIPv6() : string {
        return Binary::unpackIPv6( $this->consume( 16 ) );
    }


    public function consumeLabel() : string {
        $uLength = Binary::unpackUINT8( self::consume( 1 ) );
        return self::consume( $uLength );
    }


    /**
     * Be a little careful with this function. It is theoretically legal for a name to
     * contain dots that are part of a label, not label separators. This function
     * will lose that distinction. However, in practice, this is unlikely to be a problem.
     */
    public function consumeName() : string {
        return implode( '.', self::consumeNameArray() );
    }


    /**
     * @return list<string>
     */
    public function consumeNameArray() : array {
        # Names can be encoded as a series of labels, or as a pointer to a previously defined name, or
        # a combination of both.
        $rOut = [];
        while ( true ) {
            $stLabel = self::consumeNameLabel();
            if ( chr( 0 ) === $stLabel ) {
                return $rOut;
            }
            $uPointer = Binary::unpackPointer( $stLabel );
            if ( is_int( $uPointer ) ) {
                return array_merge( $rOut, self::expandNamePointer( $uPointer ) );
            }

            $rOut[] = substr( $stLabel, 1 );
        }
    }


    public function consumeNameLabel() : string {
        $uFirstByte = Binary::unpackUINT8( self::consume( 1 ) );
        if ( 0 === $uFirstByte ) {
            return chr( 0 );
        }
        if ( 0xC0 === ( $uFirstByte & 0xC0 ) ) {
            return chr( $uFirstByte ) . self::consume( 1 );
        }
        return chr( $uFirstByte ) . self::consume( $uFirstByte );
    }


    public function consumeUINT16() : int {
        return Binary::unpackUINT16( $this->consume( 2 ) );
    }


    public function consumeUINT32() : int {
        return Binary::unpackUINT32( self::consume( 4 ) );
    }


    public function consumeUINT8() : int {
        return Binary::unpackUINT8( self::consume( 1 ) );
    }


    /**
     * Expand a name pointer into an array of labels. This function requires
     * all the needed data to be in the buffer already.
     *
     * @param array<int, true> $x_rLoopDetection Internal use only. Prevents infinite loops.
     * @return list<string> Array of labels
     */
    public function expandNamePointer( int $i_uOffset, array $x_rLoopDetection = [] ) : array {
        if ( isset( $x_rLoopDetection[ $i_uOffset ] ) ) {
            throw new InvalidArgumentException( "Detected loop in name pointer at offset {$i_uOffset}." );
        }
        $x_rLoopDetection[ $i_uOffset ] = true;
        $rOut = [];
        while ( true ) {
            $stLabel = Binary::unpackNameLabel( $this->stData, $i_uOffset );
            if ( chr( 0 ) === $stLabel ) {
                return $rOut;
            }
            $uPointer = Binary::unpackPointer( $stLabel );
            if ( is_int( $uPointer ) ) {
                return array_merge( $rOut, self::expandNamePointer( $uPointer, $x_rLoopDetection ) );
            }
            $rOut[] = substr( $stLabel, 1 );
            $i_uOffset += strlen( $stLabel );
        }
    }


    public function getData() : string {
        return $this->stData;
    }


    public function length() : int {
        return strlen( $this->stData );
    }


    public function readyCheck() : bool {
        if ( $this->uOffset < strlen( $this->stData ) ) {
            return true;
        }
        return $this->tryFill();
    }


    public function seek( int $i_uOffset, int $i_iWhence = SEEK_SET ) : void {
        if ( $i_iWhence === SEEK_SET ) {
            $this->uOffset = $i_uOffset;
        } elseif ( $i_iWhence === SEEK_CUR ) {
            $this->uOffset += $i_uOffset;
        } elseif ( $i_iWhence === SEEK_END ) {
            $this->uOffset = strlen( $this->stData ) + $i_uOffset;
        } else {
            throw new InvalidArgumentException( "Invalid whence value: {$i_iWhence}." );
        }
        if ( $this->uOffset < 0 ) {
            $this->uOffset = 0;
        }
    }


    public function tell() : int {
        return $this->uOffset;
    }


    abstract protected function fetchData() : ?string;


    protected function fillTo( int $i_uOffset ) : void {
        while ( strlen( $this->stData ) < $i_uOffset ) {
            if ( ! $this->tryFill() ) {
                $uBytesNeeded = $i_uOffset - strlen( $this->stData );
                throw new OutOfBoundsException( "Not enough data to consume {$uBytesNeeded} bytes." );
            }
        }
    }


    protected function tryFill() : bool {
        $nst = $this->fetchData();
        if ( ! is_string( $nst ) ) {
            return false;
        }
        $this->stData .= $nst;
        return true;
    }


}