<?php

namespace RangeDownload;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Qcloud\Cos\Service;
use Qcloud\Cos\Signature;
use function Qcloud\Cos\region_map;

class Client
{
    public function __construct(array $cosConfig) {
        $this->rawCosConfig = $cosConfig;
        $this->cosConfig['schema'] = isset($cosConfig['schema']) ? $cosConfig['schema'] : 'http';
        $this->cosConfig['timeout'] = isset($cosConfig['timeout']) ? $cosConfig['timeout'] : 3600;
        $this->cosConfig['connect_timeout'] = isset($cosConfig['connect_timeout']) ? $cosConfig['connect_timeout'] : 3600;
        $this->cosConfig['ip'] = isset($cosConfig['ip']) ? $cosConfig['ip'] : null;
        $this->cosConfig['port'] = isset($cosConfig['port']) ? $cosConfig['port'] : null;
        $this->cosConfig['endpoint'] = isset($cosConfig['endpoint']) ? $cosConfig['endpoint'] : null;
        $this->cosConfig['domain'] = isset($cosConfig['domain']) ? $cosConfig['domain'] : null;
        $this->cosConfig['proxy'] = isset($cosConfig['proxy']) ? $cosConfig['proxy'] : null;
        $this->cosConfig['retry'] = isset($cosConfig['retry']) ? $cosConfig['retry'] : 1;
        $this->cosConfig['userAgent'] = isset($cosConfig['userAgent']) ? $cosConfig['userAgent'] : 'cos-php-sdk-v5.'. \Qcloud\Cos\Client::VERSION;
        $this->cosConfig['pathStyle'] = isset($cosConfig['pathStyle']) ? $cosConfig['pathStyle'] : false;
        $this->cosConfig['signHost'] = isset($cosConfig['signHost']) ? $cosConfig['signHost'] : true;
        $this->cosConfig['allow_redirects'] = isset($cosConfig['allow_redirects']) ? $cosConfig['allow_redirects'] : false;
        $this->cosConfig['allow_accelerate'] = isset($cosConfig['allow_accelerate']) ? $cosConfig['allow_accelerate'] : false;

        // check config
        $this->inputCheck();

        $service = Service::getService();
        $handler = HandlerStack::create();
        $handler->push(Middleware::retry($this->retryDecide(), $this->retryDelay()));
        $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('User-Agent', $this->cosConfig['userAgent']);
        }));
        if ($this->cosConfig['token'] != null) {
            $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
                return $request->withHeader('x-cos-security-token', $this->cosConfig['token']);
            }));
        }
        $handler->push($this::handleErrors());
        $this->signature = new Signature($this->cosConfig['secretId'], $this->cosConfig['secretKey'], $this->cosConfig, $this->cosConfig['token']);
        $area = $this->cosConfig['allow_accelerate'] ? 'accelerate' : $this->cosConfig['region'];
        $this->httpClient = new HttpClient([
            'base_uri' => "{$this->cosConfig['schema']}://cos.{$area}.myqcloud.com/",
            'timeout' => $this->cosConfig['timeout'],
            'handler' => $handler,
            'proxy' => $this->cosConfig['proxy'],
            'allow_redirects' => $this->cosConfig['allow_redirects']
        ]);
        $this->desc = new Description($service);
        $this->api = (array) $this->desc->getOperations();
        parent::__construct($this->httpClient, $this->desc, [$this,
            'commandToRequestTransformer'], [$this, 'responseToResultTransformer'],
            null);
    }
}