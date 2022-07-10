<?php


declare( strict_types = 1 );


namespace JDWX\DNSQuery\Cache;


use JDWX\DNSQuery\Exception;
use JDWX\DNSQuery\Lookups;
use Shmop;


/**
 * DNS Library for handling lookups and updates. 
 *
 * Copyright (c) 2020, Mike Pultz <mike@mikepultz.com>. All rights reserved.
 *
 * See LICENSE for more details.
 *
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2020 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      https://netdns2.com/
 * @since     File available since Release 1.1.0
 *
 */

/**
 * Shared Memory-based caching for the Net_DNS2_Cache class
 *
 */
class ShmCache extends Cache
{
    /*
     * resource id of the shared memory cache
     */
    private bool|Shmop $_cache_id = false;

    /*
     * the IPC key
     */
    private int $_cache_file_tok = -1;

    /**
     * open a cache object
     *
     * @param string $cache_file path to a file to use for cache storage
     * @param int    $size       the size of the shared memory segment to create
     * @param string $serializer the name of the cache serializer to use
     *
     * @throws Exception
     * @access public
     * @return void
     *
     */
    public function open( string $cache_file, int $size, string $serializer ) : void
    {
        $this->cache_size       = $size;
        $this->cache_file       = $cache_file;
        $this->cache_serializer = $serializer;

        //
        // if we've already loaded the cache data, then just return right away
        //
        if ( $this->cache_opened )
        {
            return;
        }

        //
        // make sure the file exists first
        //
        if (!file_exists($cache_file)) {

            if (file_put_contents($cache_file, '') === false) {
        
                throw new Exception(
                    'failed to create empty SHM file: ' . $cache_file,
                    Lookups::E_CACHE_SHM_FILE
                );
            }
        }

        //
        // convert the filename to an IPC key
        //
        $this->_cache_file_tok = ftok($cache_file, 't');
        if ($this->_cache_file_tok == -1) {

            throw new Exception(
                'failed on ftok() file: ' . $this->_cache_file_tok,
                Lookups::E_CACHE_SHM_FILE
            );
        }

        //
        // try to open an existing cache; if it doesn't exist, then there's no
        // cache, and nothing to do.
        //
        $shm = @shmop_open($this->_cache_file_tok, 'w', 0, 0);
        if ( $shm === false ) {
            return;
        }
        $this->_cache_id = $shm;

        //
        // this returns the size allocated, and not the size used, but it's
        // still a good check to make sure there's space allocated.
        //
        $allocated = shmop_size($this->_cache_id);
        if ($allocated > 0) {

            //
            // read the data from the shared memory segment
            //
            $data = shmop_read($this->_cache_id, 0, $allocated);
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ( is_string( $data ) && (strlen($data) > 0) ) {
                $data = trim( $data );

                //
                // unserialize and store the data
                //

                if ($this->cache_serializer == 'json') {

                    $decoded = json_decode($data, true);
                } else {

                    $decoded = unserialize($data);
                }

                if ( is_array( $decoded ) ) {

                    $this->cache_data = $decoded;
                } else {

                    $this->cache_data = [];
                }

                //
                // call clean to clean up old entries
                //
                $this->clean();

                //
                // mark the cache as loaded, so we don't load it more than once
                //
                $this->cache_opened = true;
            }
        }
    }

    /**
     * Destructor
     *
     * @access public
     *
     */
    public function __destruct()
    {
        //
        // if there's no cache file set, then there's nothing to do
        //
        if (strlen($this->cache_file) == 0) {
            return;
        }

        $fp = fopen($this->cache_file, 'r');
        if ( $fp === false ) {
            return;
        }

        //
        // lock the file
        //
        flock($fp, LOCK_EX);

        //
        // check to see if we have an open shm segment
        //
        if ($this->_cache_id === false) {

            //
            // try opening it again, in case it was created by another
            // process in the meantime
            //
            $this->_cache_id = @shmop_open(
                $this->_cache_file_tok, 'w', 0, 0
            );
            if ($this->_cache_id === false) {

                //
                // otherwise, create it.
                //
                $this->_cache_id = @shmop_open(
                    $this->_cache_file_tok, 'c', 0, $this->cache_size
                );
            }
        }

        //
        // get the size allocated to the segment
        //
        $allocated = shmop_size($this->_cache_id);

        //
        // read the contents
        //
        $data = shmop_read($this->_cache_id, 0, $allocated);

        //
        // if there was some data
        //
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ( is_string( $data ) && (strlen($data) > 0) ) {
            $data = trim($data);

            //
            // unserialize and store the data
            //
            $c = $this->cache_data;

            if ($this->cache_serializer == 'json') {
                $decoded = json_decode($data, true);
            } else {
                $decoded = unserialize($data);
            }

            if ( is_array( $decoded ) ) {
                $this->cache_data = array_merge($c, $decoded);
            }
        }

        //
        // delete the segment
        //
        shmop_delete($this->_cache_id);

        //
        // clean the data
        //
        $this->clean();

        //
        // clean up and write the data
        //
        $data = $this->resize();
        if (!is_null($data)) {

            //
            // re-create segment
            //
            $this->_cache_id = @shmop_open(
                $this->_cache_file_tok, 'c', 0644, $this->cache_size
            );
            if ($this->_cache_id !== false) {
                shmop_write($this->_cache_id, $data, 0);
            }

        }

        //
        // unlock
        //
        flock($fp, LOCK_UN);

        //
        // close the file
        //
        fclose($fp);
    }
}
