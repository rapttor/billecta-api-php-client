<?php

namespace Billecta;

class Billecta {

	var $creditor_public_id;
	var $http_client;

	function __construct($host, $secure_token, $format = 'json', $version = 1) {
		// add version to host var
		$host = $host . '/v' . $version . '/';

		// vars
		$this->creditor_public_id = NULL;

		// check accept format
		if ($format == 'json') {
			$accept_format = 'application/json';
		} else {
			// oh don't feel intimidated.
			throw new Exception('JSON is the only supported format. For good reasons.');
		}

		// default headers used in every request
		$default_headers = [
			'Accept' => $accept_format,
			'Authorization' => 'SecureToken ' . base64_encode($secure_token)
		];

		// create http client
		$this->http_client = new \GuzzleHttp\Client(
			[
				'base_uri' => $host,
				'headers' => $default_headers
			]
		);
	}

	private function GUID() {
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
		}

	private function getCurrentDate() {
		return date('Y-m-d H:i:sO');
	}

	// convert to string and create php object from the json response
	private function returnResponseBody($response) {
		return json_decode((string)$response->getBody());
	}

	public function setCreditorPublicId($creditor_public_id) {
		$this->creditor_public_id = $creditor_public_id;
	}

	// companies
	public function getCreditors() {
		$response = $this->http_client->get('creditors/creditors');
		return $this->returnResponseBody($response);
	}

	// customers
	public function addDebtor($debtor) {

		if (!$this->creditor_public_id) {
			throw new Exception('No debtor is selected. Use setCreditor() function first', 1);
		}

		if (!array_key_exists('Name', $debtor)) {
			throw new Exception('Name property is required on $debtor object', 1);

		}

		$url = 'debtors/debtor/';


		$debtor['DebtorPublicId'] = $this->GUID();
		$debtor['CreditorPublicId'] = $this->creditor_public_id;
		$debtor['Created'] = $this->getCurrentDate();

		$body = json_encode($debtor);

		$response = $this->http_client->post($url, ['body' => $body]);
		return $this->returnResponseBody($response);
	}

	public function getDebtor($debtor_public_id) {

		$url = 'debtors/debtor/' . $debtor_public_id;

		$response = $this->http_client->get($url);
		return $this->returnResponseBody($response);
	}

	public function getDebtorExternalId($external_id) {

		$url = 'debtors/debtor/' . $this->creditor_public_id;

		$query = array(
			'externalid' => $external_id
		);

		$response = $this->http_client->get($url, ['query' => $query]);
		return $this->returnResponseBody($response);
	}
}

class Exception extends \Exception {}
