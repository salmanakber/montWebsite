<?php
class getApi
{


	public function getApifromDC($type, $product = '', $api){
		$api_url = 'https://dc-garment.com/staff/api/api.php';
		$api_data = array(
			'request_type' => $type,
	        'productId' => ($product !== '') ? $product : 'null'

		);
        // Initialize cURL session
		$ch = curl_init($api_url);
        // Set cURL options
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
            'Api-Key: '.$api  // Replace with your actual API key
        ));
        // Execute cURL session and get the response
		$response = curl_exec($ch);
        // Check for cURL errors
		if (curl_errno($ch)) {
            // Handle cURL errors
			error_log('cURL Error: ' . curl_error($ch));
		}
        // Close cURL session
		curl_close($ch);
        // Decode the JSON response
		return $response;
        // Check for errors in the response
		if (isset($response_data['error'])) {
            // Handle errors
			error_log('Error fetching from DC Garments: ' . $response_data['error']);
		}

	}
}



?>
