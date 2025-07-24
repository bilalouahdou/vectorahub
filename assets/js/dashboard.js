// Enhanced Dashboard with complete error prevention
class Dashboard {
  constructor() {
    this.currentSection = "overview"
    this.currentPage = 1
    this.VectorizeUtils = { formatDate: (date) => new Date(date).toLocaleDateString() }
    this.initialized = false
    this.isValidPage = false

    // Comprehensive initialization with multiple safety checks
    this.safeInit()
  }

  safeInit() {
    try {
      // Wait for DOM and check if we should initialize
      const initWhenReady = () => {
        if (this.shouldInitialize()) {
          this.isValidPage = true
          this.performInitialization()
        } else {
          console.log("Dashboard: Not a dashboard page, skipping initialization")
        }
      }

      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initWhenReady)
      } else {
        // DOM already loaded, but wait a bit for dynamic content
        setTimeout(initWhenReady, 200)
      }
    } catch (error) {
      console.warn("Dashboard: Initialization failed safely:", error.message)
    }
  }

  shouldInitialize() {
    // Check for any dashboard-related elements
    const dashboardIndicators = [
      ".sidebar-nav",
      "#totalJobs",
      "#recentJobs",
      "#historyContent",
      "[data-section]",
      ".dashboard-container",
      ".stats-card",
    ]

    return dashboardIndicators.some((selector) => {
      try {
        return document.querySelector(selector) !== null
      } catch (e) {
        return false
      }
    })
  }

  performInitialization() {
    try {
      this.setupNavigation()

      // Only attempt data loading if elements exist
      if (this.hasRequiredElements("stats")) {
        this.loadOverviewStats()
      }

      if (this.hasRequiredElements("recentJobs")) {
        this.loadRecentJobs()
      }

      this.initialized = true
      console.log("Dashboard: Successfully initialized")
    } catch (error) {
      console.warn("Dashboard: Partial initialization failure:", error.message)
    }
  }

  hasRequiredElements(type) {
    try {
      switch (type) {
        case "stats":
          return document.getElementById("totalJobs") && document.getElementById("successfulJobs")
        case "recentJobs":
          return document.getElementById("recentJobs")
        case "history":
          return document.getElementById("historyContent")
        default:
          return false
      }
    } catch (e) {
      return false
    }
  }

  setupNavigation() {
    try {
      const navLinks = document.querySelectorAll(".sidebar-nav .nav-link[data-section]")
      if (navLinks.length === 0) return

      navLinks.forEach((link) => {
        try {
          link.addEventListener("click", (e) => {
            e.preventDefault()
            const section = link.dataset.section
            if (section) {
              this.showSection(section)
              this.updateActiveNavLink(navLinks, link)
            }
          })
        } catch (error) {
          console.warn("Dashboard: Failed to setup nav link:", error.message)
        }
      })
    } catch (error) {
      console.warn("Dashboard: Navigation setup failed:", error.message)
    }
  }

  updateActiveNavLink(allLinks, activeLink) {
    try {
      allLinks.forEach((l) => {
        try {
          l.classList.remove("active")
        } catch (e) {
          // Ignore individual link errors
        }
      })
      activeLink.classList.add("active")
    } catch (error) {
      console.warn("Dashboard: Failed to update active nav:", error.message)
    }
  }

  showSection(sectionName) {
    if (!this.isValidPage) return

    try {
      // Hide all sections safely
      const sections = document.querySelectorAll(".section")
      sections.forEach((section) => {
        try {
          section.classList.add("d-none")
        } catch (e) {
          // Ignore individual section errors
        }
      })

      // Show target section
      const targetSection = document.getElementById(`${sectionName}-section`)
      if (targetSection) {
        targetSection.classList.remove("d-none")
        this.currentSection = sectionName

        // Load section-specific content
        if (sectionName === "history" && this.hasRequiredElements("history")) {
          this.loadHistory()
        }
      }
    } catch (error) {
      console.warn("Dashboard: Section switching failed:", error.message)
    }
  }

  async loadOverviewStats() {
    if (!this.hasRequiredElements("stats")) return

    const totalJobsElement = document.getElementById("totalJobs")
    const successfulJobsElement = document.getElementById("successfulJobs")

    try {
      // Set loading state
      if (totalJobsElement) totalJobsElement.textContent = "..."
      if (successfulJobsElement) successfulJobsElement.textContent = "..."

      const response = await this.tryApiCall([
        "php/dashboard_stats.php",
        "/php/dashboard_stats.php",
        "./php/dashboard_stats.php",
      ])

      if (response) {
        const stats = await response.json()
        if (stats && !stats.error) {
          if (totalJobsElement) totalJobsElement.textContent = stats.total_jobs || 0
          if (successfulJobsElement) successfulJobsElement.textContent = stats.successful_jobs || 0
          return
        }
      }

      throw new Error("No valid response received")
    } catch (error) {
      console.warn("Dashboard: Stats loading failed:", error.message)
      // Set safe fallback values
      if (totalJobsElement) totalJobsElement.textContent = "0"
      if (successfulJobsElement) successfulJobsElement.textContent = "0"
    }
  }

  async loadRecentJobs() {
    const container = document.getElementById("recentJobs")
    if (!container) return

    try {
      container.innerHTML = '<p class="text-muted">Loading...</p>'

      const response = await this.tryApiCall([
        "php/history.php?limit=5",
        "/php/history.php?limit=5",
        "./php/history.php?limit=5",
      ])

      if (response) {
        const data = await response.json()
        if (data && !data.error && data.jobs) {
          if (data.jobs.length > 0) {
            container.innerHTML = data.jobs.map((job) => this.createJobRow(job)).join("")
          } else {
            container.innerHTML =
              '<p class="text-muted">No jobs yet. <a href="#" onclick="dashboard?.showSection(\'upload\')">Upload your first image!</a></p>'
          }
          return
        }
      }

      throw new Error("No valid response received")
    } catch (error) {
      console.warn("Dashboard: Recent jobs loading failed:", error.message)
      container.innerHTML =
        '<p class="text-muted">Unable to load recent jobs. <a href="#" onclick="dashboard?.showSection(\'upload\')">Upload your first image!</a></p>'
    }
  }

  async tryApiCall(paths, timeout = 10000) {
    for (const path of paths) {
      try {
        const controller = new AbortController()
        const timeoutId = setTimeout(() => controller.abort(), timeout)

        const response = await fetch(path, {
          method: "GET",
          headers: {
            Accept: "application/json",
            "Cache-Control": "no-cache",
          },
          signal: controller.signal,
        })

        clearTimeout(timeoutId)

        if (response.ok) {
          const contentType = response.headers.get("content-type")
          if (contentType && contentType.includes("application/json")) {
            return response
          }
        }
      } catch (error) {
        if (error.name !== "AbortError") {
          console.log(`Dashboard: API path ${path} failed:`, error.message)
        }
        continue
      }
    }
    return null
  }

  async loadHistory(page = 1) {
    if (!this.hasRequiredElements("history")) return

    const container = document.getElementById("historyContent")
    const pagination = document.getElementById("historyPagination")

    try {
      container.innerHTML = `
        <div class="text-center py-5">
          <div class="spinner-border text-accent" role="status"></div>
          <p class="mt-2">Loading history...</p>
        </div>
      `

      const response = await this.tryApiCall([
        `php/history.php?page=${page}&limit=9`,
        `/php/history.php?page=${page}&limit=9`,
        `./php/history.php?page=${page}&limit=9`,
      ])

      if (response) {
        const data = await response.json()
        if (data && !data.error) {
          if (data.jobs && data.jobs.length > 0) {
            container.innerHTML = `
              <div class="row g-3">
                ${data.jobs.map((job) => this.createJobCard(job)).join("")}
              </div>
            `

            if (pagination && data.pagination && data.pagination.total_pages > 1) {
              this.setupPagination(data.pagination)
              pagination.classList.remove("d-none")
            } else if (pagination) {
              pagination.classList.add("d-none")
            }
            return
          }
        }
      }

      throw new Error("No valid response received")
    } catch (error) {
      console.warn("Dashboard: History loading failed:", error.message)
      container.innerHTML = `
        <div class="text-center py-5">
          <p class="text-muted">Unable to load history.</p>
          <a href="#" class="btn btn-accent" onclick="dashboard?.showSection('upload')">Upload your first image</a>
        </div>
      `
      if (pagination) pagination.classList.add("d-none")
    }
  }

  createJobRow(job) {
    if (!job) return ""

    try {
      const statusClass = `status-${job.status || "unknown"}`
      const date = job.created_at ? this.VectorizeUtils.formatDate(job.created_at) : "Unknown"
      const filename = job.original_filename || `Job #${job.id || "Unknown"}`

      return `
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <div>
            <strong>${filename}</strong>
            <span class="status-badge ${statusClass}">${job.status || "unknown"}</span>
          </div>
          <div class="text-end">
            <small class="text-muted">${date}</small>
            ${job.status === "done" && job.output_svg_path ? `<a href="download.php?file=${encodeURIComponent(job.output_svg_path)}" class="btn btn-sm btn-outline-accent ms-2" download>⬇️</a>` : ""}
          </div>
        </div>
      `
    } catch (error) {
      console.warn("Dashboard: Failed to create job row:", error.message)
      return ""
    }
  }

  createJobCard(job) {
    if (!job) return ""

    try {
      const statusClass = `status-${job.status || "unknown"}`
      const date = job.created_at ? this.VectorizeUtils.formatDate(job.created_at) : "Unknown"
      const filename = job.original_filename || `Job #${job.id || "Unknown"}`

      // Truncate long titles to 25 characters
      const truncatedTitle = filename.length > 25 ? filename.substring(0, 25) + "..." : filename

      return `
      <div class="col-md-4 mb-4">
        <div class="job-card h-100 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="mb-0 flex-grow-1 me-2" title="${filename}">${truncatedTitle}</h6>
            <span class="status-badge ${statusClass} flex-shrink-0">${job.status || "unknown"}</span>
          </div>
          
          <div class="text-center mb-3 flex-grow-1 d-flex align-items-center justify-content-center" style="min-height: 120px; background-color: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
            ${
              job.status === "done" && job.output_svg_path
                ? `
                <img src="/download.php?file=${encodeURIComponent(job.output_svg_path)}" 
                     alt="Preview" 
                     class="img-fluid" 
                     style="max-width: 100px; max-height: 100px; object-fit: contain;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display: none; color: #6c757d;">
                  <i class="fas fa-image fa-2x mb-2"></i>
                  <p class="small mb-0">Preview Available</p>
                </div>
              `
                : job.status === "processing"
                  ? `
                <div class="text-center text-muted">
                  <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                  <p class="small mb-0">Processing...</p>
                </div>
              `
                  : `
                <div class="text-center text-muted">
                  <i class="fas fa-file-image fa-2x mb-2"></i>
                  <p class="small mb-0">${job.status === "failed" ? "Processing Failed" : "No Preview"}</p>
                </div>
              `
            }
          </div>
          
          <div class="small text-muted mb-3">
            <div class="d-flex justify-content-between">
              <span>Created:</span>
              <span>${date}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Coins:</span>
              <span>${job.coins_used || 1}</span>
            </div>
          </div>
          
          <div class="mt-auto">
            ${
              job.status === "done" && job.output_svg_path
                ? `
              <a href="download.php?file=${encodeURIComponent(job.output_svg_path)}" 
                 class="btn btn-accent btn-sm w-100" download>
                <i class="fas fa-download me-1"></i> Download
              </a>
            `
                : job.status === "failed"
                  ? `
              <button class="btn btn-outline-secondary btn-sm w-100" onclick="dashboard?.retryJob(${job.id})">
                <i class="fas fa-redo me-1"></i> Retry
              </button>
            `
                  : `
              <button class="btn btn-outline-secondary btn-sm w-100 disabled">
                <i class="fas fa-clock me-1"></i> Processing
              </button>
            `
            }
          </div>
        </div>
      </div>
    `
    } catch (error) {
      console.warn("Dashboard: Failed to create job card:", error.message)
      return ""
    }
  }

  setupPagination(pagination) {
    try {
      const container = document.querySelector("#historyPagination .pagination")
      if (!container || !pagination) return

      const { current_page, total_pages } = pagination
      let html = ""

      if (current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="dashboard?.loadHistory(${current_page - 1})">Previous</a></li>`
      }

      for (let i = 1; i <= total_pages; i++) {
        if (i === current_page) {
          html += `<li class="page-item active"><span class="page-link">${i}</span></li>`
        } else if (i === 1 || i === total_pages || Math.abs(i - current_page) <= 2) {
          html += `<li class="page-item"><a class="page-link" href="#" onclick="dashboard?.loadHistory(${i})">${i}</a></li>`
        } else if (Math.abs(i - current_page) === 3) {
          html += `<li class="page-item disabled"><span class="page-link">...</span></li>`
        }
      }

      if (current_page < total_pages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="dashboard?.loadHistory(${current_page + 1})">Next</a></li>`
      }

      container.innerHTML = html
    } catch (error) {
      console.warn("Dashboard: Pagination setup failed:", error.message)
    }
  }

  async retryJob(jobId) {
    if (!jobId) return

    try {
      const response = await fetch("/php/retry_job.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          job_id: jobId,
          csrf_token: document.querySelector('input[name="csrf_token"]')?.value,
        }),
      })

      const result = await response.json()

      if (result && result.success) {
        this.showToast("Job retry initiated", "success")
        if (this.hasRequiredElements("history")) {
          this.loadHistory(this.currentPage)
        }
      } else {
        this.showToast(result?.error || "Failed to retry job", "danger")
      }
    } catch (error) {
      console.warn("Dashboard: Job retry failed:", error.message)
      this.showToast("Network error", "danger")
    }
  }

  showToast(message, type) {
    console.log(`Dashboard Toast: ${message} (${type})`)
  }
}

// Safe initialization with multiple fallbacks
let dashboard

function initializeDashboard() {
  if (dashboard) return // Already initialized

  try {
    dashboard = new Dashboard()
  } catch (error) {
    console.warn("Dashboard: Failed to initialize:", error.message)
  }
}

// Multiple initialization attempts
document.addEventListener("DOMContentLoaded", initializeDashboard)

if (document.readyState !== "loading") {
  setTimeout(initializeDashboard, 100)
}

// Fallback initialization
setTimeout(() => {
  if (!dashboard) {
    initializeDashboard()
  }
}, 1000)
