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
                    'registrant' => $registrantId,
                    'auth_code' => $authCode,
                    'clientDeleteProhibited' => true,
                    'clientTransferProhibited' => true,
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
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];
    $eppCode = $params['eppcode'];

    /**
     * Nameservers.
     *
     * If purchased with web hosting, values will be taken from the
     * assigned web hosting server. Otherwise uses the values specified
     * during the order process.
     */
    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];

    // registrant information
    $firstName = $params["firstname"];
    $lastName = $params["lastname"];
    $fullName = $params["fullname"]; // First name and last name combined
    $companyName = $params["companyname"];
    $email = $params["email"];
    $address1 = $params["address1"];
    $address2 = $params["address2"];
    $city = $params["city"];
    $state = $params["state"]; // eg. TX
    $stateFullName = $params["fullstate"]; // eg. Texas
    $postcode = $params["postcode"]; // Postcode/Zip code
    $countryCode = $params["countrycode"]; // eg. GB
    $countryName = $params["countryname"]; // eg. United Kingdom
    $phoneNumber = $params["phonenumber"]; // Phone number as the user provided it
    $phoneCountryCode = $params["phonecc"]; // Country code determined based on country
    $phoneNumberFormatted = $params["fullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    /**
     * Admin contact information.
     *
     * Defaults to the same as the client information. Can be configured
     * to use the web hosts details if the `Use Clients Details` option
     * is disabled in Setup > General Settings > Domains.
     */
    $adminFirstName = $params["adminfirstname"];
    $adminLastName = $params["adminlastname"];
    $adminCompanyName = $params["admincompanyname"];
    $adminEmail = $params["adminemail"];
    $adminAddress1 = $params["adminaddress1"];
    $adminAddress2 = $params["adminaddress2"];
    $adminCity = $params["admincity"];
    $adminState = $params["adminstate"]; // eg. TX
    $adminStateFull = $params["adminfullstate"]; // eg. Texas
    $adminPostcode = $params["adminpostcode"]; // Postcode/Zip code
    $adminCountry = $params["admincountry"]; // eg. GB
    $adminPhoneNumber = $params["adminphonenumber"]; // Phone number as the user provided it
    $adminPhoneNumberFormatted = $params["adminfullphonenumber"]; // Format: +CC.xxxxxxxxxxxx

    // domain addon purchase status
    $enableDnsManagement = (bool) $params['dnsmanagement'];
    $enableEmailForwarding = (bool) $params['emailforwarding'];
    $enableIdProtection = (bool) $params['idprotection'];

    /**
     * Premium domain parameters.
     *
     * Premium domains enabled informs you if the admin user has enabled
     * the selling of premium domain names. If this domain is a premium name,
     * `premiumCost` will contain the cost price retrieved at the time of
     * the order being placed. The premium order should only be processed
     * if the cost price now matches that previously fetched amount.
     */
    $premiumDomainsEnabled = (bool) $params['premiumEnabled'];
    $premiumDomainsCost = $params['premiumCost'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'eppcode' => $eppCode,
        'nameservers' => array(
            'ns1' => $nameserver1,
            'ns2' => $nameserver2,
            'ns3' => $nameserver3,
            'ns4' => $nameserver4,
            'ns5' => $nameserver5,
        ),
        'years' => $registrationPeriod,
        'contacts' => array(
            'registrant' => array(
                'firstname' => $firstName,
                'lastname' => $lastName,
                'companyname' => $companyName,
                'email' => $email,
                'address1' => $address1,
                'address2' => $address2,
                'city' => $city,
                'state' => $state,
                'zipcode' => $postcode,
                'country' => $countryCode,
                'phonenumber' => $phoneNumberFormatted,
            ),
            'tech' => array(
                'firstname' => $adminFirstName,
                'lastname' => $adminLastName,
                'companyname' => $adminCompanyName,
                'email' => $adminEmail,
                'address1' => $adminAddress1,
                'address2' => $adminAddress2,
                'city' => $adminCity,
                'state' => $adminState,
                'zipcode' => $adminPostcode,
                'country' => $adminCountry,
                'phonenumber' => $adminPhoneNumberFormatted,
            ),
        ),
        'dnsmanagement' => $enableDnsManagement,
        'emailforwarding' => $enableEmailForwarding,
        'idprotection' => $enableIdProtection,
    );

    try {
        $api = new ApiClient();
        $api->call('Transfer', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
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
            'Auth Code' => $registrant['auth_code'],
        ];

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
                'Auth Code' => $admin['auth_code'],
            ];
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
                'Auth Code' => $tech['auth_code'],
            ];
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
                'Auth Code' => $billing['auth_code'],
            ];
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
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // whois information
    $contactDetails = $params['contactdetails'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'contacts' => array(
            'registrant' => array(
                'firstname' => $contactDetails['Registrant']['First Name'],
                'lastname' => $contactDetails['Registrant']['Last Name'],
                'company' => $contactDetails['Registrant']['Company Name'],
                'email' => $contactDetails['Registrant']['Email Address'],
                // etc...
            ),
            'tech' => array(
                'firstname' => $contactDetails['Technical']['First Name'],
                'lastname' => $contactDetails['Technical']['Last Name'],
                'company' => $contactDetails['Technical']['Company Name'],
                'email' => $contactDetails['Technical']['Email Address'],
                // etc...
            ),
            'billing' => array(
                'firstname' => $contactDetails['Billing']['First Name'],
                'lastname' => $contactDetails['Billing']['Last Name'],
                'company' => $contactDetails['Billing']['Company Name'],
                'email' => $contactDetails['Billing']['Email Address'],
                // etc...
            ),
            'admin' => array(
                'firstname' => $contactDetails['Admin']['First Name'],
                'lastname' => $contactDetails['Admin']['Last Name'],
                'company' => $contactDetails['Admin']['Company Name'],
                'email' => $contactDetails['Admin']['Email Address'],
                // etc...
            ),
        ),
    );

    try {
        $api = new ApiClient();
        $api->call('UpdateWhoisInformation', $postfields);

        return array(
            'success' => true,
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
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
        $api->call('GetLockStatus', $postfields);

        if ($api->getFromResponse('lockstatus') == 'locked') {
            return 'locked';
        } else {
            return 'unlocked';
        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
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
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // lock status
    $lockStatus = $params['lockenabled'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'registrarlock' => ($lockStatus == 'locked') ? 1 : 0,
    );

    try {
        $api = new ApiClient();
        $api->call('SetLockStatus', $postfields);

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
    $userIdentifier = $params['API Username'];
    $apiKey = $params['API Key'];
    $testMode = $params['Test Mode'];
    $accountMode = $params['Account Mode'];
    $emailPreference = $params['Email Preference'];
    $additionalInfo = $params['Additional Information'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'];

    // transfer tag
    $transferTag = $params['transfertag'];

    // Build post data
    $postfields = array(
        'username' => $userIdentifier,
        'password' => $apiKey,
        'testmode' => $testMode,
        'domain' => $sld . '.' . $tld,
        'newtag' => $transferTag,
    );

    try {
        $api = new ApiClient();
        $api->call('ReleaseDomain', $postfields);

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
        $api->call('GetDomainInfo', $postfields);

        return array(
            'expirydate' => $api->getFromResponse('expirydate'), // Format: YYYY-MM-DD
            'active' => (bool) $api->getFromResponse('active'), // Return true if the domain is active
            'expired' => (bool) $api->getFromResponse('expired'), // Return true if the domain has expired
            'transferredAway' => (bool) $api->getFromResponse('transferredaway'), // Return true if the domain is transferred out
        );

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
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
    return array(
        'Push Domain' => 'push',
    );
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
    return array(
        'Push Domain' => 'push',
    );
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
function twnicepp_push($params)
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
}

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