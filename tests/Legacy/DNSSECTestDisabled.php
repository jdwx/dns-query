<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Tests\Legacy;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Resolver;
use JDWX\DNSQuery\Legacy\RR\OPT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/** Basic DNSSEC test.
 *
 * TODO: Expand this dramatically.
 */
#[CoversClass( Resolver::class )]
final class DNSSECTestDisabled extends TestCase {


    /**
     * @throws Exception
     */
    public function testDOFlag() : void {
        $dns = new Resolver( '8.8.8.8' );
        $dns->setDNSSEC();
        $rsp = $dns->query( 'org.', 'SOA' );
        self::assertSame( 1, $rsp->header->ad );
        self::assertCount( 1, $rsp->additional );
        $opt = $rsp->additional[ 0 ];
        assert( $opt instanceof OPT );
        self::assertSame( 1, $opt->do );
    }


}