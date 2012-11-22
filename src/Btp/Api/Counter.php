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
 * Counter object used for measurement.
 */
class Counter
{

    /**
     * Request object.
     *
     * @var \Btp\Api\Request
     */
    protected $request = null;

    /**
     * Array with counter data.
     *
     * @var array
     */
    protected $data = null;

    /**
     * In what time the counter was initialized.
     *
     * @var float
     */
    protected $timeStart = null;

    /**
     * Constructor.
     *
     * @param  \Btp\Api\Request          $request
     * @param  array                     $data
     * @throws \InvalidArgumentException
     */
    public function __construct(Request $request, array $data)
    {
        $this->request = $request;

        if (empty($data['srv'])) {
            throw new \InvalidArgumentException('You must provide "srv" key in $data');
        }

        if (empty($data['service'])) {
            $service = preg_replace('~\.(\d+)~', '', $data['srv']);
            $service = preg_replace('~([._](tcp|udp|json|test))~','', $service);
            $service = preg_replace('~(\d+)$~','', $service);

            $data['service'] = $service;
        }

        $this->data = $data;
        $this->timeStart = microtime(true);
    }

    /**
     * Destructor.
     * Stops the counter and appends item to the request queue.
     */
    public function __destruct()
    {
        $this->stop();
    }

    /**
     * Stops the counter and appends item to the request queue.
     */
    public function stop()
    {
        if (null !== $this->data) {
            $tmp = $this->data + array('ts' => round(1000000 * (microtime(true) - $this->timeStart)));
            $this->request->append($tmp);

            $this->data = null;
        }
    }

    /**
     * Sets the operation name.
     *
     * @param string $operation
     */
    public function setOperation($operation)
    {
        $this->data['op'] = $operation;
    }

}
