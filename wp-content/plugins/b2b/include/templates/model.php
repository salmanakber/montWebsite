
  <?php
  $class = (isset($_SESSION['products']) && !empty($_SESSION['products']) && count($_SESSION['products']) > 0) ? '' : 'd-none';
?> <div class="bubble-circle add-to-cart-button-bubble 
<?php echo $class ?>" data-monte-b2b-modal-trigger="#monte-b2b-form">
<span class="count-item-b2b"> <?= (isset($_SESSION['products']) ? str_pad(count($_SESSION['products']), 2, '0', STR_PAD_LEFT) : ''); ?> </span>
<img src="<?php echo get_template_directory_uri(); ?>/assets/images/cart.svg" alt="cart">
</div>
<div id="monte-b2b-size" class="monte-b2b-modal">
  <div class="monte-b2b-modal-content">
    <span class="monte-b2b-close">
      <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 16 16" width="32px" height="32px"><path d="M 2.75 2.042969 L 2.042969 2.75 L 2.398438 3.101563 L 7.292969 8 L 2.042969 13.25 L 2.75 13.957031 L 8 8.707031 L 12.894531 13.605469 L 13.25 13.957031 L 13.957031 13.25 L 13.605469 12.894531 L 8.707031 8 L 13.957031 2.75 L 13.25 2.042969 L 8 7.292969 L 3.101563 2.398438 Z"/></svg></span>
      <div class="modal-body-b2b">
        <div class="guide-table-data">
          <table>
            <thead>
              <tr>
                <th colspan="10" class="main-heading"> Regular </th>
              </tr>
              <tr>
                <th>No.</th>
                <th>Size</th>
                <th>M/39 </th>
                <th>M/40 </th>
                <th>L/41 </th>
                <th>L/42 </th>
                <th>XL/43 </th>
                <th>XL/44 </th>
                <th>2XL/45 </th>
                <th>2XL/46 </th>
              </tr>
              <tr>
                <td>1</td>
                <td>NECK/COLLAR</td>
                <td>39</td>
                <td>40</td>
                <td>41</td>
                <td>42</td>
                <td>43</td>
                <td>44</td>
                <td>45</td>
                <td>46</td>
              </tr>
              <tr>
                <td>2</td>
                <td>HALF CHEST</td>
                <td>110</td>
                <td>114</td>
                <td>116</td>
                <td>120</td>
                <td>124</td>
                <td>127</td>
                <td>132</td>
                <td>135</td>
              </tr>
              <tr>
                <td>3</td>
                <td>HALF WAIST</td>
                <td>100</td>
                <td>103</td>
                <td>106</td>
                <td>109</td>
                <td>112</td>
                <td>115</td>
                <td>118</td>
                <td>123</td>
              </tr>
              <tr>
                <td>4</td>
                <td>HALF BOTTOM</td>
                <td>109</td>
                <td>112</td>
                <td>115</td>
                <td>118</td>
                <td>125</td>
                <td>130</td>
                <td>133</td>
                <td>136</td>
              </tr>
              <tr>
                <td>5</td>
                <td>HALF SHOULDER</td>
                <td>46.5</td>
                <td>48</td>
                <td>49.5</td>
                <td>51</td>
                <td>53.5</td>
                <td>55</td>
                <td>56.5</td>
                <td>58</td>
              </tr>
              <tr>
                <td>6</td>
                <td>SLEEVE LENGTH</td>
                <td>66</td>
                <td>66.5</td>
                <td>67</td>
                <td>67.5</td>
                <td>68</td>
                <td>69</td>
                <td>69.5</td>
                <td>70</td>
              </tr>
              <tr>
                <td>7</td>
                <td>BACK LENGTH</td>
                <td>79</td>
                <td>80</td>
                <td>81</td>
                <td>82</td>
                <td>83</td>
                <td>84</td>
                <td>85</td>
                <td>86</td>
              </tr>
            </thead>
          </table>
        </div>
        <div class="guide-table-data">
          <table>
            <thead>
              <tr>
                <th colspan="10" class="main-heading"> slim Fit </th>
              </tr>
              <tr>
                <th>No.</th>
                <th>Size</th>
                <th>S/37 </th>
                <th>S/38 </th>
                <th>M/39 </th>
                <th>M/40 </th>
                <th>L/41 </th>
                <th>L/42 </th>
                <th>XL/43 </th>
                <th>XL/44 </th>
              </tr>
              <tr>
                <td>1</td>
                <td>NECK/COLLAR</td>
                <td>37</td>
                <td>38</td>
                <td>39</td>
                <td>40</td>
                <td>41</td>
                <td>42</td>
                <td>43</td>
                <td>44</td>
              </tr>
              <tr>
                <td>2</td>
                <td>HALF CHEST</td>
                <td>96</td>
                <td>102</td>
                <td>105</td>
                <td>109</td>
                <td>113</td>
                <td>118</td>
                <td>123</td>
                <td>126</td>
              </tr>
              <tr>
                <td>3</td>
                <td>HALF WAIST</td>
                <td>86</td>
                <td>91</td>
                <td>94</td>
                <td>99</td>
                <td>103</td>
                <td>106</td>
                <td>110</td>
                <td>113</td>
              </tr>
              <tr>
                <td>4</td>
                <td>HALF BOTTOM</td>
                <td>96</td>
                <td>100</td>
                <td>104</td>
                <td>108</td>
                <td>112</td>
                <td>117</td>
                <td>122</td>
                <td>125</td>
              </tr>
              <tr>
                <td>5</td>
                <td>HALF SHOULDER</td>
                <td>42.5</td>
                <td>44</td>
                <td>45.5</td>
                <td>47</td>
                <td>48</td>
                <td>49</td>
                <td>51</td>
                <td>52</td>
              </tr>
              <tr>
                <td>6</td>
                <td>SLEEVE LENGTH</td>
                <td>64.5</td>
                <td>65</td>
                <td>65.5</td>
                <td>66</td>
                <td>67</td>
                <td>68</td>
                <td>69</td>
                <td>69</td>
              </tr>
              <tr>
                <td>7</td>
                <td>BACK LENGTH AT CB</td>
                <td>77</td>
                <td>78</td>
                <td>79</td>
                <td>80</td>
                <td>81</td>
                <td>82</td>
                <td>83</td>
                <td>84</td>
              </tr>
            </thead>
          </table>
        </div>
        <div class="guide-table-data">
          <table>
            <thead>
              <tr>
                <th colspan="10" class="main-heading"> Loose fit </th>
              </tr>
              <tr>
                <th>No.</th>
                <th>Size</th>
                <th>M/39-40 </th>
                <th>L/41-42 </th>
                <th>XL/43-44 </th>
                <th>2XL/45-46 </th>
                <th>3XL/47-48 </th>
                <th>4XL/49-50 </th>
              </tr>
              <tr>
                <td>1</td>
                <td>NECK/COLLAR</td>
                <td>40</td>
                <td>42</td>
                <td>44</td>
                <td>46</td>
                <td>48</td>
                <td>50</td>
              </tr>
              <tr>
                <td>2</td>
                <td>HALF CHEST</td>
                <td>118</td>
                <td>126</td>
                <td>133</td>
                <td>140</td>
                <td>147</td>
                <td>154</td>
              </tr>
              <tr>
                <td>3</td>
                <td>HALF WAIST</td>
                <td>113</td>
                <td>121</td>
                <td>130</td>
                <td>138</td>
                <td>146</td>
                <td>153</td>
              </tr>
              <tr>
                <td>4</td>
                <td>HALF BOTTOM</td>
                <td>117</td>
                <td>125</td>
                <td>132</td>
                <td>140</td>
                <td>147</td>
                <td>154</td>
              </tr>
              <tr>
                <td>5</td>
                <td>HALF SHOULDER</td>
                <td>52</td>
                <td>55</td>
                <td>58</td>
                <td>61</td>
                <td>64</td>
                <td>67</td>
              </tr>
              <tr>
                <td>6</td>
                <td>SLEEVE LENGTH</td>
                <td>89</td>
                <td>92</td>
                <td>94</td>
                <td>95</td>
                <td>98</td>
                <td>99</td>
              </tr>
              <tr>
                <td>7</td>
                <td>BACK LENGTH AT CB</td>
                <td>81</td>
                <td>83</td>
                <td>84</td>
                <td>85</td>
                <td>87</td>
                <td>89</td>
              </tr>
            </thead>
          </table>
        </div>
        <div class="guide-table-data">
          <table>
            <thead>
              <tr>
                <th colspan="10" class="main-heading"> CONTEMPORARY(casual) </th>
              </tr>
              <tr>
                <th>No.</th>
                <th>Size</th>
                <th>S/38 </th>
                <th>M/40 </th>
                <th>L/42 </th>
                <th>XL/44 </th>
                <th>2XL/46 </th>
                <th>3XL/48 </th>
              </tr>
              <tr>
                <td>1</td>
                <td>NECK/COLLAR</td>
                <td>39</td>
                <td>41</td>
                <td>43</td>
                <td>45</td>
                <td>47</td>
                <td>49</td>
              </tr>
              <tr>
                <td>2</td>
                <td>HALF CHEST</td>
                <td>110</td>
                <td>108</td>
                <td>118</td>
                <td>126</td>
                <td>134</td>
                <td>142</td>
              </tr>
              <tr>
                <td>3</td>
                <td>HALF WAIST</td>
                <td>94</td>
                <td>102</td>
                <td>110</td>
                <td>118</td>
                <td>124</td>
                <td>132</td>
              </tr>
              <tr>
                <td>4</td>
                <td>HALF BOTTOM</td>
                <td>100</td>
                <td>108</td>
                <td>116</td>
                <td>124</td>
                <td>132</td>
                <td>140</td>
              </tr>
              <tr>
                <td>5</td>
                <td>HALF SHOULDER</td>
                <td>44</td>
                <td>46</td>
                <td>48.5</td>
                <td>51.5</td>
                <td>54.5</td>
                <td>57.5</td>
              </tr>
              <tr>
                <td>6</td>
                <td>SLEEVE LENGTH</td>
                <td>64.5</td>
                <td>66.5</td>
                <td>68.5</td>
                <td>70.5</td>
                <td>72.5</td>
                <td>74.5</td>
              </tr>
              <tr>
                <td>7</td>
                <td>BACK LENGTH AT CB</td>
                <td>74.5</td>
                <td>75.5</td>
                <td>77.5</td>
                <td>79.5</td>
                <td>81</td>
                <td>83</td>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div id="monte-b2b-form" class="monte-b2b-modal">
    <div class="monte-b2b-modal-content">
      <span class="monte-b2b-close">
        <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 16 16" width="32px" height="32px"><path d="M 2.75 2.042969 L 2.042969 2.75 L 2.398438 3.101563 L 7.292969 8 L 2.042969 13.25 L 2.75 13.957031 L 8 8.707031 L 12.894531 13.605469 L 13.25 13.957031 L 13.957031 13.25 L 13.605469 12.894531 L 8.707031 8 L 13.957031 2.75 L 13.25 2.042969 L 8 7.292969 L 3.101563 2.398438 Z"/></svg></span>
        <div class="modal-body-b2b">

          <div class="disflx">
            <div class="mont-b2b-form">
              <h4>Shipping Address</h4>
              <form class="order-form">
                <div class="form-group">
                  <label for="companyName">Company Name</label>
                  <input type="text" class="form-control" id="companyName" placeholder="Company Name" name="companyname" data-required="Please enter your company name">
                </div>
                <div class="form-group">
                  <label for="DeliveryAddress">Delivery Address</label>
                  <input type="text" class="form-control" id="DeliveryAddress" placeholder="Delivery Address" name="deliveryaddress" data-required="Please enter your delivery address">
                </div>
                <div class="form-group">
                  <label for="Country">Country</label>
                  <input type="text" class="form-control" id="Country" placeholder="Country" name="country" data-required="Please enter your country">
                </div>
                <div class="form-group">
                  <label for="postbox">Post Box</label>
                  <input type="text" class="form-control" id="postbox" placeholder="Post Box" name="postbox" data-required="Please enter your post box">
                </div>
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" placeholder="Email" name="email" data-required="Please enter your email">
                </div>
                <div class="form-group">
                  <label for="contactperson">Contact Person</label>
                  <input type="text" class="form-control" id="contactperson" placeholder="Contact Person" name="contactperson" data-required="Please enter your contact info">
                </div>
                <div class="form-group">
                  <label for="mobilenumber">Mobile Number</label>
                  <input type="text" class="form-control" id="mobilenumber" placeholder="Mobile Number" name="mobilenumber" data-required="Please enter your mobile number">
                </div>
              </form>
            </div>
            <div class="order-data-b2b-monte">
              <h4>B2B Cart Items</h4>
              <div class="cart-data"></div>
            </div>
          </div>
          <div class="footer-model-b2b-monte">
            <button class="button btn-monte order-btn">Order now</button>
          </div>
        </div>
      </div>
    </div>
  </div>



