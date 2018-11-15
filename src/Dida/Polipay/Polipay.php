<?php
/**
 * Dida Framework  -- A Rapid Development Framework
 * Copyright (c) Zeupin LLC. (http://zeupin.com)
 *
 * Licensed under The MIT License.
 * Redistributions of files must retain the above copyright notice.
 */

namespace Dida\Polipay;

class Polipay
{
    const VERSION = '20181108';

    protected $conf = [];


    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }


    public function required(array $data, array $keys)
    {
        $missing = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }


    public function initiateTransaction(array $data)
    {
        $default = [
            "MerchantHomepageURL" => $this->conf["MerchantHomepageURL"],
            "SuccessURL"          => $this->conf["SuccessURL"],
            "FailureURL"          => $this->conf["FailureURL"],
            "CancellationURL"     => $this->conf["CancellationURL"],
            "NotificationURL"     => $this->conf["NotificationURL"],
        ];

        $required_fields = ["Amount", "CurrencyCode", "MerchantReference"];
        $missing = $this->required($data, $required_fields);
        if ($missing) {
            $missing = implode(",", $missing);
            return [-1, "缺少必填字段{$missing}", null];
        }

        $txdata = array_merge($default, $data);
        $jsondata = json_encode($txdata, JSON_UNESCAPED_UNICODE);

        $url = "https://poliapi.apac.paywithpoli.com/api/v2/Transaction/Initiate";
        $ch = $this->curlInit($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        return $response;
    }


    public function getTransaction($token)
    {
        if (!$token) {
            return false;
        }

        $url = 'https://poliapi.apac.paywithpoli.com/api/v2/Transaction/GetTransaction?token=' . urlencode($token);
        $ch = $this->curlInit($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        return $response;
    }


    protected function curlInit($url = null)
    {
        $header = [];

        $auth = base64_encode($this->conf["MerchantCode"] . ':' . $this->conf["AuthenticationCode"]);
        $header[] = "Authorization: Basic {$auth}";

        $header[] = 'Content-Type: application/json';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/cacert.pem");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        return $ch;
    }
}
