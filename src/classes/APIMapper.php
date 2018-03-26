<?php

namespace VodHost;

class APIMapper
{
    protected $base_url;
    protected $api_key;

    public function __construct($config)
    {
        $this->base_url = $config['server_domain'];
        $this->api_key = $config['api_key'];
    }

    /* API Functions Below */

    /**
     * Calls /api/backend/broadcast/removeSource/$id to inform the frontend that a broadcast
     * has finalized processing.
     *
     * @param string $id - Broadcast ID
     */
    public function removeSource(string $id)
    {
        $api_endpoint = '/api/backend/broadcast/removesource/';
        $url = $this->base_url . $api_endpoint . $id;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array('X-API-KEY: ' . $this->api_key)
        ));

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    /**
     * Calls /api/backend/retrieve/$id and returns the json result
     *
     * @param string $id - Broadcast ID
     * @return json array contained in the response, or null on error
     */
    public function getBroadcastInfo(string $id)
    {
        $api_endpoint = '/api/backend/broadcast/retrieve/';
        $url = $this->base_url . $api_endpoint . $id;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array('X-API-KEY: ' . $this->api_key)
        ));

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    /**
     * Calls /api/broadcast/modify
     *
     * @param string $id - Broadcast ID
     * @param string $json json encoded data
     */
    public function modifyBroadcast(string $id, string $json)
    {
        $api_endpoint = '/api/backend/broadcast/modify/';
        $url = $this->base_url . $api_endpoint . $id;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                                    'X-API-KEY: ' . $this->api_key,
                                    'Content-Type: application/json'),
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true
        ));

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}
