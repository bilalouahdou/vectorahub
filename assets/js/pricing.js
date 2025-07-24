// Pricing page JavaScript functionality
document.addEventListener("DOMContentLoaded", () => {
  // Highlight recommended plan with subtle animation
  const recommendedCard = document.querySelector(".border-accent")
  if (recommendedCard) {
    // Add a subtle glow effect on hover
    recommendedCard.addEventListener("mouseenter", function () {
      this.style.boxShadow = "0 0 20px rgba(0, 255, 209, 0.3)"
      this.style.transform = "translateY(-5px)"
      this.style.transition = "all 0.3s ease"
    })

    recommendedCard.addEventListener("mouseleave", function () {
      this.style.boxShadow = ""
      this.style.transform = ""
    })
  }

  // Sample coupon code click handlers
  document.querySelectorAll(".coupon-sample").forEach((badge) => {
    badge.addEventListener("click", function () {
      const couponInput = document.getElementById("couponCode")
      couponInput.value = this.textContent
      couponInput.focus()
    })
  })

  // Coupon form handling
  const couponForm = document.getElementById("couponForm")
  const couponInput = document.getElementById("couponCode")
  const couponMessage = document.getElementById("couponMessage")

  if (couponForm) {
    couponForm.addEventListener("submit", async (e) => {
      e.preventDefault()

      const couponCode = couponInput.value.trim().toUpperCase()
      if (!couponCode) return

      // Show loading state
      const submitBtn = couponForm.querySelector('button[type="submit"]')
      const originalBtnText = submitBtn.textContent
      submitBtn.disabled = true
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking...'

      try {
        const response = await fetch("php/check_coupon.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            coupon_code: couponCode,
            csrf_token: document.querySelector('input[name="csrf_token"]').value,
          }),
        })

        const result = await response.json()

        if (result.valid) {
          if (result.type === "discount") {
            // Handle discount coupon
            couponMessage.innerHTML = `<div class="alert alert-success">
              <strong>✅ ${result.message}</strong><br>
              <small>${result.description}</small>
            </div>`

            // Update prices with discount (existing functionality)
            updatePricesWithDiscount(result.discount_percent)
          } else if (result.type === "free_plan" || result.type === "free_upgrade") {
            // Handle free plan/upgrade coupon
            couponMessage.innerHTML = `<div class="alert alert-success">
              <strong>🎉 ${result.message}</strong><br>
              <small>${result.description}</small><br>
              <button class="btn btn-success btn-sm mt-2" onclick="applyFreeCoupon('${couponCode}')">
                Activate Free Access
              </button>
            </div>`
          }
        } else {
          // Show error message
          couponMessage.innerHTML = `<div class="alert alert-danger">${result.message || "Invalid coupon code"}</div>`
        }
      } catch (error) {
        console.error("Error checking coupon:", error)
        couponMessage.innerHTML = '<div class="alert alert-danger">Error checking coupon. Please try again.</div>'
      } finally {
        // Reset button state
        submitBtn.disabled = false
        submitBtn.textContent = originalBtnText
      }
    })
  }

  // Add smooth scroll to FAQ section when coming from external links
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get("section") === "faq") {
    const faqSection = document.getElementById("pricingFAQ")
    if (faqSection) {
      faqSection.scrollIntoView({ behavior: "smooth" })
    }
  }

  // Track plan selection clicks for analytics (optional)
  const planButtons = document.querySelectorAll('a[href*="billing.php?plan_id="]')
  planButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const planId = new URL(this.href).searchParams.get("plan_id")
      const planName = this.closest(".feature-card").querySelector("h3").textContent.trim()

      // You can add analytics tracking here
      console.log(`Plan selected: ${planName} (ID: ${planId})`)

      // Optional: Add a brief loading state
      const originalText = this.textContent
      this.textContent = "Loading..."
      this.classList.add("disabled")

      // Reset after a short delay (the page will navigate anyway)
      setTimeout(() => {
        this.textContent = originalText
        this.classList.remove("disabled")
      }, 1000)
    })
  })

  // Add comparison highlighting when hovering over cards
  const planCards = document.querySelectorAll(".feature-card")
  planCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      // Slightly fade other cards
      planCards.forEach((otherCard) => {
        if (otherCard !== this) {
          otherCard.style.opacity = "0.7"
          otherCard.style.transition = "opacity 0.3s ease"
        }
      })
    })

    card.addEventListener("mouseleave", () => {
      // Restore opacity to all cards
      planCards.forEach((otherCard) => {
        otherCard.style.opacity = "1"
      })
    })
  })

  // Auto-expand FAQ if user comes from a help link
  const helpParam = urlParams.get("help")
  if (helpParam) {
    const faqMap = {
      upgrade: "collapse1",
      coins: "collapse2",
      payment: "collapse3",
      cancel: "collapse4",
    }

    const targetCollapse = faqMap[helpParam]
    if (targetCollapse) {
      const collapseElement = document.getElementById(targetCollapse)
      if (collapseElement) {
        const bsCollapse = window.bootstrap.Collapse(collapseElement, {
          show: true,
        })

        // Scroll to the FAQ section
        setTimeout(() => {
          collapseElement.scrollIntoView({ behavior: "smooth", block: "center" })
        }, 300)
      }
    }
  }
})

