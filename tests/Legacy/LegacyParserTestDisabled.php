<?php


declare( strict_types = 1 );


/**
 * DNS Library for handling lookups and updates.
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.0.0
 *
 */


namespace JDWX\DNSQuery\Tests\Legacy;


use JDWX\DNSQuery\Exceptions\Exception;
use JDWX\DNSQuery\Legacy\Packet\RequestPacket;
use JDWX\DNSQuery\Legacy\Packet\ResponsePacket;
use JDWX\DNSQuery\Legacy\RR\RR;
use JDWX\DNSQuery\Legacy\RR\TSIG;
use JDWX\DNSQuery\Legacy\Updater;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;


/**
 * Test class to test the parsing code
 *
 */
#[CoversClass( RR::class )]
final class LegacyParserTestDisabled extends TestCase {


    /**
     * function to test the compression logic
     *
     * @return void
     * @access public
     *
     * @throws Exception
     */
    public function testCompression() : void {
        # This list of RRs uses name compression.
        /** @noinspection SpellCheckingInspection */
        $rrs = [
            'NS' => 'example.com. 300 IN NS ns1.mrdns.com.',
            'CNAME' => 'example.com. 300 IN CNAME www.example.com.',
            'SOA' => 'example.com. 300 IN SOA ns1.mrdns.com. help\.desk.mrhost.ca. 1278700841 900 1800 86400 21400',
            'MX' => 'example.com. 300 IN MX 10 mx1.mrhost.ca.',
            'RP' => 'example.com. 300 IN RP louie\.trantor.umd.edu. lam1.people.test.com.',
            'AFSDB' => 'example.com. 300 IN AFSDB 3 afsdb.example.com.',
            'RT' => 'example.com. 300 IN RT 2 relay.prime.com.',
            'PX' => 'example.com. 300 IN PX 10 ab.net2.it. o-ab.prmd-net2.admdb.c-it.',
            'SRV' => 'example.com. 300 IN SRV 20 0 5269 xmpp-server2.l.google.com.',
            'NAPTR' => 'example.com. 300 IN NAPTR 100 10 S SIP+D2U !^.*$!sip:customer-service@example.com! _sip._udp.example.com.',
            'DNAME' => 'example.com. 300 IN DNAME frobozz-division.acme.example.',
            'HIP' => 'example.com. 300 IN HIP 2 200100107B1A74DF365639CC39F1D578 AwEAAbdxyhNuSutc5EMzxTs9LBPCIkOFH8cIvM4p9+LrV4e19WzK00+CI6zBCQTdtWsuxKbWIy87UOoJTwkUs7lBu+Upr1gsNrut79ryra+bSRGQb1slImA8YVJyuIDsj7kwzG7jnERNqnWxZ48AWkskmdHaVDP4BcelrTI3rMXdXF5D rvs.example.com. another.example.com. test.domain.org.',
        ];

        # Create a new updater object.
        $updater = new Updater( 'example.com' );
        $updater->setNameServer( '10.10.0.1' );

        # Add each RR to the same object, so we can build a build compressed name list.
        foreach ( $rrs as $rrType => $line ) {

            $className = '\\JDWX\\DNSQuery\\RR\\' . $rrType;

            # Parse the line.
            $rr = RR::fromString( $line );

            # Check that the object is right.
            self::assertInstanceOf( $className, $rr );

            # Set it on the packet.
            $updater->add( $rr );
        }

        # Get the request packet.
        $request = $updater->packet();

        # Get the authority section of the request.
        $requestAuthority = $request->authority;

        # Parse the binary.
        $data = $request->get();
        $response = new ResponsePacket( $data, strlen( $data ) );

        # Get the authority section of the response, and clean up the
        # rdata so everything will match.
        #
        # The request packet doesn't have the rdLength and rdata fields
        # built yet, so it will throw off the hash.
        $responseAuthority = $response->authority;

        foreach ( $responseAuthority as $object ) {
            $object->rdLength = 0;
            $object->rdata = '';
        }

        # Build the hashes.
        $rr = md5( print_r( $requestAuthority, true ) );
        $otherRR = md5( print_r( $responseAuthority, true ) );

        # The new hashes should match.
        self::assertSame( $rr, $otherRR );
    }


