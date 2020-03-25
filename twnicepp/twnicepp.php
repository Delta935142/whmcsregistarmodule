<?php
/**
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/domain-registrars/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Module\Registrar\Twnicepp\ApiClient;

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Define module related metadata
 *
 * Provide some module information including the display name and API Version to
 * determine the method of decoding the input values.
 *
 * @return array
 */
function twnicepp_MetaData()
{
    return array(
        'DisplayName' => 'Sample Registrar Module for WHMCS',
        'APIVersion' => '1.1',
    );
}

/**
 * Define registrar configuration options.
 *
 * The values you return here define what configuration options
 * we store for the module. These values are made available to
 * each module function.
 *
 * You can store an unlimited number of configuration settings.
 * The following field types are supported:
 *  * Text
 *  * Password
 *  * Yes/No Checkboxes
 *  * Dropdown Menus
 *  * Radio Buttons
 *  * Text Areas
 *
 * @return array
 */
function twnicepp_getConfigArray()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'TWNIC EPP',
        ),
        'APIToken' => array(
            'Type' => 'text',
            'Size' => '25',
            'Default' => ''
        ),
        'TestMode' => array(
            'Type' => 'yesno',
            'Description' => 'Tick to enable',
            'Default' => ''
        ),
    );
}

/**
 * Register a domain.
 *
 * Attempt to register a domain with the domain registrar.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain registration order
 * * When a pending domain registration order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_RegisterDomain($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];
    $authCode = uniqid();

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];
    $registrationPeriod = $params['regperiod'];
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
    // Nameservers
    $nameservers = [];
    if ($params['ns1']) array_push($nameservers, $params['ns1']);
    if ($params['ns2']) array_push($nameservers, $params['ns2']);
    if ($params['ns3']) array_push($nameservers, $params['ns3']);
    if ($params['ns4']) array_push($nameservers, $params['ns4']);
    if ($params['ns5']) array_push($nameservers, $params['ns5']);

    // registrant information
    $registarArr = [
        'name' => $params["firstname"].$params["lastname"],
        'email' => $params["email"],
        'phone' => '+'.$params["phonecc"].'.'.$params["phonenumber"],
        'organization' => $params["companyname"],
        'address1' => $params["address1"],
        'address2' => $params["address2"],
        'zipcode' => $params["postcode"],
        'city' => $params["city"],
        'country' => $params["countrycode"],
        'province' => $params["state"],
        'fax' => $params["fax"],
        'auth_code' => $authCode,
    ];

    if (isset($params["customfields"]) && count($params["customfields"]) > 0) {
        $registarArr['c_name'] = $params["customfields"][0]['value'];
        $registarArr['app_id'] = $params["customfields"][1]['value'];
        $registarArr['c_organization'] = $params["customfields"][2]['value'];
        $registarArr['cmp_id'] = $params["customfields"][3]['value'];
        $registarArr['c_province'] = $params["customfields"][4]['value'];
        $registarArr['c_city'] = $params["customfields"][5]['value'];
        $registarArr['c_address'] = $params["customfields"][6]['value'];
    }

    $registrantId = createContact($userToken, $testMode, $registarArr);

    // Admin contact information
    $adminId = createContact($userToken, $testMode, [
        'name' => $params["adminfirstname"].$params["adminlastname"],
        'email' => $params["adminemail"],
        'phone' => '+'.$params["adminphonecc"].'.'.$params["adminphonenumber"],
        'organization' => $params["admincompanyname"],
        'address1' => $params["adminaddress1"],
        'address2' => $params["adminaddress2"],
        'zipcode' => $params["adminpostcode"],
        'city' => $params["admincity"],
        'country' => $params["admincountry"],
        'province' => $params["adminstate"],
        'fax' => $params["adminfax"],
        'auth_code' => $authCode,
    ]);

    // Build post data
    $postfields = [
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        'years' => $registrationPeriod,
        'nameservers' => $nameservers,
        'auth_code' => $authCode,
        'lang' => (strpos($params['tld'], '台灣')) ? 'ZH' : 'EN',
        'registrant' => $registrantId,
    ];

    if ($adminId) $postfields['admincontact'] = $adminId;

    if (!is_array($registrantId) && $registrantId) {
        $domainCreateUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains' : 'http://dcitn.com/api/domains';
        $domainUpdateUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains' : 'http://dcitn.com/api/domains';
        try {
            $api = new ApiClient();
            $api->setUrl($domainCreateUrl);
            $response = $api->call('Create Domain', $postfields);

            $result = [];
            if ($response['result']) {
                $putfields = [
                    'api_token' => $userToken,
                    'domain' => "{$sld}.{$tld}",
                    '_method' => 'PUT',
                    'clientDeleteProhibited' => 'true',
                    'clientTransferProhibited' => 'true',
                ];
                if ($adminId) $putfields['admincontact'] = $adminId;

                $api->setUrl($domainUpdateUrl);
                $api->call('Update Domain', $putfields);

                $result['success'] = true;
            } else {
                $result['error'] = "{$sld}.{$tld} 註冊失敗";
            }

            return $result;
        } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
        }
    }
}

/**
 * Create Contact
 *
 * @param [type] $userToken
 * @param [type] $testMode
 * @param array $info
 * @return void
 */
