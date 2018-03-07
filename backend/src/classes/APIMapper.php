<?php

namespace VodHost\Backend;

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
     * Calls /api/backend/tagProcessed/$id to inform the frontend that a broadcast
     * has finalized processing.
     *
     * @param int $id - Broadcast ID
     */
    public function tagBroadcastAsProcessed(int $id)
    {
        $api_endpoint = '/api/backend/tagprocessed/';
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
     * @param int $id - Broadcast ID
     * @return json array contained in the response, or null on error
     */
    public function getBroadcastInfo(int $id)
    {
        $api_endpoint = '/api/backend/retrieve/';
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
}