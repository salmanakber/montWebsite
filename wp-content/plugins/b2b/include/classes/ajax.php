<?php

class ajax extends b2b
{
    public function __construct($urlPath, $dirPath)
    {
        parent::__construct($urlPath, $dirPath);
        add_action('wp_ajax_add_to_car_b2b_hook', array($this, 'add_to_cart_b2b'));
        add_action('wp_ajax_nopriv_add_to_car_b2b_hook', array($this, 'add_to_cart_b2b'));

        add_action('wp_ajax_show_cart_data_hook', array($this, 'show_cart_data'));
        add_action('wp_ajax_nopriv_show_cart_data_hook', array($this, 'show_cart_data'));

         add_action('wp_ajax_removeKey', array($this, 'remove_item_from_session'));
        add_action('wp_ajax_nopriv_removeKey', array($this, 'remove_item_from_session'));

        add_action('wp_ajax_placed_order', array($this, 'send_order_to_api'));
        add_action('wp_ajax_nopriv_placed_order', array($this, 'send_order_to_api'));
    }

    public function add_to_cart_b2b()
    {
        $productData = $_POST['productData'];
		
		if($this->getTotalSizeValue($productData) >= $productData['fabircDetails'][0]['moq']){
        // Call a function to save the product data in the session
        $this->put_b2b_data_in_session($productData);
        exit();
		}
		else{
	wp_send_json(array(

    'message' => "Minimum order is ".$productData['fabircDetails'][0]['moq']." shirts You're only adding ".$this->getTotalSizeValue($productData)." to your cart.",
    'sizeError' => true,
    'server' => ''
        ));
			exit();
		}
    }

public function put_b2b_data_in_session($productData)
{
    // Start or resume the session
    if (!session_id()) {
        session_start();
    }

    // Check if the product data exists in the session
    if (isset($_SESSION['products']) && !empty($_SESSION['products'])) {
        // Iterate through each product in the session
        foreach ($_SESSION['products'] as $key => $existingProduct) {
            // Compare the product name to check if it already exists
            if ($existingProduct['fabircDetails'][0]['fabricName'] === $productData['fabircDetails'][0]['fabricName']) {
                // If the product with the same name already exists, update its data and return
                $_SESSION['products'][$key] = $productData;
                     wp_send_json_success(array(
                    'message' => 'Product data updated successfully',
                      'count' => count($_SESSION['products']),
                        ));

                return;
            }
        }
    }

    // If the product data does not already exist, add it to the session
    $_SESSION['products'][] = $productData;

                       wp_send_json_success(array(
                    'message' => 'Product data saved successfully',
                      'count' => count($_SESSION['products']),
                        ));
}


// Function to compare two sets of product data for equality
private function areProductDataEqual($productData1, $productData2)
{
    // Compare all fields of the product data
    return (
        $productData1['size'] === $productData2['size'] &&
        $productData1['comments'] === $productData2['comments'] &&
        $productData1['price'] === $productData2['price'] &&
        $productData1['checkedForms'] === $productData2['checkedForms'] &&
        $productData1['fabircDetails'] === $productData2['fabircDetails'] &&
        $productData1['collarType'] === $productData2['collarType'] &&
        $productData1['cuffType'] === $productData2['cuffType']
        // Add additional comparisons for other fields as needed
    );
}


public function show_cart_data() {
    // Start the session
    session_start();

    // Initialize the response array
    $response = array();

    // Check if the 'products' session variable exists and is not empty
    if(isset($_SESSION['products']) && !empty($_SESSION['products'])) {
        // If 'products' session variable is set and not empty
        $data = $_SESSION['products'];
        // Start building the HTML for the accordion items
        $html = '';
        $html .= '<div class="accordion-b2b" id="monteB2B">';
        foreach ($data as $key => $item) {
            $html .= '<div class="accordion-item-monte-b2b">';
            $html .= '<h2 class="accordion-header-monte-b2b" id="heading' . $key . '">';
            $html .= $item['fabircDetails'][0]['fabricName'];
            $html .= '<div style="display: flex;position: relative;"><button class="accordion-button-monte-b2b" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $key . '" aria-expanded="true" aria-controls="collapse' . $key . '">';
            $html .= '<span class="fa fa-caret-down"></span>';
            $html .= '</button>';
            $html .= '<span class="monte-b2b-remove-item" data-id="'.$key.'">&times;</span></div>';
            $html .= '</h2>';
            $html .= '<div id="collapse' . $key . '" class="accordion-collapse-monte-b2b collapse-monte-b2b d-none" aria-labelledby="heading' . $key . '" data-bs-parent="#accordionExample">';
            $html .= '<div class="accordion-body-monte-b2b">';
            // Output size data
            $html .= '<p>Size:</p>';
            $html .= '<ul>';
            foreach ($item['size'] as $size) {
                $html .= '<li>Pisces: ' . $size['value'] . ', Size: ' . $size['dataValue'] . '</li>';
            }
            $html .= '</ul>';           

            // Output price
            $html .= '<div class="cupp"><b>Total shirts: </b>'.$item['price'].'</div>';

            // Output checked forms
            $html .= '<p>Checked Forms:</p>';
            $html .= '<ul>';
            foreach ($item['checkedForms'] as $checkedForm) {
                $html .= '<li>' . $checkedForm . '</li>';
            }
            $html .= '</ul>';

            // Output fabric details
            $html .= '<p>Fabric Details:</p>';
            $html .= '<ul>';
            foreach ($item['fabircDetails'] as $fabricDetail) {
                $html .= "<li>Color: ".$fabricDetail['fabircColor'].'</li>';
                $html .= "<li>Quality: ".$fabricDetail['fabricQuality'].'</li>';
                $html .= "<li>Weight: ".$fabricDetail['fabricWeight'].'</li>';
    
            }
            $html .= '</ul>';

            // Output collar and cuff type
            $html .= '<div class="cupp"><b>Collar Type:</b> '.$item['collarType'].'</div>';
            $html .= '<div class="cupp"><b>Cuff Type:</b> '.$item['cuffType'].'</div>';

             // Output comments
            $html .= '<p>Comments: </p> <span class="b2b-span">'. $item['comments'].'</span>';

            $html .= '</div></div></div>';
        }
        $html .= '</div>';
        // Add the HTML to the response array
        $response['html'] = $html;
    } else {
        // If 'products' session variable is not set or empty
        $response['html'] = '<div class="empty-cart"><h2>Your cart is empty</h2></div>';
    }

    // Set the JSON response
    $response['json'] = $_SESSION['products'] ?? array();

    // Output the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);

    // Exit to prevent any further output
    exit();
}

public function remove_item_from_session()
{
    // Start or resume the session
    if (!session_id()) {
        session_start();
    }
    $keyToRemove = $_REQUEST['key'];
    // Check if the session variable exists and is an array
    if (isset($_SESSION['products']) && is_array($_SESSION['products'])) {
        // Iterate through each item in the session array
        foreach ($_SESSION['products'] as $key => $item) {
            // Check if the current item's key matches the key to remove
            if ($key == $keyToRemove) {
                // Remove the item from the session array
                unset($_SESSION['products'][$key]);
                wp_send_json_success(array(
                    'message' => 'Product data removed successfully',
                      'count' => count($_SESSION['products']),
                        ));
                //return true; // Return true indicating successful removal
            }
        }
    }

    return false; // Return false if the item was not found or the session variable does not exist
}
public function getTotalSizeValue($array) {
    $totalSizeValue = 0;

        if (isset($array) && is_array($array['size'])) {
            foreach ($array['size'] as $size) {
                if (isset($size['value']) && ($size['value'])) {
                    $totalSizeValue += $size['value'];
                }
				
        }
    }
    return $totalSizeValue;
}

public function send_order_to_api() {
    // Retrieve form data
    $customerData = array();
    if(isset($_REQUEST['productData'])){
    foreach ($_REQUEST['productData'] as $value) {
        // code...
        $customerData[$value['name']] = $value['value'];
    }
    }
 
    // Check if products are selected
    header('Content-Type: text/html; charset=utf-8');
    if(isset($_SESSION['products']) && count($_SESSION['products']) > 0) {

        // Prepare order data
        $orderData = array(
            'customerData' => json_encode($customerData, JSON_UNESCAPED_UNICODE),
            'orderData' => json_encode($_SESSION['products'], JSON_UNESCAPED_UNICODE)
        );
    
        // Send order data to API
        $response = $this->getApifromDC('storeOrder', $orderData, $this->api);
        if(json_decode($response, true)['status'] === 'success')
        {
          session_start();
        unset($_SESSION["products"]);
        wp_send_json(array(
            'message' => 'Order successfully sent placcd',
            'success' => true,
            'server' => $response 
        ));
        exit();
        }
        else
        {
             wp_send_json(array(
            'message' => 'Network error!',
            'success' => false,
            'server' => $response 
        )); 
        exit();
        }

    } else{
        // No products selected, send error response
        wp_send_json(array(
            'message' => 'Please add some products to the cart to continue',
            'success' => false,
            'server' => ''
        ));
        
    }

}




}



?>