function createContact($userToken, $testMode, $info = [])
{
    $contactCreateUrl = ($testMode) ? 'http://dev.dcitn.com/api/contacts' : 'http://dcitn.com/api/contacts';
    $info['api_token'] = $userToken;

    try {
        $api = new ApiClient();
        $api->setUrl($contactCreateUrl);
        $response = $api->call('Create Contact', $info);

        return $response['result'] ? $response['message']['contactId'] : null;
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Initiate domain transfer.
 *
 * Attempt to create a domain transfer request for a given domain.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain transfer order
 * * When a pending domain transfer order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_TransferDomain($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];
    $eppCode = $params['eppcode'];

    $nameservers = [];
    if ($params['ns1']) array_push($nameservers, $params['ns1']);
    if ($params['ns2']) array_push($nameservers, $params['ns2']);
    if ($params['ns3']) array_push($nameservers, $params['ns3']);
    if ($params['ns4']) array_push($nameservers, $params['ns4']);
    if ($params['ns5']) array_push($nameservers, $params['ns5']);

    $postfields = [
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        'period' => $registrationPeriod,
        'auth_code' => $eppCode,
        'nameservers' => $nameservers,
    ];

    $domainTransferRequestUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains/transfer-request' : 'http://dcitn.com/api/domains/transfer-request';

    try {
        $api = new ApiClient();
        $api->setUrl($domainTransferRequestUrl);
        $response = $api->call('Domain Transfer Request', $postfields);

        return $response['result'] ? "{$sld}.{$tld} 轉入請求已送出" : "{$sld}.{$tld} 轉入請求失敗";
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Renew a domain.
 *
 * Attempt to renew/extend a domain for a given number of years.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain renewal order
 * * When a pending domain renewal order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_RenewDomain($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];

    // Build post data.
    $postfields = array(
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        'period' => $registrationPeriod,
    );

    $domainRenewUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains/renew' : 'http://dcitn.com/api/domains/renew';
    
    try {
        $api = new ApiClient();
        $api->setUrl($domainRenewUrl);
        $response = $api->call('Domain Renew', $postfields);

        $result = [];
        if ($response['result']) {
            $result['success'] = true;
        } else {
            $result['error'] = "{$sld}.{$tld} 續用失敗";
        }

        return $result;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Fetch current nameservers.
 *
 * This function should return an array of nameservers for a given domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_GetNameservers($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    $result = [];
    if (!array_key_exists('error', $response)) {
        foreach ($response['nameservers'] as $nameserver) {
            $result[] = $nameserver;
        }
    } else {
        $result['error'] = "{$sld}.{$tld} 查詢 Nameserver 失敗";
    }

    return $result;
}

/**
 * Save nameserver changes.
 *
 * This function should submit a change of nameservers request to the
 * domain registrar.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_SaveNameservers($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // submitted nameserver values
    $nameservers = [];
    if ($params['ns1']) array_push($nameservers, $params['ns1']);
    if ($params['ns2']) array_push($nameservers, $params['ns2']);
    if ($params['ns3']) array_push($nameservers, $params['ns3']);
    if ($params['ns4']) array_push($nameservers, $params['ns4']);
    if ($params['ns5']) array_push($nameservers, $params['ns5']);

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    if (!array_key_exists('error', $response)) {
        // Build post data
        $putfields = [
            'api_token' => $userToken,
            'domain' => "{$sld}.{$tld}",
            '_method' => 'PUT',
            'nameservers' => $nameservers,
            'registrant' => $response['registrant'],
            'auth_code' => $response['authCode']
        ];

        $domainUpdateUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains' : 'http://dcitn.com/api/domains';
        try {
            $api = new ApiClient();
            $api->setUrl($domainUpdateUrl);
            $response = $api->call('Domain SetNameservers', $putfields);

            return $response['result'] ? 'Nameserver 更新完成' : 'Nameserver 更新失敗';
        } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
        }
    }
}

/**
 * Domain Info
 *
 * @param [type] $userToken
 * @param [type] $testMode
 * @param [type] $domain
 * @return void
 */
function getDomainInfo($userToken, $testMode, $domain)
{
    $getfields = '?api_token='.$userToken.'&domain='.$domain;
    $domainInfoUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains/show'.$getfields : 'http://dcitn.com/api/domains/show'.$getfields;

    try {
        $api = new ApiClient();
        $api->setUrl($domainInfoUrl);
        $response = $api->call('Domain Info', [], 'GET');

        return $response['result'] ? $response['message'] : null;
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Get the current WHOIS Contact Information.
 *
 * Should return a multi-level array of the contacts and name/address
 * fields that be modified.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_GetContactDetails($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    if (!array_key_exists('error', $response)) {
        $registrant = getContactInfo($userToken, $testMode, $response['registrant']);
        $registrantArr = [
            'Name' => $registrant['post_info'][0]['name'],
            'Company Name' => $registrant['post_info'][0]['organization'],
            'Email Address' => $registrant['email'],
            'Address 1' => $registrant['post_info'][0]['address'][0],
            'Address 2' => $registrant['post_info'][0]['address'][1],
            'City' => $registrant['post_info'][0]['city'],
            'State' => $registrant['post_info'][0]['province'],
            'Postcode' => $registrant['post_info'][0]['zipcode'],
            'Country' => $registrant['post_info'][0]['country'],
            'Phone Number' => $registrant['phone'],
            'Fax Number' => $registrant['fax'],
        ];

        if (array_key_exists(1, $registrant['post_info'])) {
            $registrantArr['Chinese Name'] = $registrant['post_info'][1]['name'];
            $registrantArr['TaiwanID'] = $registrant['app_id'];
            $registrantArr['Chinese Company Name'] = $registrant['post_info'][1]['organization'];
            $registrantArr['CompanyID'] = $registrant['cmp_id'];
            $registrantArr['Chinese State'] = $registrant['post_info'][1]['province'];
            $registrantArr['Chinese City'] = $registrant['post_info'][1]['city'];
            $registrantArr['Chinese Address'] = $registrant['post_info'][1]['address'][0];
        }
        $registrantArr['Auth Code'] = $registrant['auth_code'];

        $adminArr = [];
        $techArr = [];
        $billingArr = [];
        $admin = null;
        $tech = null;
        $billing = null;
        foreach ($response['contacts'] as $contact) {
            if (strstr($contact, 'admin')) $admin = getContactInfo($userToken, $testMode, explode(':', $contact)[1]);
            if (strstr($contact, 'tech')) $tech = getContactInfo($userToken, $testMode, explode(':', $contact)[1]);
            if (strstr($contact, 'billing')) $billing = getContactInfo($userToken, $testMode, explode(':', $contact)[1]);
        }

        if (is_array($admin)) {
            $adminArr = [
                'Name' => $admin['post_info'][0]['name'],
                'Company Name' => $admin['post_info'][0]['organization'],
                'Email Address' => $admin['email'],
                'Address 1' => $admin['post_info'][0]['address'][0],
                'Address 2' => $admin['post_info'][0]['address'][1],
                'City' => $admin['post_info'][0]['city'],
                'State' => $admin['post_info'][0]['province'],
                'Postcode' => $admin['post_info'][0]['zipcode'],
                'Country' => $admin['post_info'][0]['country'],
                'Phone Number' => $admin['phone'],
                'Fax Number' => $admin['fax'],
            ];

            if (array_key_exists(1, $admin['post_info'])) {
                $adminArr['Chinese Name'] = $admin['post_info'][1]['name'];
                $adminArr['TaiwanID'] = $admin['app_id'];
                $adminArr['Chinese Company Name'] = $admin['post_info'][1]['organization'];
                $adminArr['CompanyID'] = $admin['cmp_id'];
                $adminArr['Chinese State'] = $admin['post_info'][1]['province'];
                $adminArr['Chinese City'] = $admin['post_info'][1]['city'];
                $adminArr['Chinese Address'] = $admin['post_info'][1]['address'][0];
            }
            $adminArr['Auth Code'] = $admin['auth_code'];
        }

        if (is_array($tech)) {
            $techArr = [
                'Name' => $tech['post_info'][0]['name'],
                'Company Name' => $tech['post_info'][0]['organization'],
                'Email Address' => $tech['email'],
                'Address 1' => $tech['post_info'][0]['address'][0],
                'Address 2' => $tech['post_info'][0]['address'][1],
                'City' => $tech['post_info'][0]['city'],
                'State' => $tech['post_info'][0]['province'],
                'Postcode' => $tech['post_info'][0]['zipcode'],
                'Country' => $tech['post_info'][0]['country'],
                'Phone Number' => $tech['phone'],
                'Fax Number' => $tech['fax'],
            ];

            if (array_key_exists(1, $tech['post_info'])) {
                $techArr['Chinese Name'] = $tech['post_info'][1]['name'];
                $techArr['TaiwanID'] = $tech['app_id'];
                $techArr['Chinese Company Name'] = $tech['post_info'][1]['organization'];
                $techArr['CompanyID'] = $tech['cmp_id'];
                $techArr['Chinese State'] = $tech['post_info'][1]['province'];
                $techArr['Chinese City'] = $tech['post_info'][1]['city'];
                $techArr['Chinese Address'] = $tech['post_info'][1]['address'][0];
            }
            $techArr['Auth Code'] = $tech['auth_code'];
        }

        if (is_array($billing)) {
            $billingArr = [
                'Name' => $billing['post_info'][0]['name'],
                'Company Name' => $billing['post_info'][0]['organization'],
                'Email Address' => $billing['email'],
                'Address 1' => $billing['post_info'][0]['address'][0],
                'Address 2' => $billing['post_info'][0]['address'][1],
                'City' => $billing['post_info'][0]['city'],
                'State' => $billing['post_info'][0]['province'],
                'Postcode' => $billing['post_info'][0]['zipcode'],
                'Country' => $billing['post_info'][0]['country'],
                'Phone Number' => $billing['phone'],
                'Fax Number' => $billing['fax'],
            ];

            if (array_key_exists(1, $billing['post_info'])) {
                $billingArr['Chinese Name'] = $billing['post_info'][1]['name'];
                $billingArr['TaiwanID'] = $billing['app_id'];
                $billingArr['Chinese Company Name'] = $billing['post_info'][1]['organization'];
                $billingArr['CompanyID'] = $billing['cmp_id'];
                $billingArr['Chinese State'] = $billing['post_info'][1]['province'];
                $billingArr['Chinese City'] = $billing['post_info'][1]['city'];
                $billingArr['Chinese Address'] = $billing['post_info'][1]['address'][0];
            }
            $billingArr['Auth Code'] = $billing['auth_code'];
        }

        return [
            'Registrant' => $registrantArr,
            'Technical' => $techArr,
            'Billing' => $billingArr,
            'Admin' => $adminArr,
        ];
    }
}

/**
 * Contact Info
 *
 * @param [type] $userToken
 * @param [type] $testMode
 * @param [type] $contactId
 * @return void
 */
function getContactInfo($userToken, $testMode, $contactId)
{
    $getfields = '?api_token='.$userToken.'&contact_id='.$contactId;
    $contactInfoUrl = ($testMode) ? 'http://dev.dcitn.com/api/contacts/show'.$getfields : 'http://dcitn.com/api/contacts/show'.$getfields;

    try {
        $api = new ApiClient();
        $api->setUrl($contactInfoUrl);
        $response = $api->call('Contact Info', [], 'GET');

        return $response['result'] ? $response['message'] : null;
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * Update the WHOIS Contact Information for a given domain.
 *
 * Called when a change of WHOIS Information is requested within WHMCS.
 * Receives an array matching the format provided via the `GetContactDetails`
 * method with the values from the users input.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_SaveContactDetails($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // whois information
    $contactDetails = $params['contactdetails'];

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    $admin = null;
    $tech = null;
    $billing = null;
    foreach ($response['contacts'] as $contact) {
        if (strstr($contact, 'admin')) $admin = explode(':', $contact)[1];
        if (strstr($contact, 'tech')) $tech = explode(':', $contact)[1];
        if (strstr($contact, 'billing')) $billing = explode(':', $contact)[1];
    }

    $types = [
        'registrant' => 'Registrant', 
        'admin' => 'Admin',
        'tech' => 'Technical',
        'billing' => 'Billing'
    ];

    $contacts = [
        'registrant' => $response['registrant'],
        'admin' => $admin,
        'tech' => $tech,
        'billing' => $billing,
    ];

    $contactUpdateUrl = ($testMode) ? 'http://dev.dcitn.com/api/contacts' : 'http://dcitn.com/api/contacts';

    $api = new ApiClient();
    $api->setUrl($contactUpdateUrl);

    if (!array_key_exists('error', $response)) {
        foreach ($types as $key => $val) {
            if ($contacts[$key]) {
                $info = [
                    'api_token' => $userToken,
                    '_method' => 'PUT',
                    'contact_id' => $contacts[$key],
                    'email' => $contactDetails[$val]['Email Address'],
                    'phone' => $contactDetails[$val]['Phone Number'],
                    'name' => $contactDetails[$val]['Name'],
                    'organization' => $contactDetails[$val]['Company Name'],
                    'address1' => $contactDetails[$val]['Address 1'],
                    'address2' => $contactDetails[$val]['Address 2'],
                    'zipcode' => $contactDetails[$val]['Postcode'],
                    'city' => $contactDetails[$val]['City'], 
                    'country' => $contactDetails[$val]['Country'], 
                    'province' => $contactDetails[$val]['State'],
                    'fax' => $contactDetails[$val]['Fax Number'],
                    'auth_code' => $contactDetails[$val]['Auth Code'],
                ];
        
                if (array_key_exists('Chinese Name', $contactDetails[$val])) {
                    $info['c_name'] = $contactDetails[$val]['Chinese Name'];
                    $info['c_organization'] = $contactDetails[$val]['Chinese Company Name'];
                    $info['c_address'] = $contactDetails[$val]['Chinese Address'];
                    $info['c_city'] = $contactDetails[$val]['Chinese City'];
                    $info['c_province'] = $contactDetails[$val]['Chinese State'];
                    $info['app_id'] = $contactDetails[$val]['TaiwanID'];
                    $info['cmp_id'] = $contactDetails[$val]['CompanyID'];
                }
            }

            try {
                $response = $api->call('Update Whois Information', $info);
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
        }

        return [
            'success' => true
        ];
    }

    return [
        'error' => '網域不存在'
    ];
}

/**
 * Check Domain Availability.
 *
 * Determine if a domain or group of domains are available for
 * registration or transfer.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain availability check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */
function twnicepp_CheckAvailability($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // availability check parameters
    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $isIdnDomain = (bool) $params['isIdnDomain'];
    $premiumEnabled = (bool) $params['premiumEnabled'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'searchTerm' => $searchTerm,
        'tldsToSearch' => $tldsToInclude,
        'includePremiumDomains' => $premiumEnabled,
    );

    try {
        $api = new ApiClient();
        $api->call('CheckAvailability', $postfields);

        $results = new ResultsList();
        foreach ($api->getFromResponse('domains') as $domain) {

            // Instantiate a new domain search result object
            $searchResult = new SearchResult($domain['sld'], $domain['tld']);

            // Determine the appropriate status to return
            if ($domain['status'] == 'available') {
                $status = SearchResult::STATUS_NOT_REGISTERED;
            } elseif ($domain['statis'] == 'registered') {
                $status = SearchResult::STATUS_REGISTERED;
            } elseif ($domain['statis'] == 'reserved') {
                $status = SearchResult::STATUS_RESERVED;
            } else {
                $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);

            // Return premium information if applicable
            if ($domain['isPremiumName']) {
                $searchResult->setPremiumDomain(true);
                $searchResult->setPremiumCostPricing(
                    array(
                        'register' => $domain['premiumRegistrationPrice'],
                        'renew' => $domain['premiumRenewPrice'],
                        'CurrencyCode' => 'USD',
                    )
                );
            }

            // Append to the search results list
            $results->append($searchResult);
        }

        return $results;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Domain Suggestion Settings.
 *
 * Defines the settings relating to domain suggestions (optional).
 * It follows the same convention as `getConfigArray`.
 *
 * @see https://developers.whmcs.com/domain-registrars/check-availability/
 *
 * @return array of Configuration Options
 */
function twnicepp_DomainSuggestionOptions() {
    return array(
        'includeCCTlds' => array(
            'FriendlyName' => 'Include Country Level TLDs',
            'Type' => 'yesno',
            'Description' => 'Tick to enable',
        ),
    );
}

/**
 * Get Domain Suggestions.
 *
 * Provide domain suggestions based on the domain lookup term provided.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain suggestions check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */
function twnicepp_GetDomainSuggestions($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // availability check parameters
    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $isIdnDomain = (bool) $params['isIdnDomain'];
    $premiumEnabled = (bool) $params['premiumEnabled'];
    $suggestionSettings = $params['suggestionSettings'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'searchTerm' => $searchTerm,
        'tldsToSearch' => $tldsToInclude,
        'includePremiumDomains' => $premiumEnabled,
        'includeCCTlds' => $suggestionSettings['includeCCTlds'],
    );

    try {
        $api = new ApiClient();
        $api->call('GetSuggestions', $postfields);

        $results = new ResultsList();
        foreach ($api->getFromResponse('domains') as $domain) {

            // Instantiate a new domain search result object
            $searchResult = new SearchResult($domain['sld'], $domain['tld']);

            // All domain suggestions should be available to register
            $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);

            // Used to weight results by relevance
            $searchResult->setScore($domain['score']);

            // Return premium information if applicable
            if ($domain['isPremiumName']) {
                $searchResult->setPremiumDomain(true);
                $searchResult->setPremiumCostPricing(
                    array(
                        'register' => $domain['premiumRegistrationPrice'],
                        'renew' => $domain['premiumRenewPrice'],
                        'CurrencyCode' => 'USD',
                    )
                );
            }

            // Append to the search results list
            $results->append($searchResult);
        }

        return $results;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get registrar lock status.
 *
 * Also known as Domain Lock or Transfer Lock status.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return string|array Lock status or error message
 */
function twnicepp_GetRegistrarLock($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    if (!array_key_exists('error', $response)) {
        return (in_array('clientTransferProhibited', $response['status'])) ? 'locked' : 'unlocked';
    }
    
    return 'unlocked';
}

/**
 * Set registrar lock status.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_SaveRegistrarLock($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // lock status
    $lockStatus = $params['lockenabled'];

    // Build post data
    $putfields = array(
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        '_method' => 'PUT',
        'client_transfer_prohibited' => ($lockStatus == 'locked') ? 'true' : 'false',
    );

    $domainUpdateUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains' : 'http://dcitn.com/api/domains';
    try {
        $api = new ApiClient();
        $api->setUrl($domainUpdateUrl);
        $response = $api->call('Domain TransferLock', $putfields);

        return $response['result'] ? "{$sld}.{$tld} 更新完成" : "{$sld}.{$tld} 更新失敗";

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Get DNS Records for DNS Host Record Management.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array DNS Host Records
 */
function twnicepp_GetDNS($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
    );

    try {
        $api = new ApiClient();
        $api->call('GetDNSHostRecords', $postfields);

        $hostRecords = array();
        foreach ($api->getFromResponse('records') as $record) {
            $hostRecords[] = array(
                "hostname" => $record['name'], // eg. www
                "type" => $record['type'], // eg. A
                "address" => $record['address'], // eg. 10.0.0.1
                "priority" => $record['mxpref'], // eg. 10 (N/A for non-MX records)
            );
        }
        return $hostRecords;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Update DNS Host Records.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_SaveDNS($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // dns record parameters
    $dnsrecords = $params['dnsrecords'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'records' => $dnsrecords,
    );

    try {
        $api = new ApiClient();
        $api->call('GetDNSHostRecords', $postfields);

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Enable/Disable ID Protection.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_IDProtectToggle($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // id protection parameter
    $protectEnable = (bool) $params['protectenable'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
    );

    try {
        $api = new ApiClient();

        if ($protectEnable) {
            $api->call('EnableIDProtection', $postfields);
        } else {
            $api->call('DisableIDProtection', $postfields);
        }

        return array(
            'success' => 'success',
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Request EEP Code.
 *
 * Supports both displaying the EPP Code directly to a user or indicating
 * that the EPP Code will be emailed to the registrant.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 *
 */
function twnicepp_GetEPPCode($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    $result = [];
    if (!array_key_exists('error', $response)) {
        $result = [
            'eppcode' => $response['authCode'],
        ];
    } else {
        $result['error'] = "{$sld}.{$tld} 發送 EppCode 失敗";
    }

    return $result;
}

/**
 * Release a Domain.
 *
 * Used to initiate a transfer out such as an IPSTAG change for .UK
 * domain names.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_ReleaseDomain($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // transfer tag
    $transferTag = $params['transfertag'];

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
    );

    $domainTransferApproveUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains/transfer-approve' : 'http://dcitn.com/api/domains/transfer-approve';
    try {
        $api = new ApiClient();
        $api->setUrl($domainTransferApproveUrl);
        $response = $api->call('Domain Transfer Approve', $postfields);

        return $response['result'] ? ['success' => 'success'] : ['error' => '網域轉移同意失敗'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Delete Domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_RequestDelete($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'domain' => $sld . '.' . $tld,
        '_method' => 'DELETE',
    );

    $domainDeleteUrl = ($testMode) ? 'http://dev.dcitn.com/api/domains' : 'http://dcitn.com/api/domains';
    try {
        $api = new ApiClient();
        $api->setUrl($domainDeleteUrl);
        $response = $api->call('Delete Domain', $postfields);

        return $response['result'] ? ['success' => true] : ['error' => '網域刪除失敗'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Register a Nameserver.
 *
 * Adds a child nameserver for the given domain name.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_RegisterNameserver($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];
    $ipAddress = $params['ipaddress'];

    $hostUrl = ($testMode) ? 'http://dev.dcitn.com/api/hosts?nameservers='.$nameserver.'&api_token='.$userToken : 'http://dcitn.com/api/hosts?nameserver='.$nameserver.'&api_token='.$userToken;
    
    $api = new ApiClient();
    try {
        $api->setUrl($hostUrl);
        $response = $api->call('Check Nameserver', [], 'GET');

        if ($response['result'] && $response['message']['message'][0]['exist']) return ['error' => 'Nameserver 已經存在'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'nameserver' => $nameserver,
        'ip_address' => $ipAddress,
    );

    $HostCreateUrl = ($testMode) ? 'http://dev.dcitn.com/api/hosts' : 'http://dcitn.com/api/hosts';
    try {
        $api->setUrl($HostCreateUrl);
        $response = $api->call('RegisterNameserver', $postfields);

        return $response['result'] ? ['success' => true] : ['error' => 'Nameserver 註冊失敗'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Modify a Nameserver.
 *
 * Modifies the IP of a child nameserver.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_ModifyNameserver($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];
    $currentIpAddress = $params['currentipaddress'];
    $newIpAddress = $params['newipaddress'];

    $hostInfoUrl = ($testMode) ? 'http://dev.dcitn.com/api/hosts/show?nameserver='.$nameserver.'&api_token='.$userToken : 'http://dcitn.com/api/hosts/show?nameserver='.$nameserver.'&api_token='.$userToken;
    $api = new ApiClient();
    try {
        $api->setUrl($hostInfoUrl);
        $response = $api->call('Get Nameserver', [], 'GET');

        $oldIp = $response['result'] ? array_keys($response['message']['ip'])[0] : null;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    if ($currentIpAddress != $oldIp) return ['error' => 'Nameserver 更新失敗'];

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'nameserver' => $nameserver,
        'currentip' => $currentIpAddress,
        'ip_address' => $newIpAddress,
        '_method' => 'PUT',
    );

    $hostUpdateUrl = ($testMode) ? 'http://dev.dcitn.com/api/hosts' : 'http://dcitn.com/api/hosts';
    try {
        $api->setUrl($hostUpdateUrl);
        $response = $api->call('ModifyNameserver', $postfields);

        return $response['result'] ? ['success' => true] : ['error' => 'Nameserver 更新失敗'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Delete a Nameserver.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_DeleteNameserver($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];

    $hostUrl = ($testMode) ? 'http://dev.dcitn.com/api/hosts?nameservers='.$nameserver.'&api_token='.$userToken : 'http://dcitn.com/api/hosts?nameserver='.$nameserver.'&api_token='.$userToken;
    
    $api = new ApiClient();
    try {
        $api->setUrl($hostUrl);
        $response = $api->call('Check Nameserver', [], 'GET');

        if ($response['result'] && !$response['message']['message'][0]['exist']) return ['error' => 'Nameserver 不存在'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'nameserver' => $nameserver,
        '_method' => 'DELETE',
    );

    $hostDeleteUrl = ($testMode) ? 'http://dev.dcitn.com/api/hosts' : 'http://dcitn.com/api/hosts';
    try {
        $api->setUrl($hostDeleteUrl);
        $api->call('DeleteNameserver', $postfields);

        return $response['result'] ? ['success' => true] : ['error' => 'Nameserver 刪除失敗'];

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * Domain syncing is intended to ensure domain status and expiry date
 * changes made directly at the domain registrar are synced to WHMCS.
 * It is called periodically for a domain.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_Sync($params)
{
    // user defined configuration values
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    if (!array_key_exists('error', $response)) {
        $expdate = date('Y-m-d', strtotime($response['expiration']));
        return [
            'expirydate' => $expdate, // Format: YYYY-MM-DD
            'active' => true, // Return true if the domain is active
            'expired' => date('Y-m-d') >= $expdate ? true : false, // Return true if the domain has expired
            'transferredAway' => false, // Return true if the domain is transferred out
        ];
    }

    return [
        'error' => $response['message']
    ];
}

/**
 * Incoming Domain Transfer Sync.
 *
 * Check status of incoming domain transfers and notify end-user upon
 * completion. This function is called daily for incoming domains.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function twnicepp_TransferSync($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
    );

    try {
        $api = new ApiClient();
        $api->call('CheckDomainTransfer', $postfields);

        if ($api->getFromResponse('transfercomplete')) {
            return array(
                'completed' => true,
                'expirydate' => $api->getFromResponse('expirydate'), // Format: YYYY-MM-DD
            );
        } elseif ($api->getFromResponse('transferfailed')) {
            return array(
                'failed' => true,
                'reason' => $api->getFromResponse('failurereason'), // Reason for the transfer failure if available
            );
        } else {
            // No status change, return empty array
            return array();
        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * Client Area Custom Button Array.
 *
 * Allows you to define additional actions your module supports.
 * In this example, we register a Push Domain action which triggers
 * the `twnicepp_push` function when invoked.
 *
 * @return array
 */
function twnicepp_ClientAreaCustomButtonArray()
{
    /*return array(
        'Push Domain' => 'push',
    );*/
    return [];
}

/**
 * Client Area Allowed Functions.
 *
 * Only the functions defined within this function or the Client Area
 * Custom Button Array can be invoked by client level users.
 *
 * @return array
 */
function twnicepp_ClientAreaAllowedFunctions()
{
    /*return array(
        'Push Domain' => 'push',
    );*/
    return [];
}

/**
 * Example Custom Module Function: Push
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
/*function twnicepp_push($params)
{
    // user defined configuration values
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // Perform custom action here...

    return 'Not implemented';
}*/

/**
 * Client Area Output.
 *
 * This function renders output to the domain details interface within
 * the client area. The return should be the HTML to be output.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return string HTML Output
 */
function twnicepp_ClientArea($params)
{
    $output = '
        <div class="alert alert-info">
            Your custom HTML output goes here...
        </div>
    ';

    return $output;
}

/**
 * Debug
 *
 * @param [type] $params
 * @return void
 */
function twnicepp_dd($params)
{
    echo "<pre>";
    print_r($params);
    exit();
}