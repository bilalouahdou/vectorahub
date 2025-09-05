/**
 * Hardened Billing JavaScript
 * 
 * This file handles the billing flow with:
 * - Defensive JSON parsing
 * - CSRF token validation
 * - Proper error handling
 * - Content-Type verification
 */

/**
 * Wait for Stripe.js to load with timeout
 * @param {number} maxMs - Maximum time to wait in milliseconds
 * @returns {Promise<Stripe>} - Promise that resolves to Stripe instance
 */
async function waitForStripe(maxMs = 3000) {
    const start = performance.now();
    while (!window.Stripe) {
        if (performance.now() - start > maxMs) {
            throw new Error('Stripe.js not loaded within timeout period');
        }
        await new Promise(r => setTimeout(r, 100));
    }
    
    // Validate publishable key
    if (!STRIPE_PUBLISHABLE_KEY) {
        throw new Error('Stripe publishable key is missing');
    }
    
    return Stripe(STRIPE_PUBLISHABLE_KEY);
}

// Initialize billing system with proper Stripe loading
async function initializeBilling() {
    let stripe;
    
    try {
        // Wait for Stripe to load
        stripe = await waitForStripe();
        console.log('Stripe initialized successfully:', stripe);
    } catch (error) {
        console.error('Stripe initialization failed:', error.message);
        showError('Stripe failed to load. Check console for details.');
        return;
    }
    
    const csrfToken = document.getElementById('csrf_token')?.value || '';
    
    // Debug info (remove in production)
    console.log('CSRF Token available:', !!csrfToken);
    console.log('Publishable Key:', STRIPE_PUBLISHABLE_KEY);

    // Handle buy now buttons
    document.querySelectorAll('.buy-now-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const planId = this.dataset.planId;
            const planName = this.dataset.planName;
            const planPrice = this.dataset.planPrice;
            
            if (!planId) {
                showError('Invalid plan configuration');
                return;
            }
            
            // Show loading state
            const btnText = this.querySelector('.btn-text');
            const spinner = this.querySelector('.spinner-border');
            const originalText = btnText.textContent;
            
            btnText.textContent = 'Processing...';
            spinner.classList.remove('d-none');
            this.disabled = true;
            
            try {
                // Create checkout session via hardened API with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 7000);
                
                const response = await fetch('/api/create_checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        plan_id: planId,
                        csrf_token: csrfToken
                    }),
                    credentials: 'same-origin', // Ensures PHPSESSID cookie is sent
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                // Verify Content-Type header before parsing JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Non-JSON response from server');
                }
                
                const result = await response.json();
                
                console.log('Checkout response:', result);
                
                if (result.success && result.session_id) {
                    // Valid Stripe session - redirect to checkout
                    const { error } = await stripe.redirectToCheckout({
                        sessionId: result.session_id
                    });
                    
                    if (error) {
                        console.error('Stripe error:', error);
                        showError('Payment failed: ' + error.message);
                    }
                } else {
                                    // Handle error response
                const errorMessage = result.error || 'Failed to create checkout session';
                
                // Handle CSRF token errors specifically
                if (errorMessage.includes('CSRF token invalid') || response.status === 419) {
                    showError('Session expired, reloading...');
                    setTimeout(() => location.reload(), 1500);
                    return;
                }
                
                showError(errorMessage);
                }
            } catch (error) {
                console.error('Checkout Error Details:', {
                    error: error,
                    message: error.message,
                    stack: error.stack
                });
                
                if (error.name === 'AbortError') {
                    showError('Request timed out. Please try again.');
                } else if (error.message === 'Non-JSON response from server') {
                    showError('Server returned invalid response. Please try again.');
                } else if (error.message.includes('Failed to fetch')) {
                    showError('Network error. Please check your connection and try again.');
                } else {
                    showError('Payment system error. Please try again.');
                }
            } finally {
                // Reset button state
                btnText.textContent = originalText;
                spinner.classList.add('d-none');
                this.disabled = false;
            }
        });
    });

    // Handle activate free plan button
    document.querySelectorAll('.activate-free-plan-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const planId = this.dataset.planId;
            
            if (!planId) {
                showError('Invalid plan configuration');
                return;
            }
            
            // Show loading state
            const btnText = this.querySelector('.btn-text');
            const spinner = this.querySelector('.spinner-border');
            const originalText = btnText.textContent;
            
            btnText.textContent = 'Activating...';
            spinner.classList.remove('d-none');
            this.disabled = true;
            
            try {
                const response = await fetch('php/activate_free_plan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        plan_id: planId,
                        csrf_token: csrfToken
                    }),
                    credentials: 'same-origin' // Ensures PHPSESSID cookie is sent
                });
                
                // Verify Content-Type header
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Non-JSON response from server');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Reload page to reflect changes
                    window.location.href = 'billing?activation_success=true';
                } else {
                    const errorMessage = result.error || 'Failed to activate free plan';
                    
                    // Handle CSRF token errors specifically
                    if (errorMessage.includes('CSRF token invalid') || response.status === 419) {
                        showError('Session expired, reloading...');
                        setTimeout(() => location.reload(), 1500);
                        return;
                    }
                    
                    showError(errorMessage);
                }
            } catch (error) {
                console.error('Free plan activation error:', error);
                
                if (error.message === 'Non-JSON response from server') {
                    showError('Server returned invalid response. Please try again.');
                } else {
                    showError('Network error. Please try again.');
                }
            } finally {
                // Reset button state (though page reload will happen on success)
                btnText.textContent = originalText;
                spinner.classList.add('d-none');
                this.disabled = false;
            }
        });
    });
}

/**
 * Show error message to user
 * @param {string} message - Error message to display
 */
function showError(message) {
    // Create or update error message container
    let errorContainer = document.getElementById('errorContainer');
    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.id = 'errorContainer';
        errorContainer.className = 'alert alert-danger mt-3';
        errorContainer.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>
            <span id="errorMessage"></span>
        `;
        
        // Insert at the top of the container
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(errorContainer, container.firstChild);
        }
    }
    
    // Update error message
    const errorMessage = errorContainer.querySelector('#errorMessage');
    if (errorMessage) {
        errorMessage.textContent = message;
    }
    
    // Show the error container
    errorContainer.style.display = 'block';
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        errorContainer.style.display = 'none';
    }, 10000);
}

/**
 * Show success message to user
 * @param {string} message - Success message to display
 */
function showSuccess(message) {
    // Create or update success message container
    let successContainer = document.getElementById('successContainer');
    if (!successContainer) {
        successContainer = document.createElement('div');
        successContainer.id = 'successContainer';
        successContainer.className = 'alert alert-success mt-3';
        successContainer.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <span id="successMessage"></span>
        `;
        
        // Insert at the top of the container
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(successContainer, container.firstChild);
        }
    }
    
    // Update success message
    const successMessage = successContainer.querySelector('#successMessage');
    if (successMessage) {
        successMessage.textContent = message;
    }
    
    // Show the success container
    successContainer.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        successContainer.style.display = 'none';
    }, 5000);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize billing system with proper error handling
    initializeBilling().catch(error => {
        console.error('Billing initialization failed:', error);
        showError('Payment system initialization failed. Please refresh the page.');
    });
});
