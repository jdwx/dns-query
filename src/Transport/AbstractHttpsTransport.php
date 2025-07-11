<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Transport;


use JDWX\Strict\TypeIs;


abstract class AbstractHttpsTransport implements TransportInterface {


    protected readonly string $stURL;

    /** @var list<string> */
    protected array $rBuffer = [];

    protected float $fTimeoutSeconds;


    public function __construct( string $i_stURL, ?int $i_nuTimeoutSeconds = null, ?int $i_nuTimeoutMicroseconds = null ) {
        $i_nuTimeoutSeconds ??= 5;
        $i_nuTimeoutMicroseconds ??= 0;

        if ( ! str_contains( $i_stURL, '/' ) ) {
            $i_stURL .= '/dns-query';
        }
        if ( ! str_contains( $i_stURL, '://' ) ) {
            $i_stURL = 'https://' . $i_stURL;
        }
        $this->stURL = $i_stURL;
        $this->fTimeoutSeconds = $i_nuTimeoutSeconds + ( $i_nuTimeoutMicroseconds / 1_000_000.0 );
    }


    public function receive( int $i_uBufferSize = 65_536 ) : ?string {
        if ( empty( $this->rBuffer ) ) {
            return null;
        }
        $stData = TypeIs::string( array_shift( $this->rBuffer ) );
        if ( strlen( $stData ) > $i_uBufferSize ) {
            $stData = substr( $stData, 0, $i_uBufferSize );
        }
        return $stData;
    }


    public function timeout() : float {
        return $this->fTimeoutSeconds;
    }


}
