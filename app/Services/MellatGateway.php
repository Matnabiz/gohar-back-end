<?php
namespace App\Services;

use SoapClient;
use SoapFault;
use Exception;

class MellatGateway
{
    protected $wsdl;
    protected $terminalId;
    protected $user;
    protected $pass;
    protected $client = null;

    public function __construct()
    {
        $this->wsdl = config('services.mellat.wsdl');
        $this->terminalId = config('services.mellat.terminal_id');
        $this->user = config('services.mellat.user');
        $this->pass = config('services.mellat.password');
    }

    protected function client()
    {
        if ($this->client) return $this->client;
        $opts = [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $this->client = new SoapClient($this->wsdl, $opts);
        return $this->client;
    }

    protected function parseResponse($res)
    {
        // The gateway usually returns a string like "0,REFID" or a raw string
        if (is_object($res) && isset($res->return)) $raw = (string)$res->return;
        elseif (is_string($res)) $raw = $res;
        else $raw = json_encode($res);

        $raw = trim($raw);
        if (strpos($raw, ',') !== false) {
            [$code, $rest] = explode(',', $raw, 2);
            return ['code' => trim($code), 'ref' => trim($rest), 'raw' => $raw];
        }
        return ['code' => $raw, 'ref' => null, 'raw' => $raw];
    }

    public function bpPayRequest($merchantOrderId, $amount, $callbackUrl, $additionalData = '')
    {
        $params = [
            'terminalId' => (int)$this->terminalId,
            'userName' => $this->user,
            'userPassword' => $this->pass,
            'orderId' => (int)$merchantOrderId,
            'amount' => (int)$amount,
            'localDate' => now()->format('Ymd'),
            'localTime' => now()->format('His'),
            'additionalData' => $additionalData,
            'callBackUrl' => $callbackUrl,
            'payerId' => 0,
        ];

        try {
            $res = $this->client()->__soapCall('bpPayRequest', [$params]);
            return $this->parseResponse($res);
        } catch (SoapFault $e) {
            return ['error' => 'soap_fault', 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['error' => 'exception', 'message' => $e->getMessage()];
        }
    }

    public function bpVerifyRequest($verifyOrderId, $saleOrderId, $saleReferenceId)
    {
        $params = [
            'terminalId' => (int)$this->terminalId,
            'userName' => $this->user,
            'userPassword' => $this->pass,
            'orderId' => (int)$verifyOrderId,
            'saleOrderId' => (int)$saleOrderId,
            'saleReferenceId' => (int)$saleReferenceId,
        ];

        try {
            $res = $this->client()->__soapCall('bpVerifyRequest', [$params]);
            $parsed = $this->parseResponse($res);
            return $parsed; // parsed['code'] contains verify code
        } catch (SoapFault $e) {
            return ['error' => 'soap_fault', 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['error' => 'exception', 'message' => $e->getMessage()];
        }
    }

    public function bpSettleRequest($orderId, $saleOrderId, $saleReferenceId)
    {
        $params = [
            'terminalId' => (int)$this->terminalId,
            'userName' => $this->user,
            'userPassword' => $this->pass,
            'orderId' => (int)$orderId,
            'saleOrderId' => (int)$saleOrderId,
            'saleReferenceId' => (int)$saleReferenceId,
        ];

        try {
            $res = $this->client()->__soapCall('bpSettleRequest', [$params]);
            return $this->parseResponse($res);
        } catch (SoapFault $e) {
            return ['error' => 'soap_fault', 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['error' => 'exception', 'message' => $e->getMessage()];
        }
    }

    public function bpInquiryRequest($orderId, $saleOrderId, $saleReferenceId)
    {
        $params = [
            'terminalId' => (int)$this->terminalId,
            'userName' => $this->user,
            'userPassword' => $this->pass,
            'orderId' => (int)$orderId,
            'saleOrderId' => (int)$saleOrderId,
            'saleReferenceId' => (int)$saleReferenceId,
        ];

        try {
            $res = $this->client()->__soapCall('bpInquiryRequest', [$params]);
            return $this->parseResponse($res);
        } catch (SoapFault $e) {
            return ['error' => 'soap_fault', 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['error' => 'exception', 'message' => $e->getMessage()];
        }
    }
}
