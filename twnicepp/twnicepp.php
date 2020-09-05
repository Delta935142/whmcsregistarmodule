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
use WHMCS\Database\Capsule;

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
    if ($params['ns1'] && gethostbyname($params['ns1']) != $params['ns1']) array_push($nameservers, $params['ns1']);
    if ($params['ns2'] && gethostbyname($params['ns2']) != $params['ns2']) array_push($nameservers, $params['ns2']);
    if ($params['ns3'] && gethostbyname($params['ns3']) != $params['ns3']) array_push($nameservers, $params['ns3']);
    if ($params['ns4'] && gethostbyname($params['ns4']) != $params['ns4']) array_push($nameservers, $params['ns4']);
    if ($params['ns5'] && gethostbyname($params['ns5']) != $params['ns5']) array_push($nameservers, $params['ns5']);

    if (!count($nameservers)) $nameservers = ['ns1.ilv.tw', 'ns2.ilv.tw'];

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

    // Admin contact information
    $adminArr = [
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
        'fax' => $params["fax"],
        'auth_code' => $authCode,
    ];

    if (isset($params["customfields"]) && count($params["customfields"]) > 0) {
        $registarArr['c_name'] = $params["customfields"][0]['value'];
        $registarArr['app_id'] = $params["customfields"][2]['value'];
        $registarArr['c_organization'] = $params["customfields"][5]['value'];
        $registarArr['cmp_id'] = $params["customfields"][4]['value'];
        $registarArr['c_province'] = $params["state"];
        $registarArr['c_city'] = $params["city"];
        $registarArr['c_address'] = $params["customfields"][3]['value'];

        $adminArr['c_name'] = $params["customfields"][0]['value'];
        $adminArr['app_id'] = $params["customfields"][2]['value'];
        $adminArr['c_organization'] = $params["customfields"][5]['value'];
        $adminArr['cmp_id'] = $params["customfields"][4]['value'];
        $adminArr['c_province'] = $params["adminstate"];
        $adminArr['c_city'] = $params["admincity"];
        $adminArr['c_address'] = $params["customfields"][3]['value'];
    }

    $registrantId = createContact($userToken, $testMode, $registarArr);
    $adminId = createContact($userToken, $testMode, $adminArr);
    $techId = createContact($userToken, $testMode, $adminArr);
    $billingId = createContact($userToken, $testMode, $adminArr);

    // Build post data
    $postfields = [
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        'years' => $registrationPeriod,
        'nameservers' => $nameservers,
        'auth_code' => $authCode,
        'lang' => (strstr($params['tld'], '台灣')) ? 'ZH' : 'EN',
        'registrant' => $registrantId,
        'admincontact' => $adminId,
        'techcontact' => $techId,
        'billingcontact' => $billingId,
    ];

    if (!is_array($registrantId) && $registrantId) {
        try {
            $api = new ApiClient($testMode);
            $response = $api->call('domainCreate', $postfields);

            $result = [];
            if ($response['result']) {
                $putfields = [
                    'api_token' => $userToken,
                    'domain' => "{$sld}.{$tld}",
                    '_method' => 'PUT',
                    'client_delete_prohibited' => 'true',
                    'client_transfer_prohibited' => 'true',
                ];

                domainUpdata($testMode, $putfields);

                $result['success'] = true;
            } else {
                $result['error'] = "{$sld}.{$tld} 註冊失敗";
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    } else {
        return [
            'error' => "{$sld}.{$tld} 註冊失敗",
        ];
    }
}

/**
 * Domain Update
 *
 * @param [type] $testMode
 * @param array $info
 * @return void
 */
function domainUpdata($testMode, $info = [])
{
    try {
        $api = new ApiClient($testMode);
        $api->call('domainUpdate', $info);
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
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
    $info['api_token'] = $userToken;

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('contactCreate', $info);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];
    $registrationPeriod = $params['regperiod'];
    $eppCode = $params['eppcode'];
	
	if ($eppCode == '搶註') {
		return [
            'error' => '更新搶註資料',
        ];
	}

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

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainTransferRequest', $postfields);
		
		if ($response['result']) {
			return [
				'success' => true,
			];
		} else {
			return [
				'error' => "{$sld}.{$tld} 轉入請求失敗",
			];
		}
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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];
    $registrationPeriod = $params['regperiod'];

    // Build post data.
    $postfields = array(
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        'period' => $registrationPeriod,
    );
    
    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainRenew', $postfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    $result = [];
    if (!array_key_exists('error', $response)) {
        foreach ($response['nameservers'] as $nameserver) {
            $ns[] = $nameserver;
        }
        $result = [
            'ns1' => array_key_exists(0, $ns) ? $ns[0] : null,
            'ns2' => array_key_exists(1, $ns) ? $ns[1] : null,
            'ns3' => array_key_exists(2, $ns) ? $ns[2] : null,
            'ns4' => array_key_exists(3, $ns) ? $ns[3] : null,
            'ns5' => array_key_exists(4, $ns) ? $ns[4] : null,
        ];
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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
        ];

        try {
            $api = new ApiClient($testMode);
            $response = $api->call('domainHosts', $putfields);

            return $response['result'] ? ['success' => true] : ['error' => 'Nameserver 更新失敗'];
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

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainInfo', $getfields, 'GET');

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // Build post data
    $client = Capsule::table('tblclients')->where('id', $params['userid'])->first();
    $customfields = Capsule::table('tblcustomfieldsvalues')->where('relid', $params['userid'])->get();

    $registrantArr = [
        'Name' => "{$client->firstname} {$client->lastname}",
        'Company Name' => $client->companyname,
        'Email Address' => $client->email,
        'Address 1' => $client->address1,
        'Address 2' => $client->address2,
        'City' => $client->city,
        'State' => $client->state,
        'Postcode' => $client->postcode,
        'Country' => $client->country,
        'Phone Number' => $client->phonenumber,
        'Fax Number' => $client->fax,
    ];

    if (count($customfields)) {
        $registrantArr['Chinese Name'] = $customfields[0]->value;
        $registrantArr['TaiwanID'] = $customfields[1]->value;
        $registrantArr['Chinese Company Name'] = $customfields[2]->value;
        $registrantArr['CompanyID'] = $customfields[3]->value;
        $registrantArr['Chinese State'] = $customfields[4]->value;
        $registrantArr['Chinese City'] = $customfields[5]->value;
        $registrantArr['Chinese Address'] = $customfields[6]->value;
    }

    $response = getDomainInfo($userToken, $testMode, $sld.'.'.$tld) ?? [];

    if (!array_key_exists('error', $response)) {
        $registrant = getContactInfo($userToken, $testMode, $response['registrant']);
    
        if ($registrant) {
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


            $admin = null;
            $tech = null;
            $billing = null;
            foreach ($response['contacts'] as $contact) {
                if (strstr($contact, 'admin')) $admin = getContactInfo($userToken, $testMode, explode(':', $contact)[1]);
                if (strstr($contact, 'tech')) $tech = getContactInfo($userToken, $testMode, explode(':', $contact)[1]);
                if (strstr($contact, 'billing')) $billing = getContactInfo($userToken, $testMode, explode(':', $contact)[1]);
            }

            $contacts = [
                'admin' => $admin,
                'tech' => $tech,
                'billing' => $billing,
            ];

            $arr = [];
            foreach ($contacts as $key => $contact) {
                $arr[$key] = [
                    'Name' => is_array($contact) ? $contact['post_info'][0]['name'] : null,
                    'Company Name' => is_array($contact) ? $contact['post_info'][0]['organization'] : null,
                    'Email Address' => is_array($contact) ? $contact['email'] : null,
                    'Address 1' => is_array($contact) ? $contact['post_info'][0]['address'][0] : null,
                    'Address 2' => is_array($contact) ? $contact['post_info'][0]['address'][1] : null,
                    'City' => is_array($contact) ? $contact['post_info'][0]['city'] : null,
                    'State' => is_array($contact) ? $contact['post_info'][0]['province'] : null,
                    'Postcode' => is_array($contact) ? $contact['post_info'][0]['zipcode'] : null,
                    'Country' => is_array($contact) ? $contact['post_info'][0]['country'] : null,
                    'Phone Number' => is_array($contact) ? $contact['phone'] : null,
                    'Fax Number' => is_array($contact) ? $contact['fax'] : null,
                ];
        
                if (is_array($contact) && array_key_exists(1, $contact['post_info'])) {
                    $arr[$key]['Chinese Name'] = $contact['post_info'][1]['name'];
                    $arr[$key]['TaiwanID'] = $contact['app_id'];
                    $arr[$key]['Chinese Company Name'] = $contact['post_info'][1]['organization'];
                    $arr[$key]['CompanyID'] = $contact['cmp_id'];
                    $arr[$key]['Chinese State'] = $contact['post_info'][1]['province'];
                    $arr[$key]['Chinese City'] = $contact['post_info'][1]['city'];
                    $arr[$key]['Chinese Address'] = $contact['post_info'][1]['address'][0];
                }
            }
        } else {
            $registarArr = [
                'name' => $registrantArr['Name'],
                'email' => $registrantArr['Email Address'],
                'phone' => $registrantArr['Phone Number'],
                'organization' => $registrantArr['Company Name'],
                'address1' => $registrantArr['Address 1'],
                'address2' => $registrantArr['Address 2'],
                'zipcode' => $registrantArr['Postcode'],
                'city' => $registrantArr['City'],
                'country' => $registrantArr['Country'],
                'province' => $registrantArr['State'],
                'fax' => $registrantArr['Fax Number'],
            ];

            if (count($customfields)) {
                $registarArr['c_name'] = $registrantArr['Chinese Name'];
                $registarArr['app_id'] = $registrantArr['TaiwanID'];
                $registarArr['c_organization'] = $registrantArr['Chinese Company Name'];
                $registarArr['cmp_id'] = $registrantArr['CompanyID'];
                $registarArr['c_province'] = $registrantArr['Chinese State'];
                $registarArr['c_city'] = $registrantArr['Chinese City'];
                $registarArr['c_address'] = $registrantArr['Chinese Address'];
            }

            $registrantId = createContact($userToken, $testMode, $registarArr);
            $adminId = createContact($userToken, $testMode, $registarArr);
            $techId = createContact($userToken, $testMode, $registarArr);
            $billingId = createContact($userToken, $testMode, $registarArr);

            if ($registrantId) {
                $putfields = [
                    'api_token' => $userToken,
                    'domain' => "{$sld}.{$tld}",
                    '_method' => 'PUT',
                    'registrant' => $registrantId,
                    'admincontact' => $adminId,
                    'techcontact' => $techId,
                    'billingcontact' => $billingId,
                    'client_delete_prohibited' => 'true',
                    'client_transfer_prohibited' => 'true',
                ];

                domainUpdata($testMode, $putfields);
            }
            

            $arr = [
                'tech' => $registrantArr,
                'billing' => $registrantArr,
                'admin' => $registrantArr
            ];
        }

        return [
            'Registrant' => $registrantArr,
            'Technical' => $arr['tech'],
            'Billing' => $arr['billing'],
            'Admin' => $arr['admin'],
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

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('contactInfo', $getfields, 'GET');

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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

    if (!array_key_exists('error', $response)) {
        $error = [];
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

                try {
                    $api = new ApiClient($testMode);
                    $response = $api->call('contactUpdate', $info);
                    if (!$response['result']) $error[$key] = '更新失敗';
                } catch (\Exception $e) {
                    return array(
                        'error' => $e->getMessage(),
                    );
                }
            }
        }

        if (count($error)) {
            return [
                'error' => json_encode($error, JSON_UNESCAPED_UNICODE)
            ];
        } else {
            return [
                'success' => true
            ];
        }

        
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

    $sld = $params['sld'];
    $tld = $params['tld'];

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

    $sld = $params['sld'];
    $tld = $params['tld'];

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // lock status
    $lockStatus = $params['lockenabled'];

    // Build post data
    $putfields = array(
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
        '_method' => 'PUT',
        'client_transfer_prohibited' => ($lockStatus == 'locked') ? 'true' : 'false',
    );

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainUpdate', $putfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
        $api = new ApiClient($testMode);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // transfer tag
    $transferTag = $params['transfertag'];

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'domain' => "{$sld}.{$tld}",
    );

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainTransferApprove', $postfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // Build post data
    $postfields = array(
        'api_token' => $userToken,
        'domain' => $sld . '.' . $tld,
        '_method' => 'DELETE',
    );

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainDelete', $postfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];
    $ipAddress = $params['ipaddress'];

    $getfields = '?nameservers='.$nameserver.'&api_token='.$userToken;

    $api = new ApiClient($testMode);

    try {
        $response = $api->call('hostCheck', $getfields, 'GET');

        if ($response['result'] && $response['message']['hosts'][0]['exist']) return ['error' => 'Nameserver 已經存在'];

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

    try {
        $response = $api->call('hostCreate', $postfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];
    $currentIpAddress = $params['currentipaddress'];
    $newIpAddress = $params['newipaddress'];

    $getfields = '?nameservers='.$nameserver.'&api_token='.$userToken;

    $api = new ApiClient($testMode);

    try {
        $response = $api->call('hostInfo', $getfields, 'GET');
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

    try {
        $response = $api->call('hostUpdate', $postfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // nameserver parameters
    $nameserver = $params['nameserver'];

    $getfields = '?nameservers='.$nameserver.'&api_token='.$userToken;
    
    $api = new ApiClient($testMode);

    try {
        $response = $api->call('hostCheck', $getfields, 'GET');

        if ($response['result'] && !$response['message']['hosts'][0]['exist']) return ['error' => 'Nameserver 不存在'];

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

    try {
        $api->call('hostDelete', $postfields);

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
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

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
    $userToken = $params['APIToken'];
    $testMode = $params['TestMode'];

    // domain parameters
    $sld = $params['sld'];
    $tld = $params['tld'] == 'tw(台灣)' ? 'tw' : $params['tld'];

    // Build post data
    $getfields = '?domain='.$sld.'.'.$tld.'&api_token='.$userToken;

    try {
        $api = new ApiClient($testMode);
        $response = $api->call('domainTransferQuery', $getfields, 'GET');

        if ($response['result']) {
            return array(
                'completed' => true,
                'expirydate' => date('Y-m-d', strtotime($response['message']['expdate'])), // Format: YYYY-MM-DD
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