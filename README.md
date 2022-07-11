## WARNING: This package is not currently in a usable state.

This package has recently been forked from pear/net_dns2 with the following objectives:

* Full PHP 8.1 compatibility.
* Pass my organization's internal code quality standards.
* Develop additional unit tests.

*Large* changes are in progress that are not yet adequately tested.  

Here are some backwards-incompatible changes planned:
* PEAR support is being removed.
* Certain things that were previously optional (e.g., the [filter extension](https://www.php.net/manual/en/book.filter.php)) are now required.
* PSR-4 namespaces are being introduced.
* Replacing the array-style options configuration with explicit methods.
* Changing the cache implementation
* Changing variable names

I apologize that backwards compatibility is **not** a priority; my company
required adherence to certain coding standards in order to support this work.
This is therefore likely only suited for new development, and possibly
only for *our* new development.

# DNSQuery - Native PHP DNS Resolver and Updater #

### The main features for this package include: ###

  * Uses modern PHP namespaces, classes and exceptions
  * Support for IPv4 and IPv6, TCP and UDP sockets.
  * Includes a separate, more intuitive "Updater" class for handling dynamic update
  * Support zone signing using TSIG and SIG(0) for updates and zone transfers
  * Includes a local cache using shared memory or flat file to improve performance
  * includes many more RRs, including DNSSEC RRs.

## Installing DNSQuery ##

You can require it directly via Composer: https://packagist.org/packages/jdwx/dns-query

```
composer require jdwx/dns-query
```

NOTE: As of 2022-07-10 this package has not yet been submitted to Packagist, so the above information
is preliminary.

Or download the source from Github: https://github.com/jdwx/dns-query

## Requirements ##

* PHP 8.1+
* The PHP INI setting `mbstring.func_overload` equals 0, 1, 4, or 5.


## Using DNSQuery ##

Usage examples to follow.

## History ##

This package was forked from Net_DNS2, which was maintained by Mike 
Pultz until 2020.  That package represents an enormous amount of work
done over many years.  As such Mike Pultz deserves full credit for 
most of this package; most of what I am doing is window dressing and
adapting it to meet my specific needs.  But since this is a public
repository, I wanted to make clear that I do not claim credit for Mike's
original work.

See the original Net_DNS2 Website for more details - https://netdns2.com/

