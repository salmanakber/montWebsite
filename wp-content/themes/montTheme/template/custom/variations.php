<div class="wrap">
        <h1>Variation Settings</h1>
        <button id="add-new-variation" class="button button-primary">Add New</button>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Attributes</th>
                    <th>Shirt Length</th>
                    <th>Sleeve Length</th>
                    <th>Shoulder</th>
                    <th>Half Chest</th>
                    <th>Half Waist</th>
                    <th>Half Bottom</th>
                    <th>Armhole</th>
                    <th>Neck/Collar</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="variation-settings-table">
                <!-- Table content will be loaded via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Popup for adding/editing variation settings -->
    <div id="variation-settings-popup" class="variation-settings-popup">
        <div class="variation-settings-popup-content">
            <span class="close">&times;</span>
            <h2>Add/Edit Variation Setting</h2>
            <form id="variation-settings-form">
                <div id="product-attributes">
                    <!-- Product attributes will be loaded here -->
                </div>
                <div class="number-field">
                    <label for="shirt-length">Shirt Length</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="shirt-length" name="shirt_length" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="sleeve-length">Sleeve Length</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="sleeve-length" name="sleeve_length" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="shoulder">Shoulder</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="shoulder" name="shoulder" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="half-chest">Half Chest</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="half-chest" name="half_chest" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="half-waist">Half Waist</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="half-waist" name="half_waist" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="half-bottom">Half Bottom</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="half-bottom" name="half_bottom" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="armhole">Armhole</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="armhole" name="armhole" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <div class="number-field">
                    <label for="neck-collar">Neck/Collar</label>
                    <button type="button" class="minus">-</button>
                    <input type="number" id="neck-collar" name="neck_collar" step="0.1" required>
                    <button type="button" class="plus">+</button>
                </div>
                <button type="submit" class="button button-primary">Save</button>
            </form>
        </div>
    </div>