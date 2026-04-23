// GLS Parcel Shop: Store API-based show/hide with loading states and free shipping check + AJAX HTML loading
(function() {
    var isApiCallInProgress = false;
    var isInitialized = false;

    /**
     * Returns the rate_id of the currently selected shipping method radio button in the WooCommerce Blocks checkout.
     * Used to determine which shipping method is active for conditional logic.
     */
    function getSelectedShippingMethodId() {
        const shippingOptions = document.querySelectorAll('.wp-block-woocommerce-checkout-shipping-methods-block input[type="radio"]');
        for (let radio of shippingOptions) {
            if (radio.checked) {
                return radio.value;
            }
        }
        return null;
    }

    /**
     * Sets a loading state (opacity and pointer events) on the shipping methods block during async operations.
     * Prevents user interaction while shipping rates are being checked.
     */
    function setShippingMethodsLoadingState(loading) {
        const shippingBlock = document.querySelector('.wp-block-woocommerce-checkout-shipping-methods-block');
        if (shippingBlock) {
            if (loading) {
                shippingBlock.style.pointerEvents = 'none';
                shippingBlock.style.opacity = '0.5';
            } else {
                shippingBlock.style.pointerEvents = '';
                shippingBlock.style.opacity = '';
            }
        }
    }

    /**
     * Calls the WooCommerce Store API to fetch current shipping rates and their meta data.
     * Determines if the selected method supports GLS Parcel Shop.
     * Returns a Promise resolving to an object with the boolean result.
     */
    function checkShippingRates() {
        // Check if we have the required variables
        if (typeof wc_gls_store_api === 'undefined' || !wc_gls_store_api.store_api_url || !wc_gls_store_api.nonce) {
            return Promise.resolve({ selectedMethodSupportsParcelShop: false });
        }

        // Use Store API to get cart data including shipping rates
        return fetch(wc_gls_store_api.store_api_url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Nonce': wc_gls_store_api.nonce
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            
            let selectedMethodSupportsParcelShop = false;
            const selectedMethodId = getSelectedShippingMethodId();
            
            if (data.shipping_rates && Array.isArray(data.shipping_rates)) {
                // Check each package's shipping rates
                data.shipping_rates.forEach(package => {
                    if (package.shipping_rates && Array.isArray(package.shipping_rates)) {
                        package.shipping_rates.forEach(rate => {
                            // Check if selected method supports GLS Parcel Shop
                            if (selectedMethodId && rate.rate_id === selectedMethodId) {
                                // Check if this rate has gls_parcel_shop_enabled in meta_data
                                if (rate.meta_data && Array.isArray(rate.meta_data)) {
                                    const glsMeta = rate.meta_data.find(meta => meta.key === 'gls_parcel_shop_enabled');
                                    if (glsMeta && glsMeta.value === '1') {
                                        selectedMethodSupportsParcelShop = true;
                                    }
                                }
                            }
                        });
                    }
                });
            }
            
            return { selectedMethodSupportsParcelShop };
        })
        .catch(error => {
            return { selectedMethodSupportsParcelShop: false };
        });
    }

    /**
     * Shows or hides the GLS Parcel Shop block based on the provided boolean.
     * Used to conditionally display the block depending on shipping method logic.
     */
    function updateParcelShopBlockVisibility(show) {
        var block = document.querySelector('.gls-parcel-shop-block');
        if (!block) {
            return;
        }
        
        block.style.display = show ? '' : 'none';
    }



    /**
     * Loads the GLS Parcel Shop HTML content via AJAX and inserts it into the block.
     * Uses the wgl_get_parcel_shop_block_html AJAX endpoint to fetch the HTML.
     */
    async function loadParcelShopHtml() {
        var block = document.querySelector('.gls-parcel-shop-block');
        if (!block) {
            return;
        }

        // Check if we have the required variables
        if (typeof checkout_vars === 'undefined' || !checkout_vars.ajax_url || !checkout_vars.ajax_nonce) {
            return;
        }

        // Get address values from input fields using ID selectors
        const addressData = {};
        const addressFields = ['country', 'state', 'address_1', 'address_2', 'postcode', 'city'];

        for (const field of addressFields) {
            let value = '';
            
            // Priority 1: Try shipping address fields with shipping- prefixed IDs (e.g., shipping-country)
            const shippingElement = document.getElementById(`shipping-${field}`);
            if (shippingElement && shippingElement.value) {
                value = shippingElement.value;
            }
            
            // Priority 2: Try billing address fields with billing- prefixed IDs (e.g., billing-country)
            if (!value) {
                const billingElement = document.getElementById(`billing-${field}`);
                if (billingElement && billingElement.value) {
                    value = billingElement.value;
                }
            }
            
            if (value) {
                addressData[field] = value;
            }
        }

        // Get the selected payment method
        let selectedPaymentMethod = 'other';
        
        // Check for WooCommerce Blocks payment method options
        const paymentMethodOptions = document.querySelectorAll('input[name="radio-control-wc-payment-method-options"]:checked');
        if (paymentMethodOptions.length > 0) {
            selectedPaymentMethod = paymentMethodOptions[0].value;
        } else {
            // Check for saved tokens
            const savedTokens = document.querySelectorAll('input[name="radio-control-wc-payment-method-saved-tokens"]:checked');
            if (savedTokens.length > 0) {
                selectedPaymentMethod = 'saved_token';
            } else {
                // Fallback to legacy payment method
                const legacyPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
                if (legacyPaymentMethod) {
                    selectedPaymentMethod = legacyPaymentMethod.value;
                }
            }
        }
        
        addressData.payment_method = selectedPaymentMethod;

        try {
            const postData = {
                action: 'wgl_get_parcel_shop_block_html',
                security: checkout_vars.ajax_nonce,
                ...addressData
            };

            const response = await fetch(checkout_vars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(postData)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }

            const data = await response.json();
            
            if (data.success && data.data && data.data.html) {
                block.innerHTML = data.data.html;
                return true;
            } else {
                return false;
            }
        } catch (error) {
            return false;
        }
    }

    /**
     * Handles changes to the selected shipping method.
     * Triggers a check for GLS Parcel Shop support, then updates block visibility.
     * Also manages loading state to prevent concurrent API calls.
     */
    function onShippingMethodChange() {
        if (isApiCallInProgress) {
            return;
        }

        isApiCallInProgress = true;
        setShippingMethodsLoadingState(true);

        checkShippingRates().then(({ selectedMethodSupportsParcelShop }) => {
            // Check if selected method supports parcel shop
            if (selectedMethodSupportsParcelShop) {
                updateParcelShopBlockVisibility(true);
                // Load the HTML content for the parcel shop block
                loadParcelShopHtml();
            } else {
                updateParcelShopBlockVisibility(false);
            }
        }).finally(() => {
            isApiCallInProgress = false;
            setShippingMethodsLoadingState(false);
        });
    }

    /**
     * Handles changes to the selected payment method.
     * Refreshes the parcel shop widget with updated payment method data.
     * Only runs if the parcel shop block is visible.
     */
    async function onPaymentMethodChange() {
        // Only refresh if the parcel shop block exists and is visible
        if (document.querySelector('.gls-parcel-shop-block') && 
            document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
            
            // Wait for Store API requests to complete before refreshing
            await waitForStoreApiRequests();
            
            // Small additional delay to ensure everything is processed
            setTimeout(() => {
                loadParcelShopHtml();
            }, 200);
        }
    }

    /**
     * Waits (polls) for the WooCommerce Blocks shipping methods block to be present in the DOM.
     * Resolves when the block is found or after a timeout. Ensures logic runs only after blocks are loaded.
     */
    function waitForWooCommerceBlocks() {
        return new Promise((resolve) => {
            // Check if WooCommerce Blocks are already loaded
            if (document.querySelector('.wp-block-woocommerce-checkout-shipping-methods-block')) {
                resolve();
                return;
            }

            let attempts = 0;
            const maxAttempts = 150; // 15 seconds at 100ms intervals
            const interval = setInterval(() => {
                attempts++;
                
                if (document.querySelector('.wp-block-woocommerce-checkout-shipping-methods-block')) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    resolve();
                }
            }, 100);
        });
    }

    /**
     * Waits (polls) for shipping method radio buttons to be present in the DOM.
     * Resolves when at least one is found or after a timeout. Ensures logic runs only after methods are loaded.
     */
    function waitForShippingMethods() {
        return new Promise((resolve) => {
            // Check if shipping methods are already available
            if (document.querySelectorAll('.wp-block-woocommerce-checkout-shipping-methods-block input[type="radio"]').length > 0) {
                resolve();
                return;
            }

            let attempts = 0;
            const maxAttempts = 150; // 15 seconds at 100ms intervals
            const interval = setInterval(() => {
                attempts++;
                
                if (document.querySelectorAll('.wp-block-woocommerce-checkout-shipping-methods-block input[type="radio"]').length > 0) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    resolve();
                }
            }, 100);
        });
    }

    /**
     * Waits for parcel shop session to be updated after selection/removal.
     * Uses polling to check if the session has been updated by looking for changes in the DOM.
     * Resolves when the update is detected or after a timeout.
     */
    function waitForParcelShopUpdate() {
        return new Promise((resolve) => {
            // Store initial state
            const initialCheckIcon = document.querySelector('.parcel-shop .wgl-check-icon');
            const initialState = !!initialCheckIcon;
            
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds at 100ms intervals
            const interval = setInterval(() => {
                attempts++;
                
                const currentCheckIcon = document.querySelector('.parcel-shop .wgl-check-icon');
                const currentState = !!currentCheckIcon;
                
                // If the state has changed, the update is complete
                if (currentState !== initialState) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    resolve(); // Resolve anyway to prevent hanging
                }
            }, 100);
        });
    }

    /**
     * Waits for WooCommerce checkout update to complete.
     * Uses polling to check if the checkout has finished updating by monitoring loading states.
     * Resolves when the update is complete or after a timeout.
     */
    function waitForCheckoutUpdate() {
        return new Promise((resolve) => {
            // Check if checkout is already in a stable state
            const isCurrentlyLoading = document.querySelector('.woocommerce-checkout-processing') || 
                                     document.querySelector('.blockUI') ||
                                     document.querySelector('.wc-block-components-loading-mask');
            
            if (!isCurrentlyLoading) {
                resolve();
                return;
            }

            let attempts = 0;
            const maxAttempts = 100; // 10 seconds at 100ms intervals
            const interval = setInterval(() => {
                attempts++;
                
                const isLoading = document.querySelector('.woocommerce-checkout-processing') || 
                                document.querySelector('.blockUI') ||
                                document.querySelector('.wc-block-components-loading-mask');
                
                // If no loading indicators are present, the update is complete
                if (!isLoading) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    resolve(); // Resolve anyway to prevent hanging
                }
            }, 100);
        });
    }

    /**
     * Waits for WooCommerce Store API requests to complete.
     * Monitors for active fetch requests to /wc/store/v1/checkout and waits for them to finish.
     * Resolves when no active requests are found or after a timeout.
     */
    function waitForStoreApiRequests() {
        return new Promise((resolve) => {
            // Check if there are any active fetch requests to the checkout endpoint
            const checkForActiveRequests = () => {
                // Method 1: Check WooCommerce Blocks Redux store states (most reliable)
                if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                    try {
                        const cartStore = wp.data.select('wc/store/cart');
                        const checkoutStore = wp.data.select('wc/store/checkout');
                        const paymentStore = wp.data.select('wc/store/payment');
                        
                        if (cartStore) {
                            // Check if cart is loading or customer data is updating
                            if (cartStore.isCartDataStale() || cartStore.isUpdatingCustomerData()) {
                                return true;
                            }
                        }
                        
                        if (checkoutStore) {
                            // Check if checkout is processing or calculating
                            if (checkoutStore.isProcessing() || checkoutStore.isCalculating()) {
                                return true;
                            }
                        }
                        
                        if (paymentStore) {
                            // Check if payment is processing
                            if (paymentStore.isPaymentProcessing()) {
                                return true;
                            }
                        }
                    } catch (error) {
                        // If Redux store is not available, fall back to DOM checks
                    }
                }
                
                // Method 2: Fallback to DOM-based loading indicators
                const isLoading = document.querySelector('.wc-block-components-loading-mask') ||
                                document.querySelector('.wc-block-components-spinner') ||
                                document.querySelector('[data-automation-id*="loading"]') ||
                                document.querySelector('.is-loading') ||
                                document.querySelector('.woocommerce-checkout-processing') ||
                                document.querySelector('.blockUI');
                
                return isLoading;
            };

            // If no active requests, resolve immediately
            if (!checkForActiveRequests()) {
                resolve();
                return;
            }

            let attempts = 0;
            const maxAttempts = 150; // 15 seconds at 100ms intervals
            const interval = setInterval(() => {
                attempts++;
                
                // If no loading indicators are present, the requests are complete
                if (!checkForActiveRequests()) {
                    clearInterval(interval);
                    resolve();
                } else if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    resolve(); // Resolve anyway to prevent hanging
                }
            }, 100);
        });
    }

    /**
     * Sets up event listeners for the GLS Parcel Shop functionality.
     * Uses event delegation to handle dynamically loaded content.
     */
    function setupParcelShopEventListeners() {
        // Event delegation for parcel shop checkbox clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.parcel-shop .wgl-checkbox')) {
                e.preventDefault();
                // Check if this is to remove a selected parcel shop
                const isChecked = document.querySelector('.parcel-shop .wgl-check-icon');
                if (isChecked) {
                    // This will remove the parcel shop - refresh HTML after removal
                    if (typeof GLSLabelsCheckout !== 'undefined' && GLSLabelsCheckout.loadScriptAndOpenModal) {
                        GLSLabelsCheckout.loadScriptAndOpenModal();
                        // Refresh the block HTML after parcel shop removal
                        waitForParcelShopUpdate().then(() => {
                            if (document.querySelector('.gls-parcel-shop-block') && 
                                document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
                                loadParcelShopHtml();
                            }
                        });
                    }
                } else {
                    // This will open the modal to select a parcel shop
                    if (typeof GLSLabelsCheckout !== 'undefined' && GLSLabelsCheckout.loadScriptAndOpenModal) {
                        GLSLabelsCheckout.loadScriptAndOpenModal();
                    }
                }
            }
        });

        // Event delegation for parcel shop close button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.wgl-parcel-select-close')) {
                e.preventDefault();
                if (typeof GLSLabelsCheckout !== 'undefined' && GLSLabelsCheckout.closeModal) {
                    GLSLabelsCheckout.closeModal();
                }
            }
        });

        // Event delegation for view more details
        document.addEventListener('click', function(e) {
            if (e.target.closest('.wgl-view-more')) {
                e.preventDefault();
                if (typeof GLSLabelsCheckout !== 'undefined' && GLSLabelsCheckout.viewParcelShopDetails) {
                    GLSLabelsCheckout.viewParcelShopDetails(e);
                }
            }
        });

        // Event delegation for parcel shop selection
        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-parcel-details]')) {
                e.preventDefault();
                if (typeof GLSLabelsCheckout !== 'undefined' && GLSLabelsCheckout.onSelect) {
                    GLSLabelsCheckout.onSelect(e.target.closest('[data-parcel-details]').dataset.parcelDetails);
                    // Refresh the block HTML after parcel shop selection
                    waitForParcelShopUpdate().then(() => {
                        if (document.querySelector('.gls-parcel-shop-block') && 
                            document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
                            loadParcelShopHtml();
                        }
                    });
                }
            }
        });

        // Event delegation for proxy parcel shop clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.wgl-proxy-parcel-shop, .wgl-proxy-parcel-name')) {
                e.preventDefault();
                const checkbox = document.querySelector('.wgl-checkbox-wrapper.parcel-shop .wgl-checkbox');
                if (checkbox) {
                    checkbox.click();
                }
            }
        });

        // Event delegation for address field changes
        document.addEventListener('change', function(e) {
            // Check if the changed element is an address field
            const isAddressField = e.target.matches('input[name*="address"], input[name*="city"], input[name*="postcode"], select[name*="country"], select[name*="state"]') ||
                                 e.target.matches('.wc-block-components-address-form input, .wc-block-components-address-form select') ||
                                 e.target.matches('[data-automation-id*="address"], [data-automation-id*="city"], [data-automation-id*="postcode"], [data-automation-id*="country"], [data-automation-id*="state"]');
            
            if (isAddressField) {
                // Refresh parcel shop HTML when address fields change
                if (document.querySelector('.gls-parcel-shop-block') && 
                    document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
                    // Small delay to ensure the address change has been processed
                    setTimeout(() => {
                        loadParcelShopHtml();
                    }, 500);
                }
            }
        });

        // Event delegation for payment method changes
        document.addEventListener('change', function(e) {
            // Check if the changed element is a payment method radio button
            const isPaymentMethod = e.target.matches('input[name="payment_method"]') ||
                                  e.target.matches('input[name="radio-control-wc-payment-method-options"]') ||
                                  e.target.matches('input[name="radio-control-wc-payment-method-saved-tokens"]') ||
                                  e.target.matches('.wc-block-components-payment-method-options input[type="radio"]') ||
                                  e.target.matches('[data-automation-id*="payment"] input[type="radio"]');
            
            if (isPaymentMethod) {
                onPaymentMethodChange();
            }
        });

        // Also listen for input events on address fields for real-time updates
        document.addEventListener('input', function(e) {
            // Check if the input element is an address field
            const isAddressField = e.target.matches('input[name*="address"], input[name*="city"], input[name*="postcode"]') ||
                                 e.target.matches('.wc-block-components-address-form input') ||
                                 e.target.matches('[data-automation-id*="address"], [data-automation-id*="city"], [data-automation-id*="postcode"]');
            
            if (isAddressField) {
                // Use debouncing to avoid too many requests
                clearTimeout(e.target.addressChangeTimeout);
                e.target.addressChangeTimeout = setTimeout(() => {
                    if (document.querySelector('.gls-parcel-shop-block') && 
                        document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
                        loadParcelShopHtml();
                    }
                }, 500); // 1 second delay for input events
            }
        });
    }

    /**
     * Utility to run a function when the DOM is ready (DOMContentLoaded or already loaded).
     * Used to initialize the GLS logic at the right time.
     */
    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    /**
     * Main initialization function for the GLS Parcel Shop block logic.
     * Waits for WooCommerce Blocks and shipping methods, sets up event listeners, and triggers initial state check.
     * Ensures the logic is only initialized once per page load.
     */
    function init() {
        if (isInitialized) {
            return;
        }

        // Set up event listeners for parcel shop functionality
        setupParcelShopEventListeners();
        
        // Wait for WooCommerce Blocks to be fully loaded and rendered
        waitForWooCommerceBlocks()
            .then(() => waitForShippingMethods())
            .then(() => {
                
                // Check initial state
                onShippingMethodChange();
                
                // Listen for shipping method changes
                document.addEventListener('change', function(e) {
                    if (e.target.matches('.wp-block-woocommerce-checkout-shipping-methods-block input[type="radio"]')) {
                        onShippingMethodChange();
                    }
                });

                // Listen for WooCommerce Blocks cart events that might affect checkout
                document.addEventListener('wc-blocks_added_to_cart', async function() {
                    // Wait for Store API requests to complete before refreshing
                    await waitForStoreApiRequests();
                    
                    // Refresh parcel shop widget when cart is updated
                    setTimeout(() => {
                        if (document.querySelector('.gls-parcel-shop-block') && 
                            document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
                            loadParcelShopHtml();
                        }
                    }, 200);
                });

                // Listen for WooCommerce Blocks store sync events
                document.addEventListener('wc-blocks_store_sync_required', async function() {
                    // Wait for Store API requests to complete before refreshing
                    await waitForStoreApiRequests();
                    
                    // Refresh parcel shop widget when store data is synced
                    setTimeout(() => {
                        if (document.querySelector('.gls-parcel-shop-block') && 
                            document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
                            loadParcelShopHtml();
                        }
                    }, 200);
                });

                isInitialized = true;
            })
            .catch(error => {
                console.error('[GLS DEBUG] Initialization error:', error);
            });
    }

    // Listen for WooCommerce's update_checkout event (triggered when checkout is refreshed)
    // This listener is set up immediately and works independently of the init function
    // Using jQuery since update_checkout is a jQuery event, not a native DOM event
    jQuery(document.body).on('update_checkout', function() {
        // Only refresh if the block exists and is visible
        if (document.querySelector('.gls-parcel-shop-block') && 
            document.querySelector('.gls-parcel-shop-block').style.display !== 'none') {
            waitForCheckoutUpdate().then(() => {
                loadParcelShopHtml();
            });
        }
    });

    onReady(init);
})(); 