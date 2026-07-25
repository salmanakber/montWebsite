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
            $fabric_name = isset( $item['fabircDetails'][0]['fabricName'] ) ? $item['fabircDetails'][0]['fabricName'] : 'Item';
            $html .= '<div class="accordion-item-monte-b2b b2b-cart-item">';
            $html .= '<div class="accordion-header-monte-b2b b2b-cart-item__header" id="heading' . esc_attr( $key ) . '">';
            $html .= '<button type="button" class="accordion-button-monte-b2b b2b-cart-item__toggle" data-bs-toggle="collapse" data-bs-target="#collapse' . esc_attr( $key ) . '" aria-expanded="false">';
            $html .= '<span class="b2b-cart-item__title">' . esc_html( $fabric_name ) . '</span>';
            $html .= '<span class="b2b-cart-item__chevron fa fa-caret-down" aria-hidden="true"></span>';
            $html .= '</button>';
            $html .= '<button type="button" class="monte-b2b-remove-item b2b-cart-item__remove" data-id="' . esc_attr( $key ) . '" aria-label="Remove item">&times;</button>';
            $html .= '</div>';
            $html .= '<div id="collapse' . esc_attr( $key ) . '" class="accordion-collapse-monte-b2b collapse-monte-b2b d-none b2b-cart-item__body" aria-labelledby="heading' . esc_attr( $key ) . '">';
            $html .= '<div class="accordion-body-monte-b2b">';

            $html .= '<div class="b2b-cart-meta">';
            $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Sizes</span><ul class="b2b-cart-meta__list">';
            foreach ($item['size'] as $size) {
                $html .= '<li>' . esc_html( $size['dataValue'] ) . ' · ' . esc_html( $size['value'] ) . ' pcs</li>';
            }
            $html .= '</ul></div>';

            $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Total</span><span class="b2b-cart-meta__value">' . esc_html( $item['price'] ) . '</span></div>';

            if ( ! empty( $item['checkedForms'] ) ) {
                $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Body fit</span><ul class="b2b-cart-meta__list">';
                foreach ($item['checkedForms'] as $checkedForm) {
                    $html .= '<li>' . esc_html( ucwords( str_replace( '_', ' ', $checkedForm ) ) ) . '</li>';
                }
                $html .= '</ul></div>';
            }

            $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Fabric</span><ul class="b2b-cart-meta__list">';
            foreach ($item['fabircDetails'] as $fabricDetail) {
                $html .= '<li>' . esc_html( $fabricDetail['fabircColor'] ) . '</li>';
                $html .= '<li>' . esc_html( $fabricDetail['fabricQuality'] ) . '</li>';
                $html .= '<li>' . esc_html( $fabricDetail['fabricWeight'] ) . '</li>';
            }
            $html .= '</ul></div>';

            if ( ! empty( $item['collarType'] ) ) {
                $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Collar</span><span class="b2b-cart-meta__value">' . esc_html( $item['collarType'] ) . '</span></div>';
            }
            if ( ! empty( $item['cuffType'] ) ) {
                $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Cuff</span><span class="b2b-cart-meta__value">' . esc_html( $item['cuffType'] ) . '</span></div>';
            }

            if ( ! empty( $item['comments'] ) ) {
                $html .= '<div class="b2b-cart-meta__row"><span class="b2b-cart-meta__label">Notes</span><span class="b2b-cart-meta__value">' . esc_html( $item['comments'] ) . '</span></div>';
            }
            $html .= '</div>';

            $html .= '</div></div></div>';
        }
        $html .= '</div>';
        // Add the HTML to the response array
        $response['html'] = $html;
    } else {
        // If 'products' session variable is not set or empty
        $response['html'] = '<div class="empty-cart"><h2>Your cart is empty</h2><p style="margin:8px 0 0;font-size:13px;color:#999;">Add a colour from the B2B catalogue to get started.</p></div>';
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
    if (!session_id()) {
        session_start();
    }
    if(isset($_SESSION['products']) && count($_SESSION['products']) > 0) {

        $cart_items = $_SESSION['products'];

        // Prepare order data
        $orderData = array(
            'customerData' => json_encode($customerData, JSON_UNESCAPED_UNICODE),
            'orderData' => json_encode($cart_items, JSON_UNESCAPED_UNICODE)
        );
    
        // Send order data to API
        $response = $this->getApifromDC('storeOrder', $orderData, $this->api);
        $decoded  = json_decode($response, true);
        if(is_array($decoded) && isset($decoded['status']) && $decoded['status'] === 'success')
        {
        // Mirror into DC Garments Order Portal (local CRM).
        do_action('dc_b2b_order_placed', $customerData, $cart_items, $response);

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
