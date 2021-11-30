<?php

namespace RpLaravelBridge;

use Illuminate\Support\Facades\Log;
use RpContracts\Response;

class Logger implements \RpContracts\Logger
{
    const STRATEGY_DISABLED = 0;
    const STRATEGY_LOG_EXCEPTIONS = 1;
    const STRATEGY_DEBUG = 2;
    const STRATEGY_LOG_REQUESTS_WHEN_EXCEPTION = 3; //only when failed
    const REQUEST_AND_RESPONSE_LENGTH_LIMIT = 1000;

    /**
     * @var int
     */
    protected int $strategy;

    /**
     * @var string|null
     */
    protected ?string $channel;

    /**
     * @var array
     */
    protected array $excludeStatusCodes = [];

    /**
     * Logger constructor.
     * @param int $strategy
     * @param array $excludeStatusCodes
     * @param string|null $channel
     */
    public function __construct(int $strategy = self::STRATEGY_LOG_EXCEPTIONS, array $excludeStatusCodes = [], string $channel = null)
    {
        $this->strategy = $strategy;
        $this->channel = $channel;
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
     * @param array|null $errors
     * @return bool
     */
    protected function logRequests(array $errors = null) : bool
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
        Log::channel($this->channel)->error('Exception: '.$exception->getFile().':'.$exception->getLine().' '.$exception->getMessage());
    }

    /**
     * @param Response $response
     */
    protected function logResponse(Response $response)
    {
        $responseContents = $response->getRawContents();
        $maxLength = self::REQUEST_AND_RESPONSE_LENGTH_LIMIT;

        Log::channel($this->channel)->info('Response: '.($maxLength ? substr($responseContents, 0, $maxLength).'...' : $responseContents));
    }

    /**
     * @param array $requestData
     */
    protected function logRequest(array $requestData)
    {
        $contents = json_encode($requestData);
        $maxLength = self::REQUEST_AND_RESPONSE_LENGTH_LIMIT;

        Log::channel($this->channel)->info('Request params: '.($maxLength ? substr($contents, 0, $maxLength).'...' : $contents));
    }

    /**
     * @param Response $result
     * @param array $requestData
     * @return mixed|void
     */
    public function log(Response $result, array $requestData)
    {
        $errors = $result->getErrorsBag();

        if(($this->strategy == self::STRATEGY_LOG_REQUESTS_WHEN_EXCEPTION) and !$result->isSuccess() and $errors)
        {
            Log::channel($this->channel)->error('Request failed. Url: '.($requestData['url'] ?? ''));
            return true;
        }
        elseif($this->logExceptions() and $errors and array_search($result->getStatusCode(), $this->excludeStatusCodes) === false)
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
            if($this->logRequests($errors))
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
