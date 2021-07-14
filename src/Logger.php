<?php

namespace LoggingRequestProvider;

use GuzzleWrapper\LoggerInterface;
use GuzzleWrapper\ResponseWrapper;
use Illuminate\Support\Facades\Log;

class Logger implements LoggerInterface
{
    const STRATEGY_DISABLED = 0;
    const STRATEGY_LOG_EXCEPTIONS = 1;
    const STRATEGY_DEBUG = 2;

    /**
     * @var int
     */
    protected int $strategy;

    public function __construct(int $strategy = self::STRATEGY_LOG_EXCEPTIONS)
    {
        $this->strategy = $strategy;
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
        return in_array($this->strategy, [self::STRATEGY_DEBUG]);
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
        Log::error('Exception: '.$exception->getMessage());
    }

    /**
     * @param ResponseWrapper $response
     */
    protected function logResponse(ResponseWrapper $response)
    {
        Log::info('Response: '.$response->getRawContents());
    }

    /**
     * @param array $requestData
     */
    protected function logRequest(array $requestData)
    {
        Log::info('Request params: '.json_encode($requestData));
    }

    /**
     * @param ResponseWrapper $result
     * @param array $requestData
     * @return mixed|void
     */
    public function log(ResponseWrapper $result, array $requestData)
    {
        if($this->logRequests())
        {
            $this->logRequest($requestData);
        }
        if($this->logResponses())
        {
            $this->logResponse($result);
        }
        if($this->logExceptions() and $errors = $result->getErrorsBag())
        {
            foreach($errors as $error)
            {
                $this->logException($error);
            }
        }
    }
}
