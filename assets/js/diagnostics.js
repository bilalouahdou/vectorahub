// Diagnostic script to test backend connectivity
class BackendDiagnostics {
  constructor() {
    this.results = {
      endpoints: {},
      connectivity: {},
      errors: [],
    }
  }

  async runDiagnostics() {
    console.log("🔍 Starting backend diagnostics...")

    // Test diagnostic endpoint first
    await this.testDiagnosticEndpoint()

    // Test all critical endpoints
    const endpoints = ["php/dashboard_stats.php", "php/history.php", "php/upload_handler.php", "php/status_check.php"]

    for (const endpoint of endpoints) {
      await this.testEndpoint(endpoint)
    }

    // Test with different base paths
    const basePaths = ["", "/", "./"]
    for (const basePath of basePaths) {
      await this.testEndpoint(basePath + "php/dashboard_stats.php", `base_path_${basePath || "root"}`)
    }

    this.displayResults()
  }

  async testDiagnosticEndpoint() {
    try {
      console.log("Testing diagnostic endpoint...")
      const response = await fetch("php/diagnostics.php", {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Cache-Control": "no-cache",
        },
      })

      if (response.ok) {
        const data = await response.json()
        console.log("✅ Diagnostic endpoint working:", data)
        this.results.diagnostics = data
      } else {
        console.log("❌ Diagnostic endpoint failed:", response.status, response.statusText)
        this.results.errors.push(`Diagnostic endpoint: ${response.status} ${response.statusText}`)
      }
    } catch (error) {
      console.log("❌ Diagnostic endpoint error:", error.message)
      this.results.errors.push(`Diagnostic endpoint: ${error.message}`)
    }
  }

  async testEndpoint(endpoint, label = null) {
    const testLabel = label || endpoint

    try {
      console.log(`Testing ${testLabel}...`)

      const controller = new AbortController()
      const timeoutId = setTimeout(() => controller.abort(), 10000)

      const response = await fetch(endpoint, {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Cache-Control": "no-cache",
        },
        signal: controller.signal,
      })

      clearTimeout(timeoutId)

      const result = {
        status: response.status,
        statusText: response.statusText,
        ok: response.ok,
        headers: Object.fromEntries(response.headers.entries()),
        url: response.url,
      }

      if (response.ok) {
        try {
          const contentType = response.headers.get("content-type")
          if (contentType && contentType.includes("application/json")) {
            const data = await response.json()
            result.data = data
            result.type = "json"
          } else {
            const text = await response.text()
            result.data = text.substring(0, 200) + (text.length > 200 ? "..." : "")
            result.type = "text"
          }
        } catch (parseError) {
          result.parseError = parseError.message
        }

        console.log(`✅ ${testLabel} working:`, result)
      } else {
        console.log(`❌ ${testLabel} failed:`, result)
        this.results.errors.push(`${testLabel}: ${response.status} ${response.statusText}`)
      }

      this.results.endpoints[testLabel] = result
    } catch (error) {
      const errorResult = {
        error: error.message,
        type: error.name,
      }

      console.log(`❌ ${testLabel} error:`, errorResult)
      this.results.endpoints[testLabel] = errorResult
      this.results.errors.push(`${testLabel}: ${error.message}`)
    }
  }

  displayResults() {
    console.log("\n📊 DIAGNOSTIC RESULTS:")
    console.log("=".repeat(50))

    if (this.results.diagnostics) {
      console.log("🔧 Server Info:", {
        php_version: this.results.diagnostics.php_version,
        server_software: this.results.diagnostics.server_software,
        database_connection: this.results.diagnostics.database_connection,
      })
    }

    console.log("\n🌐 Endpoint Tests:")
    Object.entries(this.results.endpoints).forEach(([endpoint, result]) => {
      const status = result.ok ? "✅" : "❌"
      console.log(`${status} ${endpoint}:`, result.status || result.error)
    })

    if (this.results.errors.length > 0) {
      console.log("\n❌ Errors Found:")
      this.results.errors.forEach((error) => console.log(`  - ${error}`))
    }

    console.log("\n💡 Recommendations:")
    this.generateRecommendations()
  }

  generateRecommendations() {
    const recommendations = []

    if (this.results.errors.length === 0) {
      recommendations.push("All endpoints are working correctly!")
    } else {
      if (this.results.errors.some((e) => e.includes("Failed to fetch"))) {
        recommendations.push("Check if the web server is running")
        recommendations.push("Verify the correct URL/port is being used")
      }

      if (this.results.errors.some((e) => e.includes("404"))) {
        recommendations.push("Check if PHP files exist in the correct directories")
        recommendations.push("Verify file permissions are set correctly")
      }

      if (this.results.errors.some((e) => e.includes("500"))) {
        recommendations.push("Check PHP error logs for detailed error information")
        recommendations.push("Verify database connection settings")
      }

      if (this.results.diagnostics?.database_connection?.includes("Failed")) {
        recommendations.push("Fix database connection issues")
        recommendations.push("Check database credentials in config.php")
      }
    }

    recommendations.forEach((rec) => console.log(`  💡 ${rec}`))
  }
}

// Auto-run diagnostics when script loads
document.addEventListener("DOMContentLoaded", () => {
  const diagnostics = new BackendDiagnostics()

  // Add a button to manually run diagnostics
  const button = document.createElement("button")
  button.textContent = "🔍 Run Backend Diagnostics"
  button.style.cssText =
    "position: fixed; top: 10px; right: 10px; z-index: 9999; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;"
  button.onclick = () => diagnostics.runDiagnostics()
  document.body.appendChild(button)

  // Auto-run after a short delay
  setTimeout(() => diagnostics.runDiagnostics(), 2000)
})
