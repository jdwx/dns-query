<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Buffer;


use OutOfBoundsException;


class WriteBuffer implements WriteBufferInterface {


    private string $stBuffer = '';


    public function __toString() : string {
        return $this->stBuffer;
    }


    public function append( int|string ...$i_rData ) : int {
        $uLength = strlen( $this->stBuffer );
        foreach ( $i_rData as $st ) {
            $this->stBuffer .= $st;
        }
        return $uLength;
    }


    public function clear() : void {
        $this->stBuffer = '';
    }


    public function end() : string {
        $st = $this->stBuffer;
        $this->clear();
        return $st;
    }


    public function length() : int {
        return strlen( $this->stBuffer );
    }


    public function set( int $i_uOffset, int|string $i_istData ) : void {
        $uLength = strlen( $i_istData );
        if ( $i_uOffset < 0 || $i_uOffset > strlen( $this->stBuffer ) ) {
            throw new OutOfBoundsException( "Offset {$i_uOffset} is out of bounds." );
        }
        $this->stBuffer = substr_replace( $this->stBuffer, strval( $i_istData ), $i_uOffset, $uLength );
    }


    public function shift( int $i_uLength ) : string {
        if ( $i_uLength < 0 || $i_uLength > strlen( $this->stBuffer ) ) {
            throw new OutOfBoundsException( "Length {$i_uLength} is out of bounds." );
        }
        $st = substr( $this->stBuffer, 0, $i_uLength );
        $this->stBuffer = substr( $this->stBuffer, $i_uLength );
        return $st;
    }


}
