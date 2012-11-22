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
 * Request object with which all counters working.
 */
class Request
{

    /**
     * Connection object.
     *
     * @var \Btp\Api\Connection
     */
    protected $connection = null;

    /**
     * If connection was failed, disable the requests.
     *
     * @var boolean
     */
    protected $isDisabled = false;

    /**
     * Stats from /proc/self/stat
     *
     * @var array
     */
    protected $procStats;

    /**
     * In what time the object was initialized.
     *
     * @var float
     */
    protected $timeStart;

    /**
     * Number of items in queue.
     *
     * @var integer
     */
    protected $nbItems = 0;

    /**
     * List of queue items.
     *
     * @var array
     */
    protected $items = array();

    /**
     * Current script name.
     *
     * @var string
     */
    protected $scriptName;

    /**
     * Constructor.
     *
     * @param \Btp\Api\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        // If connection is failed, we'll don't send stats
        $this->isDisabled = $this->connection->isFailed();

        $this->procStats  = explode(' ', file_get_contents('/proc/self/stat'));
        $this->timeStart  = microtime(true);
        $this->scriptName = $_SERVER['PHP_SELF'];
    }

    /**
     * Destructor.
     * Sends timings.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Sends timings.
     */
    public function close()
    {
        if (!$this->isDisabled) {
            $this->send(true);
        }
    }

    /**
     * Appends item to the queue.
     *
     * @param array $item
     */
    public function append(array $item)
    {
        if (empty($item['service']) || empty($item['srv']) || empty($item['op']) || empty($item['ts'])) {
            return;
        }

        if (!$this->isDisabled) {
            if (!array_key_exists($item['service'], $this->items)) {
                $this->items[$item['service']] = array();
            }
            if (!array_key_exists($item['srv'], $this->items[$item['service']])) {
                $this->items[$item['service']][$item['srv']] = array();
            }
            if (!array_key_exists($item['op'], $this->items[$item['service']][$item['srv']])) {
                $this->items[$item['service']][$item['srv']][$item['op']] = array();
                ++$this->nbItems;
            }

            $this->items[$item['service']][$item['srv']][$item['op']][] = intval($item['ts']);

            if ($this->nbItems >= 30 || microtime(true) - $this->timeStart > 1) {
                $this->send(false);
            }
        }
    }

    /**
     * Sends data to the BTP daemon.
     *
     * @param boolean $timings
     */
    protected function send($timings)
    {
        $srv = php_uname('n');

        if ($timings) {
            $new  = explode(' ', file_get_contents('/proc/self/stat'));
            $farm = 'SCRIPT_' . preg_replace('~\d~', '', $srv);

            if (!array_key_exists($farm, $this->items)) {
                $this->items[$farm] = array();
            }

            $this->items[$farm][$srv] = array(
                'system' => array(10000 * ($new[14] - $this->procStats[14])),
                'user'   => array(10000 * ($new[13] - $this->procStats[13])),
                'all'    => array(round(1000000 * (microtime(true) - $this->timeStart))),
            );
        }

        $data = array(
            'srv'    => $srv,
            'script' => $this->scriptName,
            'time'   => $this->timeStart,
            'items'  => $this->items,
        );

        $this->connection->notify('put', $data);

        $this->items   = array();
        $this->nbItems = 0;
    }

}
