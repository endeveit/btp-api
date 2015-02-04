<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Nikita Vershinin <endeveit@gmail.com>
 * @license MIT
 */
namespace Btp\Api;

/**
 * Main object for working with BTP daemon.
 */
class Connection
{

    /**
     * BTP daemon host.
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * BTP daemon port.
     *
     * @var integer
     */
    protected $port = 22400;

    /**
     * Socket resource.
     *
     * @var resource
     */
    protected $socket = null;

    /**
     * Connection was failed attribute.
     *
     * @var boolean
     */
    protected $connectionFailed = false;

    /**
     * Request object instance.
     *
     * @var \Btp\Api\Request
     */
    protected $requestInstance = null;

    /**
     * Constructor.
     *
     * @param  string            $host
     * @param  integer           $port
     * @throws \RuntimeException
     */
    public function __construct($host = null, $port = null)
    {
        if (!file_exists('/proc/self/stat')) {
            throw new \RuntimeException('Current operating system is not supported yet.');
        }

        if (null !== $host) {
            $this->host = $host;
        }

        if (null !== $port) {
            $this->port = intval($port);
        }
    }

    /**
     * Factory method to instantiate objects.
     *
     * @static
     * @param  string              $host
     * @param  integer             $port
     * @return \Btp\Api\Connection
     */
    public static function factory($host = null, $port = null)
    {
        return new self($host, $port);
    }

    /**
     * Destructor.
     * Closes socket.
     */
    public function __destruct()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Returns request instance.
     *
     * @return \Btp\Api\Request
     */
    public function getRequest()
    {
        if (null === $this->requestInstance) {
            $this->requestInstance = new Request($this);
        }

        return $this->requestInstance;
    }

    /**
     * Returns new counter.
     *
     * @param  array            $data
     * @return \Btp\Api\Counter
     */
    public function getCounter(array $data)
    {
        return new Counter($this->getRequest(), $data);
    }

    /**
     * Returns if connection was failed.
     *
     * @return boolean
     */
    public function isFailed()
    {
        $this->connect();

        return $this->connectionFailed;
    }

    /**
     * Sending data to BTP daemon.
     *
     * @param string $method
     * @param array  $params
     */
    public function notify($method, array $params)
    {
        $this->connect();

        if ($this->socket && !$this->isFailed()) {
            fwrite(
                $this->socket,
                json_encode(array('jsonrpc' => '2.0','method' => $method, 'params' => $params))."\r\n"
            );
        }
    }

    /**
     * Connect to the BTP daemon.
     */
    protected function connect()
    {
        if ((null === $this->socket) && (false === $this->connectionFailed)) {
            $this->socket = @fsockopen('udp://' . $this->host, $this->port);
            if (!$this->socket) {
                $this->connectionFailed = true;
            }
        }
    }

}