    /**
     * function to test parsing the individual RRs
     *
     * @return void
     * @access public
     *
     * @throws Exception
     */
    public function testParser() : void {
        /** @noinspection SpellCheckingInspection */
        $rrs = [
            'A' => 'example.com. 300 IN A 172.168.0.50',
            'NS' => 'example.com. 300 IN NS ns1.mrdns.com.',
            'CNAME' => 'example.com. 300 IN CNAME www.example.com.',
            'SOA' => 'example.com. 300 IN SOA ns1.mrdns.com. help\.team.mrhost.ca. 1278700841 900 1800 86400 21400',
            'WKS' => 'example.com. 300 IN WKS 128.8.1.14 6 21 25',
            'PTR' => '1.0.0.127.in-addr.arpa. 300 IN PTR localhost.',
            'HINFO' => 'example.com. 300 IN HINFO "PC-Intel-700mhz" "Redhat \"Linux\" 7.1"',
            'MX' => 'example.com. 300 IN MX 10 mx1.mrhost.ca.',
            'TXT' => [
                'example.com. 300 IN TXT "first record" "another records" "a third"',
                'another.example.com. 3600 IN TXT "k=rsa;p=DAQAB" " aa " "b "', # GitHub Issue 8
            ],
            'RP' => 'example.com. 300 IN RP louie\.trantor.umd.edu. lam1.people.test.com.',
            'AFSDB' => 'example.com. 300 IN AFSDB 3 afsdb.example.com.',
            'X25' => 'example.com. 300 IN X25 "311 06 17 0 09 56"',
            'ISDN' => 'example.com. 300 IN ISDN "150 862 028 003 217" "42"',
            'RT' => 'example.com. 300 IN RT 2 relay.prime.com.',
            'NSAP' => 'example.com. 300 IN NSAP 0x47.0005.80.005a00.0000.0001.e133.aaaaaa000151.00',
            'SIG' => 'example.com. 300 IN SIG DNSKEY 7 1 86400 20100827211706 20100822211706 57970 gov. KoWPhMtLHp8sWYZSgsMiYJKB9P71CQmh9CnxJCs5GutKfo7Jpw+nNnDLiNnsd6U1JSkf99rYRWCyOTAPC47xkHr+2Uh7n6HDJznfdCzRa/v9uwEcbXIxCZ7KfzNJewW3EvYAxDIrW6sY/4MAsjS5XM/O9LaWzw6pf7TX5obBbLI+zRECbPNTdY+RF6Fl9K0GVaEZJNYi2PRXnATwvwca2CNRWxeMT/dF5STUram3cWjH0Pkm19Gc1jbdzlZVDbUudDauWoHcc0mfH7PV1sMpe80NqK7yQ24AzAkXSiknO13itHsCe4LECUu0/OtnhHg2swwXaVTf5hqHYpzi3bQenw==',
            'KEY' => 'example.com. 300 IN KEY 256 3 7 AwEAAYCXh/ZABi8kiJIDXYmyUlHzC0CHeBzqcpyZAIjC7dK1wkRYVcUvIlpTOpnOVVfcC3Py9Ui/x45qKb0LytvK7WYAe3WyOOwk5klwIqRC/0p4luafbd2yhRMF7quOBVqYrLoHwv8i9LrV+r8dhB7rXv/lkTSI6mEZsg5rDfee8Yy1',
            'PX' => 'example.com. 300 IN PX 10 ab.net2.it. o-ab.prmd-net2.admdb.c-it.',
            'AAAA' => 'example.com. 300 IN AAAA 1080:0:0:0:8:800:200c:417a',
            'LOC' => 'example.com. 300 IN LOC 42 21 54.675 N 71 06 18.343 W 24.12m 30.00m 40.00m 5.00m',
            'SRV' => 'example.com. 300 IN SRV 20 0 5269 xmpp-server2.l.google.com.',
            'ATMA' => 'example.com. 300 IN ATMA 39246f00e7c9c0312000100100001234567800',
            'NAPTR' => 'example.com. 300 IN NAPTR 100 10 "S" "SIP+D2U" "!^.*$!sip:customer-service@example.com!" _sip._udp.example.com.',
            'KX' => 'example.com. 300 IN KX 10 mx1.mrhost.ca.',
            'CERT' => 'example.com. 300 IN CERT 3 0 0 TUlJQ1hnSUJBQUtCZ1FDcXlqbzNFMTU0dFU1Um43ajlKTFZsOGIwcUlCSVpGWENFelZvanVJT1BsMTM0by9zcHkxSE1hQytiUGh3Wk1UYVd4QlJpZHBFbUprNlEwNFJNTXdqdkFyLzFKWjhnWThtTzdCdTh1RUROVkNWeG5rQkUzMHhDSjhHRTNzL3EyN2VWSXBCUGFtU1lkNDVKZjNIeVBRRE4yaU45RjVHdGlIa2E2OXNhcmtKUnJ3SURBUUFCQW9HQkFJaUtDQ1NEM2FFUEFjQUx1MjdWN0JmR1BYN3lDTVg0OSsyVDVwNXNJdkduQjcrQ0NZZ09QaVQybmlpMGJPNVBBOTlnZnhPQXl1WCs5Z3llclVQbUFSc1ViUzcvUndkNGorRUlOVW1DanJSK2R6dGVXT0syeGxHamFOdGNPZU5jMkVtelQyMFRsekxVeUxTWGpzMzVlU2NQK0loeVptM2xJd21vbWtNb2d1QkFrRUE0a1FsOVBxaTJ2MVBDeGJCelU4Nnphblo2b0hsV0IzMUh4MllCNmFLYXhjNkVOZHhVejFzNjU2VncrRDhSVGpoSllyeDdMVkxzZDBRaVZJM0liSjVvUUpCQU1FN3k0aHg0SCtnQU40MEdrYjNjTFZGNHNpSEZrNnA2QVZRdlpzREwvVnh3bVlOdE4rM0txT3NVcG11WXZ3a3h0ajhIQnZtckxUYStXb3NmRDQwS1U4Q1FRQ1dvNmhob1R3cmI5bmdHQmFQQ2VDc2JCaVkrRUlvbUVsSm5mcEpuYWNxQlJ5emVid0pIeXdVOGsvalNUYXJIMk5HQzJ0bG5JMzRyS1VGeDZiTTJIWUJBa0VBbXBYSWZPNkZKL1NMM1RlWGNnQ1A5U1RraVlHd2NkdnhGeGVCcDlvRDZ2cElCN2FkWlgrMko5dzY5R0VUSlI0U3loSGVOdC95ZUhqWm9YdlhKVGc3ZHdKQVpEamxwL25wNEFZV3JYaGFrMVAvNGZlaDVNSU5WVHNXQkhTNlRZNW0xRmZMUEpybklHNW1FSHNidWkvdnhuQ1JmRUR4ZlU1V1E0cS9HUkZuaVl3SHB3PT0=',
            'DNAME' => 'example.com. 300 IN DNAME frobozz-division.acme.example.',
            'APL' => 'example.com. 300 IN APL 1:224.0.0.0/4 2:a0:0:0:0:0:0:0:0/8 !1:192.168.38.0/28',
            'DS' => 'example.com. 300 IN DS 21366 7 2 96eeb2ffd9b00cd4694e78278b5efdab0a80446567b69f634da078f0d90f01ba',
            'SSHFP' => 'example.com. 300 IN SSHFP 2 1 123456789abcdef67890123456789abcdef67890',
            'IPSECKEY' => 'example.com. 300 IN IPSECKEY 10 2 2 2001:db8:0:8002:0:0:2000:1 AQNRU3mG7TVTO2BkR47usntb102uFJtugbo6BSGvgqt4AQ==',
            'RRSIG' => 'example.com. 300 IN RRSIG DNSKEY 7 1 86400 20100827211706 20100822211706 57970 gov. KoWPhMtLHp8sWYZSgsMiYJKB9P71CQmh9CnxJCs5GutKfo7Jpw+nNnDLiNnsd6U1JSkf99rYRWCyOTAPC47xkHr+2Uh7n6HDJznfdCzRa/v9uwEcbXIxCZ7KfzNJewW3EvYAxDIrW6sY/4MAsjS5XM/O9LaWzw6pf7TX5obBbLI+zRECbPNTdY+RF6Fl9K0GVaEZJNYi2PRXnATwvwca2CNRWxeMT/dF5STUram3cWjH0Pkm19Gc1jbdzlZVDbUudDauWoHcc0mfH7PV1sMpe80NqK7yQ24AzAkXSiknO13itHsCe4LECUu0/OtnhHg2swwXaVTf5hqHYpzi3bQenw==',
            'NSEC' => 'example.com. 300 IN NSEC dog.poo.com. A MX RRSIG NSEC TYPE1234',
            'DNSKEY' => 'example.com. 300 IN DNSKEY 256 3 7 AwEAAYCXh/ZABi8kiJIDXYmyUlHzC0CHeBzqcpyZAIjC7dK1wkRYVcUvIlpTOpnOVVfcC3Py9Ui/x45qKb0LytvK7WYAe3WyOOwk5klwIqRC/0p4luafbd2yhRMF7quOBVqYrLoHwv8i9LrV+r8dhB7rXv/lkTSI6mEZsg5rDfee8Yy1',
            'DHCID' => 'example.com. 300 IN DHCID AAIBY2/AuCccgoJbsaxcQc9TUapptP69lOjxfNuVAA2kjEA=',
            'NSEC3' => 'example.com. 300 IN NSEC3 1 1 12 AABBCCDD b4um86eghhds6nea196smvmlo4ors995 NS DS RRSIG',
            'NSEC3PARAM' => 'example.com. 300 IN NSEC3PARAM 1 0 1 D399EAAB',
            'TLSA' => '_443._tcp.www.example.com. 300 IN TLSA 1 1 2 92003ba34942dc74152e2f2c408d29eca5a520e7f2e06bb944f4dca346baf63c1b177615d466f6c4b71c216a50292bd58c9ebdd2f74e38fe51ffd48c43326cbc',
            'SMIMEA' => 'c93f1e400f26708f98cb19d936620da35eec8f72e57f9eec01c1afd6._smimecert.example.com. 300 IN SMIMEA 1 1 2 92003ba34942dc74152e2f2c408d29eca5a520e7f2e06bb944f4dca346baf63c1b177615d466f6c4b71c216a50292bd58c9ebdd2f74e38fe51ffd48c43326cbc',
            'HIP' => 'example.com. 300 IN HIP 2 200100107B1A74DF365639CC39F1D578 AwEAAbdxyhNuSutc5EMzxTs9LBPCIkOFH8cIvM4p9+LrV4e19WzK00+CI6zBCQTdtWsuxKbWIy87UOoJTwkUs7lBu+Upr1gsNrut79ryra+bSRGQb1slImA8YVJyuIDsj7kwzG7jnERNqnWxZ48AWkskmdHaVDP4BcelrTI3rMXdXF5D rvs.example.com. another.example.com. test.domain.org.',
            'TALINK' => 'example.com. 300 IN TALINK c1.example.com. c3.example.com.',
            'CDS' => 'example.com. 300 IN CDS 21366 7 2 96eeb2ffd9b00cd4694e78278b5efdab0a80446567b69f634da078f0d90f01ba',
            'OPENPGPKEY' => '8d5730bd8d76d417bf974c03f59eedb7af98cb5c3dc73ea8ebbd54b7._openpgpkey.example.com. 300 IN OPENPGPKEY AwEAAYCXh/ZABi8kiJIDXYmyUlHzC0CHeBzqcpyZAIjC7dK1wkRYVcUvIlpTOpnOVVfcC3Py9Ui/x45qKb0LytvK7WYAe3WyOOwk5klwIqRC/0p4luafbd2yhRMF7quOBVqYrLoHwv8i9LrV+r8dhB7rXv/lkTSI6mEZsg5rDfee8Yy1',
            'CSYNC' => 'example.com. 300 IN CSYNC 1278700841 3 A NS AAAA',
            'SPF' => 'example.com. 300 IN SPF "v=spf1 ip4:192.168.0.1/24 mx ?all"',
            'NID' => 'example.com. 300 IN NID 10 14:4fff:ff20:ee64',
            'L32' => 'example.com. 300 IN L32 10 10.1.2.0',
            'L64' => 'example.com. 300 IN L64 10 2001:db8:1140:1000',
            'LP' => 'example.com. 300 IN LP 10 l64-subnet1.example.com.',
            'EUI48' => 'example.com. 300 IN EUI48 00-00-5e-00-53-2a',
            'EUI64' => 'example.com. 300 IN EUI64 00-00-5e-ef-10-00-00-2a',
            'TKEY' => 'example.com. 300 IN TKEY gss.microsoft.com. 3 123456.',
            'URI' => 'example.com. 300 IN URI 10 1 "https://mrdns.com/contact.html"',
            'CAA' => 'example.com. 300 IN CAA 0 issue "ca.example.net; policy=ev"',
            'AVC' => 'example.com. 300 IN AVC "first record" "another records" "a third"',
            'AMTRELAY' => [
                'example.com. 300 IN AMTRELAY 10 0 0 .',
                'example.com. 300 IN AMTRELAY 10 0 1 203.0.113.15',
                'example.com. 300 IN AMTRELAY 10 0 2 2600:1f16:17c:3950:47ac:cb79:62ba:702e',
                'example.com. 300 IN AMTRELAY 10 0 3 test.google.com.',
            ],
            'TA' => 'example.com. 300 IN TA 21366 7 2 96eeb2ffd9b00cd4694e78278b5efdab0a80446567b69f634da078f0d90f01ba',
            'DLV' => 'example.com. 300 IN DLV 21366 7 2 96eeb2ffd9b00cd4694e78278b5efdab0a80446567b69f634da078f0d90f01ba',
        ];

        foreach ( $rrs as $rrType => $lines ) {

            if ( ! is_array( $lines ) ) {
                $lines = [ $lines ];
            }

            foreach ( $lines as $line ) {

                $className = '\\JDWX\\DNSQuery\\RR\\' . $rrType;

                # Create a new packet.
                if ( $rrType == 'PTR' ) {
                    $request = new RequestPacket( '1.0.0.127.in-addr.arpa', $rrType, 'IN' );
                } else {
                    $request = new RequestPacket( 'example.com', $rrType, 'IN' );
                }

                # Parse the line.
                $rr = RR::fromString( $line );

                # Check that the object is right.
                self::assertInstanceOf( $className, $rr );

                # Set it on the packet.
                $request->answer[] = $rr;
                $request->header->anCount = 1;

                # Get the binary packet data.
                $data = $request->get();

                # Parse the binary data.
                $response = new ResponsePacket( $data, strlen( $data ) );

                # The answer data in the response, should match our initial line exactly.
                self::assertSame( $line, $response->answer[ 0 ]->__toString() );
            }
        }
    }


