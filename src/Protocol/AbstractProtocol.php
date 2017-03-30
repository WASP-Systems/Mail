<?php
/*
This is part of Wedeto, the WEb DEvelopment TOolkit.
Wedeto\Mail is published under the BSD 3-Clause License.

Wedeto\Mail\Protocol\AbstractProtocol was adapted from Zend\Mail\Protocol\AbstractProtocol.
The modifications are: Copyright 2017, Egbert van der Wal.

The original source code is copyright Zend Technologies USA Inc. The original
licence information is included below.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this
list of conditions and the following disclaimer. Redistributions in binary form
must reproduce the above copyright notice, this list of conditions and the
following disclaimer in the documentation and/or other materials provided with
the distribution. Neither the name of Zend or Rogue Wave Software, nor the
names of its contributors may be used to endorse or promote products derived
from this software without specific prior written permission. THIS SOFTWARE IS
PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
*/

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wedeto\Mail\Protocol;

/**
 * Provides low-level methods for concrete adapters to communicate with a
 * remote mail server and track requests and responses.
 */
abstract class AbstractProtocol
{
    /**
     * Mail default EOL string
     */
    const EOL = "\r\n";

    /**
     * Default timeout in seconds for initiating session
     */
    const TIMEOUT_CONNECTION = 30;


    /** The connection configuration */
    protected $config;

    /** Maximum of the transaction log */
    protected $maximumLog = 64;

    /** Hostname or IP address of remote server */
    protected $host;

    /** Port number of connection */
    protected $port;

    /** Socket connection resource */
    protected $socket;

    /** Last request sent to server */
    protected $request;

    /** Array of server responses to last request */
    protected $response;

    /** Log of mail requests and server responses for a session */
    private $log = array();

    /**
     * Constructor.
     *
     * @param  string  $host OPTIONAL Hostname of remote connection (default: 127.0.0.1)
     * @param  int $port OPTIONAL Port number (default: null)
     * @throws Exception\RuntimeException
     */
    public function __construct(array $config)
    {
        $ip_address = gethostbyname($config['host']);
        $ip_address = filter_var($ip_address, FILTER_VALIDATE_IP);
        if ($ip_address === false)
            throw new ProtocolException('Invalid SMTP server host: ' . $config['host']);

        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->config = $config;
    }

    /**
     * Class destructor to cleanup open resources
     *
     */
    public function __destruct()
    {
        $this->_disconnect();
    }

    /**
     * Set the maximum log size
     *
     * @param int $maximumLog Maximum log size
     */
    public function setMaximumLog($maximumLog)
    {
        $this->maximumLog = (int)$maximumLog;
    }

    /**
     * Get the maximum log size
     *
     * @return int the maximum log size
     */
    public function getMaximumLog()
    {
        return $this->maximumLog;
    }

    /**
     * Create a connection to the remote host
     *
     * Concrete adapters for this class will implement their own unique connect
     * scripts, using the _connect() method to create the socket resource.
     */
    abstract public function connect();

    /**
     * Retrieve the last client request
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Retrieve the last server response
     *
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Retrieve the transaction log
     *
     * @return string
     */
    public function getLog()
    {
        return implode('', $this->log);
    }

    /**
     * Reset the transaction log
     *
     */
    public function resetLog()
    {
        $this->log = array();
    }

    /**
     * Add the transaction log
     *
     * @param string $value new transaction
     */
    protected function _addLog(string $value)
    {
        if ($this->maximumLog >= 0 && count($this->log) >= $this->maximumLog)
            array_shift($this->log);

        $this->log[] = $value;
    }

    /**
     * Connect to the server using the supplied transport and target
     *
     * An example $remote string may be 'tcp://mail.example.com:25' or 'ssh://hostname.com:2222'
     *
     * @param  string $remote Remote
     * @throws Exception\RuntimeException
     * @return bool
     */
    protected function _connect(string $remote)
    {
        $errorNum = 0;
        $errorStr = '';

        // open connection
        try
        {
            $this->socket = stream_socket_client($remote, $errorNum, $errorStr, self::TIMEOUT_CONNECTION);
        }
        catch (\Throwable $e)
        {
            throw new ProtocolException('Could not open socket', 0, $e);
        }

        if ($this->socket === false)
        {
            if ($errorNum == 0)
                $errorStr = 'Could not open socket';
            throw new ProtocolException($errorStr);
        }

        if (($result = stream_set_timeout($this->socket, self::TIMEOUT_CONNECTION)) === false)
            throw new ProtocolException('Could not set stream timeout');

        return $result;
    }

    /**
     * Disconnect from remote host and free resource
     *
     */
    protected function _disconnect()
    {
        if (is_resource($this->socket))
            fclose($this->socket);
    }

    /**
     * Send the given request followed by a LINEEND to the server.
     *
     * @param  string $request
     * @throws Exception\RuntimeException
     * @return int|bool Number of bytes written to remote host
     */
    protected function _send(string $request)
    {
        if (!is_resource($this->socket))
            throw new ProtocolException('No connection has been established to ' . $this->host);

        $this->request = $request;
        $result = fwrite($this->socket, $request . self::EOL);

        // Save request to internal log
        $this->_addLog($request . self::EOL);

        if ($result === false)
            throw new ProtocolException('Could not send request to ' . $this->host);

        return $result;
    }

    /**
     * Get a line from the stream.
     *
     * @param  int $timeout Per-request timeout value if applicable
     * @throws ProtocolException
     * @return string The read line
     */
    protected function _receive(int $timeout = 0)
    {
        if (!is_resource($this->socket))
            throw new ProtocolException('No connection has been established to ' . $this->host);

        // Adapters may wish to supply per-commend timeouts according to appropriate RFC
        if ($timeout > 0)
            stream_set_timeout($this->socket, $timeout);

        // Retrieve response
        $response = fgets($this->socket, 1024);

        // Save request to internal log
        $this->_addLog($response);

        // Check meta data to ensure connection is still valid
        $info = stream_get_meta_data($this->socket);

        if (!empty($info['timed_out']))
            throw new ProtocolException($this->host . ' has timed out');

        if ($response === false)
            throw new ProtocolException('Could not read from ' . $this->host);

        return $response;
    }

    /**
     * Parse server response for successful codes
     *
     * Read the response from the stream and check for expected return code.
     * Throws a Wedeto\Mail\Protocol\ProtocolException if an unexpected code is returned.
     *
     * @param  string|array $code One or more codes that indicate a successful response
     * @param  int $timeout Per-request timeout value if applicable
     * @throws Wedeto\Mail\Protocol\ProtocolException
     * @return string Last line of response string
     */
    protected function _expect($code, int $timeout = 0)
    {
        $this->response = array();
        $errMsg = '';

        if (!is_array($code))
            $code = array($code);

        do
        {
            $this->response[] = $result = $this->_receive($timeout);
            list($cmd, $more, $msg) = preg_split('/([\s-]+)/', $result, 2, PREG_SPLIT_DELIM_CAPTURE);

            if ($errMsg !== '')
                $errMsg .= ' ' . $msg;
            elseif ($cmd === null || ! in_array($cmd, $code))
                $errMsg = $msg;
        } // The '-' message prefix indicates an information string instead of a response string.
        while (strpos($more, '-') === 0);

        if ($errMsg !== '')
            throw new ProtocolException($errMsg);

        return $msg;
    }
}