// Function to update prices with discount
function updatePricesWithDiscount(discountPercent) {
  const discountFactor = 1 - discountPercent / 100

  document.querySelectorAll(".price-display").forEach((priceEl) => {
    const planId = priceEl.dataset.planId
    const monthlyPrice = Number.parseFloat(priceEl.dataset.monthlyPrice)
    const yearlyPrice = Number.parseFloat(priceEl.dataset.yearlyPrice)

    if (monthlyPrice > 0) {
      const discountedMonthly = (monthlyPrice * discountFactor).toFixed(2)
      const discountedYearly = (yearlyPrice * discountFactor).toFixed(2)

      // Update monthly price
      const monthlyPriceEl = document.querySelector(`.monthly-price[data-plan-id="${planId}"]`)
      if (monthlyPriceEl) {
        monthlyPriceEl.innerHTML = `$${discountedMonthly} <small class="text-muted">/month</small>
          <br><small class="text-success">(was $${monthlyPrice.toFixed(2)})</small>`
      }

      // Update yearly price
      const yearlyPriceEl = document.querySelector(`.yearly-price[data-plan-id="${planId}"]`)
      if (yearlyPriceEl) {
        yearlyPriceEl.innerHTML = `$${discountedYearly} <small class="text-muted">/year</small>
          <br><small class="text-success">(was $${yearlyPrice.toFixed(2)})</small>`
      }

      // Update links to include coupon code
      document.querySelectorAll(`a[href*="billing.php?plan_id=${planId}"]`).forEach((link) => {
        const url = new URL(link.href)
        url.searchParams.set("coupon", document.getElementById("couponCode").value)
        link.href = url.toString()
      })
    }
  })
}

// Function to apply free coupon
async function applyFreeCoupon(couponCode) {
  try {
    const response = await fetch("php/apply_coupon.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        coupon_code: couponCode,
        csrf_token: document.querySelector('input[name="csrf_token"]').value,
      }),
    })

    const result = await response.json()

    if (result.success) {
      // Show success message and redirect
      document.getElementById("couponMessage").innerHTML = `
        <div class="alert alert-success">
          <strong>🎉 ${result.message}</strong><br>
          <small>Redirecting to dashboard...</small>
        </div>`

      setTimeout(() => {
        window.location.href = result.redirect
      }, 2000)
    } else {
      document.getElementById("couponMessage").innerHTML = `
        <div class="alert alert-danger">${result.error}</div>`
    }
  } catch (error) {
    console.error("Error applying coupon:", error)
    document.getElementById("couponMessage").innerHTML = `
      <div class="alert alert-danger">Error applying coupon. Please try again.</div>`
  }
}

// Utility function to format currency (if needed for dynamic content)
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount)
}

// Function to handle plan comparison (could be extended)
function comparePlans() {
  const cards = document.querySelectorAll(".feature-card")
  cards.forEach((card) => {
    const features = card.querySelectorAll("ul li")
    features.forEach((feature) => {
      feature.addEventListener("click", function () {
        // Could highlight similar features across plans
        const featureText = this.textContent.toLowerCase()
        console.log("Feature clicked:", featureText)
      })
    })
  })
}

// Initialize comparison functionality
comparePlans()
