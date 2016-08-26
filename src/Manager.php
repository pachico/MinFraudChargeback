<?php

/**
 * This file is part of Pachico/MinFraudChargeback. (https://github.com/pachico/minfraud-chargeback)
 *
 * @link https://github.com/pachico/minfraud-chargeback for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Mariano F.co Benítez Mulet. (https://github.com/pachico/)
 * @license https://raw.githubusercontent.com/pachico/minfraud-chargeback/master/LICENSE.md MIT
 */

namespace Pachico\MinFraudChargeback;

use Pachico\MinFraudChargeback\Auth\Credential;
use Pachico\MinFraudChargeback\Http\ClientInterface;
use Pachico\MinFraudChargeback\Http\CurlClient;

/**
 *
 */
class Manager
{
    /**
     * @var Credential
     */
    protected $credential;
    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @param Credential $credential
     * @param ClientInterface $httpClient
     */
    public function __construct(Credential $credential, ClientInterface $httpClient = null)
    {
        $this->credential = $credential;
        $this->httpClient = $httpClient ? : new CurlClient($credential);
    }

    /**
     * @param string $seconds
     *
     * @return \Pachico\MinFraudChargeback\Manager
     */
    public function setTimeout($seconds)
    {
        $this->httpClient->setTimeout($seconds);

        return $this;
    }

    /**
     * @param int $seconds
     *
     * @return \Pachico\MinFraudChargeback\Manager
     */
    public function setConnectTimeout($seconds)
    {
        $this->httpClient->setConnectTimeout($seconds);

        return $this;
    }

    /**
     * @param string $hostname
     *
     * @return \Pachico\MinFraudChargeback\Manager
     */
    public function setHostname($hostname)
    {
        $this->httpClient->setHostname($hostname);

        return $this;
    }

    /**
     * @param \Pachico\MinFraudChargeback\Chargeback $chargeback
     *
     * @return true
     *
     * @throws Exception\ExceptionAbstract
     */
    public function report(Chargeback $chargeback)
    {
        return $this->httpClient->report($chargeback);
    }
}