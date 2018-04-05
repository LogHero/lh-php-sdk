<?php

interface APIAccess {
    public function submitLogPackage($payloadAsJson);
}

abstract class APIAccessBase implements APIAccess {
    protected $apiKey;
    protected $apiLogPackageEndpoint;

    public function __construct($apiKey, $apiLogPackageEndpoint) {
        $this->apiKey = $apiKey;
        $this->apiLogPackageEndpoint = $apiLogPackageEndpoint;
    }

}

# TODO Class is not tested
class APIAccessCurl extends APIAccessBase{

    public function submitLogPackage($payloadAsJson) {
        $curl = curl_init($this->apiLogPackageEndpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                'Content-type: application/json',
                'Authorization: '.$this->apiKey
            )
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payloadAsJson);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ( $status >= 300 ) {
            die('Error: call to URL $url failed with status $status, response $response, curl_error ' . curl_error($curl) . ', curl_errno ' . curl_errno($curl));
        }
        curl_close($curl);
    }

}