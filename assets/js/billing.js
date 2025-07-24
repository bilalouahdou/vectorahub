class Billing {
  constructor() {
    this.stripe = null
    this.elements = null
    this.card = null
    this.setupStripe()
  }

  setupStripe() {
    const stripePublishableKey = this.getStripePublishableKey()
    this.stripe = Stripe(stripePublishableKey)
    this.elements = this.stripe.elements()
    this.card = this.elements.create("card")
    this.card.mount("#card-element")

    this.card.on("change", (event) => {
      const displayError = document.getElementById("card-errors")
      if (event.error) {
        displayError.textContent = event.error.message
      } else {
        displayError.textContent = ""
      }
    })
  }

  getStripePublishableKey() {
    // You'll need to make this available from your PHP config
    // For now, replace with your actual Stripe publishable key
    return "<?php echo $STRIPE_PUBLISHABLE_KEY; ?>" // This would need to be rendered by PHP
  }

  async createPaymentMethod() {
    const { paymentMethod, error } = await this.stripe.createPaymentMethod({
      type: "card",
      card: this.card,
    })

    if (error) {
      // Handle errors (e.g., display to the user)
      console.error(error)
      return null
    } else {
      return paymentMethod
    }
  }

  async handlePayment(paymentMethodId, amount, currency) {
    try {
      const response = await fetch("/process-payment", {
        // Replace with your server endpoint
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          paymentMethodId: paymentMethodId,
          amount: amount,
          currency: currency,
        }),
      })

      const data = await response.json()

      if (data.success) {
        // Payment successful
        console.log("Payment successful!")
        return true
      } else {
        // Payment failed
        console.error("Payment failed:", data.error)
        return false
      }
    } catch (error) {
      console.error("Error processing payment:", error)
      return false
    }
  }
}

// Example usage (assuming you have a button with id="pay-button")
document.addEventListener("DOMContentLoaded", () => {
  const billing = new Billing()
  const payButton = document.getElementById("pay-button")

  if (payButton) {
    payButton.addEventListener("click", async () => {
      const paymentMethod = await billing.createPaymentMethod()

      if (paymentMethod) {
        // Replace with your actual amount and currency
        const amount = 1000 // Example: $10.00
        const currency = "usd"

        const success = await billing.handlePayment(paymentMethod.id, amount, currency)

        if (success) {
          // Redirect or show success message
          alert("Payment successful!")
        } else {
          // Show error message
          alert("Payment failed.")
        }
      } else {
        alert("Error creating payment method.")
      }
    })
  } else {
    console.warn("Pay button not found.")
  }
})
