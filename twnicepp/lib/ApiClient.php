<?php

namespace WHMCS\Module\Registrar\Twnicepp;

/**
 * Sample Registrar Module Simple API Client.
 *
 * A simple API Client for communicating with an external API endpoint.
 */
class ApiClient
{
    const domainCreateUrl = '';
    const domainUpdateUrl = '';
    const domainInfoUrl = '';
	const domainHostsUrl = '';
    const domainDeleteUrl = '';
    const domainRenewUrl = '';
    const domainTransferQueryUrl = '';
    const domainTransferRequestUrl = '';
    const domainTransferApproveUrl = '';
    const contactCreateUrl = '';
    const contactInfoUrl = '';
    const contactUpdateUrl = '';
    const hostCreateUrl = '';
    const hostCheckUrl = '';
    const hostInfoUrl = '';
    const hostUpdateUrl = '';
    const hostDeleteUrl = '';

    //Dev
    const domainCreateUrlDev = 'http://dev.dcitn.com/api/domains';
    const domainUpdateUrlDev = 'http://dev.dcitn.com/api/domains';
    const domainInfoUrlDev = 'http://dev.dcitn.com/api/domains/show';
	const domainHostsUrlDev = 'http://dev.dcitn.com/api/domains/hosts';
    const domainDeleteUrlDev = 'http://dev.dcitn.com/api/domains';
    const domainRenewUrlDev = 'http://dev.dcitn.com/api/domains/renew';
    const domainTransferQueryUrlDev = 'http://dev.dcitn.com/api/domains/transfer';
    const domainTransferRequestUrlDev = 'http://dev.dcitn.com/api/domains/transfer-request';
    const domainTransferApproveUrlDev = 'http://dev.dcitn.com/api/domains/transfer-approve';
    const contactCreateUrlDev = 'http://dev.dcitn.com/api/contacts';
    const contactInfoUrlDev = 'http://dev.dcitn.com/api/contacts/show';
    const contactUpdateUrlDev = 'http://dev.dcitn.com/api/contacts';
    const hostCreateUrlDev = 'http://dev.dcitn.com/api/hosts';
    const hostCheckUrlDev = 'http://dev.dcitn.com/api/hosts';
    const hostInfoUrlDev = 'http://dev.dcitn.com/api/hosts/show';
    const hostUpdateUrlDev = 'http://dev.dcitn.com/api/hosts';
    const hostDeleteUrlDev = 'http://dev.dcitn.com/api/hosts';

    protected $results = [];
    protected $testMode;

    public function __construct($testMode = null)
    {
        $this->testMode = $testMode;
    }

    /**
     * Make external API call to registrar API.
     *
     * @param string $action
     * @param array $postfields
     *
     * @throws \Exception Connection error
     * @throws \Exception Bad API response
     *
     * @return array
     */
    public function call($action, $postfields, $method = 'POST')
    {
        $apiUrl = $this->setUrl($action);
        if ($method == 'GET') $apiUrl = $apiUrl.$postfields;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
        }
    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        /*curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);*/
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Connection Error: ' . curl_errno($ch) . ' - ' . curl_error($ch));
        }
        curl_close($ch);

        $this->results = $this->processResponse($response);

        logModuleCall(
            'TWNIC EPP',
            $action,
            $postfields,
            $response,
            $this->results,
            [
                //$postfields['username'], // Mask username & password in request/response data
                //$postfields['password'],
                $postfields['api_token']
            ]
        );

        if ($this->results === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Bad response received from API');
        }

        return $this->results;
    }

    /**
     * Process API response.
     *
     * @param string $response
     *
     * @return array
     */
    public function processResponse($response)
    {
        return json_decode($response, true);
    }

    /**
     * Get from response results.
     *
     * @param string $key
     *
     * @return string
     */
    public function getFromResponse($key)
    {
        return isset($this->results[$key]) ? $this->results[$key] : '';
    }

    /**
     * 設定網址
     *
     * @param [type] $action
     * @return void
     */
    public function setUrl($action)
    {
        if ($this->testMode) return $this->dev($action);

        switch ($action) {
            case 'domainCreate':
                return self::domainCreateUrl;
                break;
            case 'domainUpdate':
                return self::domainUpdateUrl;
                break;
            case 'domainInfo':
                return self::domainInfoUrl;
                break;
			case 'domainHosts':
                return self::domainHostsUrl;
                break;
            case 'domainDelete':
                return self::domainDeleteUrl;
                break;
            case 'domainRenew':
                return self::domainRenewUrl;
                break;
            case 'domainTransferQuery':
                return self::domainTransferQueryUrl;
                break;
            case 'domainTransferRequest':
                return self::domainTransferRequestUrl;
                break;
            case 'domainTransferApprove':
                return self::domainTransferApproveUrl;
                break;
            case 'contactCreate':
                return self::contactCreateUrl;
                break;
            case 'contactInfo':
                return self::contactInfoUrl;
                break;
            case 'contactUpdate':
                return self::contactUpdateUrl;
                break;
            case 'hostCreate':
                return self::hostCreateUrl;
                break;
            case 'hostCheck':
                return self::hostCheckUrl;
                break;
            case 'hostInfo':
                return self::hostInfoUrl;
                break;
            case 'hostUpdate':
                return self::hostUpdateUrl;
                break;
            case 'hostDelete':
                return self::hostDeleteUrl;
                break;
        }
    }

    /**
     * 開發模式
     *
     * @param [type] $action
     * @return void
     */
    protected function dev($action)
    {
        switch ($action) {
            case 'domainCreate':
                return self::domainCreateUrlDev;
                break;
            case 'domainUpdate':
                return self::domainUpdateUrlDev;
                break;
            case 'domainInfo':
                return self::domainInfoUrlDev;
                break;
			case 'domainHosts':
                return self::domainHostsUrlDev;
                break;
            case 'domainDelete':
                return self::domainDeleteUrlDev;
                break;
            case 'domainRenew':
                return self::domainRenewUrlDev;
                break;
            case 'domainTransferQuery':
                return self::domainTransferQueryUrlDev;
                break;
            case 'domainTransferRequest':
                return self::domainTransferRequestUrlDev;
                break;
            case 'domainTransferApprove':
                return self::domainTransferApproveUrlDev;
                break;
            case 'contactCreate':
                return self::contactCreateUrlDev;
                break;
            case 'contactInfo':
                return self::contactInfoUrlDev;
                break;
            case 'contactUpdate':
                return self::contactUpdateUrlDev;
                break;
            case 'hostCreate':
                return self::hostCreateUrlDev;
                break;
            case 'hostCheck':
                return self::hostCheckUrlDev;
                break;
            case 'hostInfo':
                return self::hostInfoUrlDev;
                break;
            case 'hostUpdate':
                return self::hostUpdateUrlDev;
                break;
            case 'hostDelete':
                return self::hostDeleteUrlDev;
                break;
        }
    }
}
