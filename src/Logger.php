<?php

namespace RpLaravelBridge;

use Illuminate\Support\Facades\Log;
use RpContracts\Response;

class Logger implements \RpContracts\Logger
{
    const STRATEGY_DISABLED = 0;
    const STRATEGY_LOG_EXCEPTIONS = 1;
    const STRATEGY_DEBUG = 2;
    const STRATEGY_LOG_REQUESTS_WHEN_EXCEPTION = 3;
    const REQUEST_AND_RESPONSE_LENGTH_LIMIT = 1000;

    /**
     * @var int
     */
    protected int $strategy;

    /**
     * @var array
     */
    protected array $excludeStatusCodes = [];

    /**
     * Logger constructor.
     * @param int $strategy
     * @param array $excludeStatusCodes
     */
    public function __construct(int $strategy = self::STRATEGY_LOG_EXCEPTIONS, array $excludeStatusCodes = [])
    {
        $this->strategy = $strategy;
        $this->excludeStatusCodes = $excludeStatusCodes;
    }

    /**
     * @return bool
     */
    protected function logExceptions() : bool
    {
        return in_array($this->strategy, [self::STRATEGY_LOG_EXCEPTIONS, self::STRATEGY_DEBUG]);
    }

    /**
     * @return bool
     */
    protected function logRequests() : bool
    {
        return in_array($this->strategy, [self::STRATEGY_DEBUG, self::STRATEGY_LOG_REQUESTS_WHEN_EXCEPTION]);
    }

    /**
     * @return bool
     */
    protected function logResponses() : bool
    {
        return in_array($this->strategy, [self::STRATEGY_DEBUG]);
    }


    /**
     * @param \Throwable $exception
     */
    protected function logException(\Throwable $exception)
    {
        Log::error('Exception: '.$exception->getFile().':'.$exception->getLine().' '.$exception->getMessage());
    }

    /**
     * @param Response $response
     */
    protected function logResponse(Response $response)
    {
        $responseContents = $response->getRawContents();
        $maxLength = self::REQUEST_AND_RESPONSE_LENGTH_LIMIT;

        Log::info('Response: '.($maxLength ? substr($responseContents, 0, $maxLength).'...' : $responseContents));
    }

    /**
     * @param array $requestData
     */
    protected function logRequest(array $requestData)
    {
        $contents = json_encode($requestData);
        $maxLength = self::REQUEST_AND_RESPONSE_LENGTH_LIMIT;

        Log::info('Request params: '.($maxLength ? substr($contents, 0, $maxLength).'...' : $contents));
    }

    /**
     * @param Response $result
     * @param array $requestData
     * @return mixed|void
     */
    public function log(Response $result, array $requestData)
    {
        $errors = $result->getErrorsBag();

        if($errors and $this->strategy == self::STRATEGY_LOG_REQUESTS_WHEN_EXCEPTION)
        {
            Log::error('Request failed');
        }

        if($this->logExceptions() and $errors and array_search($result->getStatusCode(), $this->excludeStatusCodes) === false)
        {
            foreach($errors as $error)
            {
                $this->logException($error);
            }
            $this->logRequest($requestData);
            $this->logResponse($result);
        }
        else
        {
            if($this->logRequests())
            {
                $this->logRequest($requestData);
            }
            if($this->logResponses())
            {
                $this->logResponse($result);
            }
        }
    }
}