    /**
     * function to test the TSIG logic
     *
     * @return void
     * @access public
     *
     * @throws Exception
     */
    public function testTSIG() : void {

        # Create a new packet.
        $request = new RequestPacket( 'example.com', 'SOA', 'IN' );

        # Add an A record to the authority section, like an update request.
        $request->authority[] = RR::fromString( 'test.example.com IN A 10.10.10.10' );
        $request->header->nsCount = 1;

        # Add the TSIG as additional.
        /** @noinspection SpellCheckingInspection */
        $request->additional[] = RR::fromString( 'mykey TSIG Zm9vYmFy' );
        $request->header->arCount = 1;

        assert( $request->additional[ 0 ] instanceof TSIG );
        $line = $request->additional[ 0 ]->name . '. ' . $request->additional[ 0 ]->ttl . ' ' .
            $request->additional[ 0 ]->class . ' ' . $request->additional[ 0 ]->type . ' ' .
            $request->additional[ 0 ]->algorithm . '. ' . $request->additional[ 0 ]->timeSigned . ' ' .
            $request->additional[ 0 ]->fudge;

        # Get the binary packet data.
        $data = $request->get();

        # Parse the binary.
        $response = new ResponsePacket( $data, strlen( $data ) );

        # The answer data in the response, should match our initial line exactly.
        self::assertSame( $line, substr( $response->additional[ 0 ]->__toString(), 0, 58 ) );
    }


}
