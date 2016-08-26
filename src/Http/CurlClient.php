<?php

/**
 * This file is part of Pachico/MinFraudChargeback. (https://github.com/pachico/minfraud-chargeback)
 *
 * @link https://github.com/pachico/minfraud-chargeback for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Mariano F.co Benítez Mulet. (https://github.com/pachico/)
 * @license https://raw.githubusercontent.com/pachico/minfraud-chargeback/master/LICENSE.md MIT
 */

namespace Pachico\MinFraudChargeback\Http;

use Pachico\MinFraudChargeback\Auth\Credential;
use Curl\Curl;
use Pachico\MinFraudChargeback\Chargeback;
use Pachico\MinFraudChargeback\Exception\ExceptionAbstract;
use Pachico\MinFraudChargeback\Exception;
use Webmozart\Assert\Assert;

class CurlClient implements ClientInterface
{
    /**
     * The URI for this service is
     * @see https://minfraud.maxmind.com/minfraud/chargeback.
     * The minfraud.maxmind.com hostname automatically picks the data center geographically
     * closest to you. In some cases, this data center may not be the one
     * that provides you with the best service. You can explicitly try the
     * following hostnames to see which one provides the best performance for you:
     */
    const HOSTNAME_DEFAULT = 'https://minfraud.maxmind.com/minfraud/chargeback_';
    const HOSTNAME_US_EAST = 'https://minfraud-us-east.maxmind.com/minfraud/chargeback';
    const HOSTNAME_US_WEST = 'https://minfraud-us-west.maxmind.com/minfraud/chargeback';
    const HOSTNAME_EU_WEST = 'https://minfraud-eu-west.maxmind.com/minfraud/chargeback';

    /**
     * @var Credential
     */
    private $credential;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var string
     */
    private $hostname;

    /**
     * @param Credential $credential
     * @param Curl $curl
     */
    public function __construct(Credential $credential, Curl $curl = null)
    {
        $this->credential = $credential;

        $this->curl = $curl ? : new Curl();

        $this->hostname = static::HOSTNAME_DEFAULT;
    }

    /**
     * @param int $seconds
     *
     * @return \Pachico\MinFraudChargeback\Http\CurlClient
     */
    public function setConnectTimeout($seconds)
    {
        $this->curl->setConnectTimeout($seconds);

        return $this;
    }

    /**
     * @param string $seconds
     *
     * @return \Pachico\MinFraudChargeback\Http\CurlClient
     */
    public function setTimeout($seconds)
    {
        $this->curl->setTimeout($seconds);

        return $this;
    }

    /**
     * @param string $hostname
     *
     * @return \Pachico\MinFraudChargeback\Http\CurlClient
     */
    public function setHostname($hostname)
    {
        Assert::oneOf($hostname, [
            static::HOSTNAME_DEFAULT,
            static::HOSTNAME_EU_WEST,
            static::HOSTNAME_US_EAST,
            static::HOSTNAME_US_WEST
        ]);

        $this->hostname = $hostname;

        return $this;
    }

    /**
     * @param Chargeback $chargeback
     *
     * @throws ExceptionAbstract
     */
    public function report(Chargeback $chargeback)
    {
        $this->curl->setBasicAuthentication(
            $this->credential->getUser(), $this->credential->getPassword()
        );

        $this->curl->setHeader('Content-Type', 'application/json');

        $this->curl->post($this->hostname, $chargeback->toArray());

        if (!$this->curl->error && 204 === $this->curl->httpStatusCode) {
            return true;
        }

        $exception = $this->isValidMaxMindResponse($this->curl->response)
            ? $this->getMaxMindExceptionByCodeAndMessage(
                $this->curl->response->code,
                $this->curl->response->error
            )
            : new \RuntimeException($this->curl->errorMessage);


        throw $exception;
    }

    /**
     * @param string $content
     *
     * @return boolean
     */
    protected function isValidMaxMindResponse($content)
    {
        $decoded = @json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (!$decoded->code || !$decoded->error) {
            return false;
        }

        return true;
    }

    /**
     * @param string $code
     * @param string $message
     *
     * @return ExceptionAbstract
     */
    protected function getMaxMindExceptionByCodeAndMessage($code, $message)
    {
        switch ($code) {
            case ExceptionAbstract::AUTHORIZATION_INVALID:
                return new Exception\UnauthorizedException($message);
            case ExceptionAbstract::FRAUD_SCORE_INVALID:
                return new Exception\InvalidFraudScoreException($message);
            case ExceptionAbstract::IP_ADDRESS_INVALID:
            case ExceptionAbstract::IP_ADDRESS_RESERVED:
                return new Exception\InvalidIPException($message);
            case ExceptionAbstract::JSON_INVALID:
                return new Exception\InvalidJSONException($message);
            case ExceptionAbstract::LICENSE_KEY_REQUIRED:
                return new Exception\InvalidLicenceException($message);
            case ExceptionAbstract::MAXMIND_ID_INVALID:
                return new Exception\InvalidMaxMindIdException($message);
            case ExceptionAbstract::MINFRAUD_ID_INVALID:
                return new Exception\InvalidMinFraudIdException($message);
            case ExceptionAbstract::PARAMETER_UNKNOWN:
                return new Exception\UnknownParameterException($message);
            case ExceptionAbstract::USER_ID_REQUIRED:
                return new Exception\InvalidUserIdException($message);
            default:
                return new Exception\ServiceUnavailableException();
        }
    }
}