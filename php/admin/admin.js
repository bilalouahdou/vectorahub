class AdminDashboard {
  constructor() {
    this.currentSection = "overview"
    this.currentPage = 1
    this.settings = {}
    this.init()
  }

  init() {
    this.setupNavigation()
    this.loadOverview()
    this.checkSystemStatus()
    this.setupEventListeners()
    this.loadStats()
    this.createModals()
  }

  createModals() {
    // Create job modal if it doesn't exist
    if (!document.getElementById("jobModal")) {
      const jobModalHTML = `
        <div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="jobModalLabel">Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="jobModalBody">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-2">Loading job details...</p>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      `
      document.body.insertAdjacentHTML("beforeend", jobModalHTML)
    }

    // Create user view modal
    if (!document.getElementById("userViewModal")) {
      const userViewModalHTML = `
        <div class="modal fade" id="userViewModal" tabindex="-1" aria-labelledby="userViewModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="userViewModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="userViewModalBody">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-2">Loading user details...</p>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      `
      document.body.insertAdjacentHTML("beforeend", userViewModalHTML)
    }

    // Create user edit modal
    if (!document.getElementById("userEditModal")) {
      const userEditModalHTML = `
        <div class="modal fade" id="userEditModal" tabindex="-1" aria-labelledby="userEditModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="userEditModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body" id="userEditModalBody">
                <div class="text-center py-4">
                  <div class="spinner-border text-primary" role="status"></div>
                  <p class="mt-2">Loading user data...</p>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Save Changes</button>
              </div>
            </div>
          </div>
        </div>
      `
      document.body.insertAdjacentHTML("beforeend", userEditModalHTML)
    }
  }

  setupNavigation() {
    document.querySelectorAll(".nav-link[data-section]").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const section = link.dataset.section
        this.showSection(section)

        // Update active nav
        document.querySelectorAll(".nav-link").forEach((l) => l.classList.remove("active"))
        link.classList.add("active")
      })
    })
  }

  setupEventListeners() {
    // User search and filter
    const userSearch = document.getElementById("userSearch")
    if (userSearch) {
      userSearch.addEventListener(
        "input",
        this.debounce(() => {
          this.loadUsers(1, userSearch.value, document.getElementById("userFilter")?.value || "")
        }, 500),
      )
    }

    const userFilter = document.getElementById("userFilter")
    if (userFilter) {
      userFilter.addEventListener("change", () => {
        this.loadUsers(1, document.getElementById("userSearch")?.value || "", userFilter.value)
      })
    }

    // Job filters
    const jobStatusFilter = document.getElementById("jobStatusFilter")
    if (jobStatusFilter) {
      jobStatusFilter.addEventListener("change", () => {
        this.loadJobs(1, jobStatusFilter.value, document.getElementById("jobDateFilter")?.value || "")
      })
    }

    const jobDateFilter = document.getElementById("jobDateFilter")
    if (jobDateFilter) {
      jobDateFilter.addEventListener("change", () => {
        this.loadJobs(1, document.getElementById("jobStatusFilter")?.value || "", jobDateFilter.value)
      })
    }
  }

  async loadStats() {
    try {
      // Load basic stats
      const response = await fetch("api/stats.php")
      const data = await response.json()

      if (data.success) {
        document.getElementById("totalUsers").textContent = data.stats.total_users || "0"
        document.getElementById("totalJobs").textContent = data.stats.total_jobs || "0"
        document.getElementById("activeSubscriptions").textContent = data.stats.active_subscriptions || "0"
        document.getElementById("monthlyRevenue").textContent = "$" + (data.stats.monthly_revenue || "0")
      } else {
        console.error("Stats error:", data.error)
        // Set default values
        document.getElementById("totalUsers").textContent = "0"
        document.getElementById("totalJobs").textContent = "0"
        document.getElementById("activeSubscriptions").textContent = "0"
        document.getElementById("monthlyRevenue").textContent = "$0"
      }
    } catch (error) {
      console.error("Failed to load stats:", error)
      // Set default values
      document.getElementById("totalUsers").textContent = "0"
      document.getElementById("totalJobs").textContent = "0"
      document.getElementById("activeSubscriptions").textContent = "0"
      document.getElementById("monthlyRevenue").textContent = "$0"
    }
  }

  showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll(".section").forEach((section) => {
      section.classList.add("d-none")
    })

    // Show target section
    const targetSection = document.getElementById(`${sectionName}-section`)
    if (targetSection) {
      targetSection.classList.remove("d-none")
      this.currentSection = sectionName

      // Load section-specific content
      switch (sectionName) {
        case "users":
          this.loadUsers()
          break
        case "jobs":
          this.loadJobs()
          break
        case "subscriptions":
          this.loadSubscriptions()
          break
        case "settings":
          this.loadSystemSettings()
          break
        case "analytics":
          this.loadAnalytics()
          break
        case "logs":
          this.loadSystemLogs()
          break
      }
    }
  }

  async loadOverview() {
    try {
      // Load recent activity
      const response = await fetch("api/recent_activity.php")
      const data = await response.json()

      if (data.success) {
        this.displayRecentActivity(data.activities)
      }
    } catch (error) {
      console.error("Failed to load overview:", error)
      // Show default message
      const container = document.getElementById("recentActivity")
      if (container) {
        container.innerHTML = '<p class="text-muted text-center py-3">No recent activity available</p>'
      }
    }
  }

  async checkSystemStatus() {
    try {
      // Check Python API status
      const response = await fetch("http://localhost:5000/health", {
        method: "GET",
        timeout: 5000,
      })
      const statusElement = document.getElementById("pythonApiStatus")

      if (response.ok) {
        statusElement.textContent = "Online"
        statusElement.className = "status-badge status-active"
      } else {
        statusElement.textContent = "Offline"
        statusElement.className = "status-badge status-inactive"
      }
    } catch (error) {
      const statusElement = document.getElementById("pythonApiStatus")
      if (statusElement) {
        statusElement.textContent = "Offline"
        statusElement.className = "status-badge status-inactive"
      }
    }
  }

  async loadUsers(page = 1, search = "", filter = "") {
    try {
      const params = new URLSearchParams({
        page: page,
        search: search,
        filter: filter,
      })

      const response = await fetch(`api/users.php?${params}`)
      const data = await response.json()

      if (data.success) {
        this.displayUsers(data.users)
        this.setupUsersPagination(data.pagination)
      } else {
        this.showError(data.error || "Failed to load users")
        this.displayUsers([]) // Show empty table
      }
    } catch (error) {
      console.error("Failed to load users:", error)
      this.showError("Network error: Failed to load users")
      this.displayUsers([]) // Show empty table
    }
  }

  displayUsers(users) {
    const tbody = document.getElementById("usersTableBody")

    if (!tbody) return

    if (users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No users found</td></tr>'
      return
    }

    tbody.innerHTML = users
      .map(
        (user) => `
            <tr>
                <td>${user.id}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        ${user.full_name}
                    </div>
                </td>
                <td>${user.email}</td>
                <td>
                    <span class="status-badge ${user.role === "admin" ? "status-pending" : "status-active"}">
                        ${user.role || "user"}
                    </span>
                </td>
                <td>
                    <span class="status-badge status-active">
                        active
                    </span>
                </td>
                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editUser(${user.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="viewUser(${user.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteUser(${user.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `,
      )
      .join("")
  }

  setupUsersPagination(pagination) {
    const paginationElement = document.getElementById("usersPagination")
    if (!paginationElement || !pagination) return

    const { current_page, total_pages } = pagination

    if (total_pages <= 1) {
      paginationElement.innerHTML = ""
      return
    }

    let html = '<ul class="pagination">'

    // Previous button
    html += `
      <li class="page-item ${current_page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page - 1}" aria-label="Previous">
          <span aria-hidden="true">&laquo;</span>
        </a>
      </li>
    `

    // Page numbers
    const startPage = Math.max(1, current_page - 2)
    const endPage = Math.min(total_pages, current_page + 2)

    if (startPage > 1) {
      html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`
      if (startPage > 2) {
        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      html += `<li class="page-item ${i === current_page ? "active" : ""}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`
    }

    if (endPage < total_pages) {
      if (endPage < total_pages - 1) {
        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`
      }
      html += `<li class="page-item"><a class="page-link" href="#" data-page="${total_pages}">${total_pages}</a></li>`
    }

    // Next button
    html += `
      <li class="page-item ${current_page === total_pages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page + 1}" aria-label="Next">
          <span aria-hidden="true">&raquo;</span>
        </a>
      </li>
    `

    html += "</ul>"
    paginationElement.innerHTML = html

    // Add event listeners to pagination links
    paginationElement.querySelectorAll(".page-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const page = Number.parseInt(link.dataset.page)
        if (!isNaN(page)) {
          this.loadUsers(
            page,
            document.getElementById("userSearch")?.value || "",
            document.getElementById("userFilter")?.value || "",
          )
        }
      })
    })
  }

  async loadJobs(page = 1, status = "", date = "") {
    try {
      const params = new URLSearchParams({
        page: page,
        status: status,
        date: date,
      })

      const response = await fetch(`api/jobs.php?${params}`)
      const data = await response.json()

      if (data.success) {
        this.displayJobs(data.jobs)
        this.setupJobsPagination(data.pagination)
      } else {
        this.showError(data.error || "Failed to load jobs")
        this.displayJobs([]) // Show empty table
      }
    } catch (error) {
      console.error("Failed to load jobs:", error)
      this.showError("Network error: Failed to load jobs")
      this.displayJobs([]) // Show empty table
    }
  }

  displayJobs(jobs) {
    const tbody = document.getElementById("jobsTableBody")

    if (!tbody) return

    if (jobs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No jobs found</td></tr>'
      return
    }

    tbody.innerHTML = jobs
      .map(
        (job) => `
            <tr>
                <td>${job.id}</td>
                <td>${job.user_name || "Unknown"}</td>
                <td>
                    <div class="text-truncate" style="max-width: 200px;" title="${job.original_filename || "Unknown"}">
                        ${job.original_filename || "Unknown"}
                    </div>
                </td>
                <td>
                    <span class="status-badge ${this.getJobStatusClass(job.status)}">
                        ${job.status}
                    </span>
                </td>
                <td>${new Date(job.created_at).toLocaleDateString()}</td>
                <td>${job.coins_used || 1}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        ${
                          job.status === "failed"
                            ? `<button class="btn btn-outline-warning" onclick="retryJob(${job.id})" title="Retry">
                                <i class="fas fa-redo"></i>
                            </button>`
                            : ""
                        }
                        <button class="btn btn-outline-info" onclick="viewJob(${job.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteJob(${job.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `,
      )
      .join("")
  }

  setupJobsPagination(pagination) {
    const paginationElement = document.getElementById("jobsPagination")
    if (!paginationElement || !pagination) return

    const { current_page, total_pages } = pagination

    if (total_pages <= 1) {
      paginationElement.innerHTML = ""
      return
    }

    let html = '<ul class="pagination">'

    // Previous button
    html += `
      <li class="page-item ${current_page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page - 1}" aria-label="Previous">
          <span aria-hidden="true">&laquo;</span>
        </a>
      </li>
    `

    // Page numbers
    const startPage = Math.max(1, current_page - 2)
    const endPage = Math.min(total_pages, current_page + 2)

    if (startPage > 1) {
      html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`
      if (startPage > 2) {
        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      html += `<li class="page-item ${i === current_page ? "active" : ""}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`
    }

    if (endPage < total_pages) {
      if (endPage < total_pages - 1) {
        html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`
      }
      html += `<li class="page-item"><a class="page-link" href="#" data-page="${total_pages}">${total_pages}</a></li>`
    }

    // Next button
    html += `
      <li class="page-item ${current_page === total_pages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page + 1}">&raquo;</a>
      </li>
    `

    html += "</ul>"
    paginationElement.innerHTML = html

    // Add event listeners to pagination links
    paginationElement.querySelectorAll(".page-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const page = Number.parseInt(link.dataset.page)
        if (!isNaN(page)) {
          this.loadJobs(
            page,
            document.getElementById("jobStatusFilter")?.value || "",
            document.getElementById("jobDateFilter")?.value || "",
          )
        }
      })
    })
  }

  getJobStatusClass(status) {
    switch (status) {
      case "done":
        return "status-active"
      case "failed":
        return "status-inactive"
      case "processing":
        return "status-pending"
      default:
        return "status-pending"
    }
  }

  displayRecentActivity(activities) {
    const container = document.getElementById("recentActivity")

    if (!container) return

    if (!activities || activities.length === 0) {
      container.innerHTML = '<p class="text-muted text-center py-3">No recent activity</p>'
      return
    }

    container.innerHTML = activities
      .map(
        (activity) => `
            <div class="d-flex align-items-center py-2 border-bottom">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i class="fas ${this.getActivityIcon(activity.type)} text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">${activity.description}</div>
                    <small class="text-muted">${new Date(activity.created_at).toLocaleString()}</small>
                </div>
            </div>
        `,
      )
      .join("")
  }

  getActivityIcon(type) {
    switch (type) {
      case "user_registered":
        return "fa-user-plus"
      case "job_completed":
        return "fa-check"
      case "job_failed":
        return "fa-times"
      case "payment_received":
        return "fa-dollar-sign"
      default:
        return "fa-info"
    }
  }

  async loadSubscriptions(page = 1, status = "") {
    try {
      // Update the section content
      const section = document.getElementById("subscriptions-section")
      if (!section) return

      section.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-credit-card me-2"></i>Subscription Management</h2>
            <div>
                <button class="action-btn me-2" onclick="exportSubscriptions()">
                    <i class="fas fa-download me-1"></i> Export
                </button>
                <button class="action-btn" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                    <i class="fas fa-plus me-1"></i> Add Plan
                </button>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number" id="active-subscriptions">0</div>
                    <div class="stat-label">Active Subscriptions</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number" id="monthly-revenue">$0</div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number" id="avg-subscription">$0</div>
                    <div class="stat-label">Avg. Subscription Value</div>
                </div>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Subscription Plans</h5>
                <select class="form-select" id="planFilter" style="width: 150px;">
                    <option value="">All Plans</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="table-responsive">
                <table class="table admin-table" id="plansTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Billing</th>
                            <th>Features</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="plansTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4">No subscription plans found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="admin-card mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">User Subscriptions</h5>
                <select class="form-select" id="subscriptionStatusFilter" style="width: 150px;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            
            <div class="table-responsive">
                <table class="table admin-table" id="subscriptionsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="subscriptionsTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4">No subscriptions found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <nav id="subscriptionsPagination" class="mt-3">
                <ul class="pagination justify-content-center"></ul>
            </nav>
        </div>
      `

      const params = new URLSearchParams({
        page: page,
        status: status,
      })

      const response = await fetch(`api/subscriptions.php?${params}`)
      const data = await response.json()

      if (data.success) {
        this.displaySubscriptions(data.subscriptions)
        this.setupSubscriptionsPagination(data.pagination)

        // Update stats
        document.getElementById("active-subscriptions").textContent = data.stats?.active_count || "0"
        document.getElementById("monthly-revenue").textContent = "$" + (data.stats?.monthly_revenue || "0")
        document.getElementById("avg-subscription").textContent = "$" + (data.stats?.avg_value || "0")

        // Setup event listeners
        const statusFilter = document.getElementById("subscriptionStatusFilter")
        if (statusFilter) {
          statusFilter.addEventListener("change", () => {
            this.loadSubscriptions(1, statusFilter.value)
          })
        }
      } else {
        this.showError(data.error || "Failed to load subscriptions")
      }
    } catch (error) {
      console.error("Failed to load subscriptions:", error)
      this.showError("Network error: Failed to load subscriptions")
    }
  }

  displaySubscriptions(subscriptions) {
    const tbody = document.getElementById("subscriptionsTableBody")

    if (!tbody) return

    if (!subscriptions || subscriptions.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No subscriptions found</td></tr>'
      return
    }

    tbody.innerHTML = subscriptions
      .map((sub) => {
        const isActive = sub.active && new Date(sub.end_date) >= new Date()

        return `
            <tr>
                <td>${sub.id}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        ${sub.user_name || "Unknown"}
                    </div>
                </td>
                <td>${sub.plan_name || "Unknown Plan"}</td>
                <td>${new Date(sub.start_date).toLocaleDateString()}</td>
                <td>${new Date(sub.end_date).toLocaleDateString()}</td>
                <td>
                    <span class="status-badge ${isActive ? "status-active" : "status-inactive"}">
                        ${isActive ? "Active" : "Expired"}
                    </span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editSubscription(${sub.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="viewSubscription(${sub.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="cancelSubscription(${sub.id})" title="Cancel">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            </tr>
          `
      })
      .join("")
  }

  setupSubscriptionsPagination(pagination) {
    const paginationElement = document.getElementById("subscriptionsPagination")
    if (!paginationElement || !pagination) return

    const { current_page, total_pages } = pagination

    if (total_pages <= 1) {
      paginationElement.innerHTML = ""
      return
    }

    // Similar pagination setup as for users and jobs
    let html = '<ul class="pagination">'

    // Previous button
    html += `
      <li class="page-item ${current_page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page - 1}">&laquo;</a>
      </li>
    `

    // Page numbers
    const startPage = Math.max(1, current_page - 2)
    const endPage = Math.min(total_pages, current_page + 2)

    for (let i = startPage; i <= endPage; i++) {
      html += `<li class="page-item ${i === current_page ? "active" : ""}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`
    }

    // Next button
    html += `
      <li class="page-item ${current_page === total_pages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page + 1}">&raquo;</a>
      </li>
    `

    html += "</ul>"
    paginationElement.innerHTML = html

    // Add event listeners
    paginationElement.querySelectorAll(".page-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const page = Number.parseInt(link.dataset.page)
        if (!isNaN(page)) {
          this.loadSubscriptions(page, document.getElementById("subscriptionStatusFilter")?.value || "")
        }
      })
    })
  }

  async loadSystemSettings() {
    try {
      // Update the section content
      const section = document.getElementById("settings-section")
      if (!section) return

      section.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cogs me-2"></i>System Settings</h2>
            <button class="action-btn" id="saveSettingsBtn">
                <i class="fas fa-save me-1"></i> Save Changes
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="list-group settings-nav">
                    <a href="#" class="list-group-item list-group-item-action active" data-settings-tab="general">
                        <i class="fas fa-sliders-h me-2"></i> General
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" data-settings-tab="email">
                        <i class="fas fa-envelope me-2"></i> Email
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" data-settings-tab="payment">
                        <i class="fas fa-credit-card me-2"></i> Payment
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" data-settings-tab="api">
                        <i class="fas fa-code me-2"></i> API
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" data-settings-tab="security">
                        <i class="fas fa-shield-alt me-2"></i> Security
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="admin-card">
                    <div id="settingsContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading settings...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      `

      // Add event listeners for settings tabs
      document.querySelectorAll(".settings-nav .list-group-item").forEach((tab) => {
        tab.addEventListener("click", (e) => {
          e.preventDefault()
          document.querySelectorAll(".settings-nav .list-group-item").forEach((t) => t.classList.remove("active"))
          tab.classList.add("active")
          this.showSettingsTab(tab.dataset.settingsTab)
        })
      })

      // Add event listener for save button
      document.getElementById("saveSettingsBtn").addEventListener("click", () => {
        this.saveSettings()
      })

      // Load settings data
      const response = await fetch("api/system_settings.php")
      const data = await response.json()

      if (data.success) {
        this.settings = data.settings
        this.showSettingsTab("general")
      } else {
        this.showError(data.error || "Failed to load system settings")
        // Show default settings
        this.settings = {}
        this.showSettingsTab("general")
      }
    } catch (error) {
      console.error("Failed to load settings:", error)
      this.showError("Network error: Failed to load system settings")
      // Show default settings
      this.settings = {}
      this.showSettingsTab("general")
    }
  }

  showSettingsTab(tab) {
    const contentDiv = document.getElementById("settingsContent")
    if (!contentDiv) return

    let html = ""

    switch (tab) {
      case "general":
        html = `
          <h5 class="mb-4">General Settings</h5>
          <form id="generalSettingsForm">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="site_name" class="form-label">Site Name</label>
                <input type="text" class="form-control" id="site_name" name="site_name" value="${this.settings?.site_name || "VectorizeAI"}">
              </div>
              <div class="col-md-6">
                <label for="site_description" class="form-label">Site Description</label>
                <input type="text" class="form-control" id="site_description" name="site_description" value="${this.settings?.site_description || "AI-powered image vectorization"}">
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="default_coins" class="form-label">Default Coins for New Users</label>
                <input type="number" class="form-control" id="default_coins" name="default_coins" value="${this.settings?.default_coins || "10"}">
              </div>
              <div class="col-md-6">
                <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" ${this.settings?.maintenance_mode ? "checked" : ""}>
                  <label class="form-check-label" for="maintenance_mode">Enable maintenance mode</label>
                </div>
              </div>
            </div>
          </form>
        `
        break
      case "email":
        html = `
          <h5 class="mb-4">Email Settings</h5>
          <form id="emailSettingsForm">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="smtp_host" class="form-label">SMTP Host</label>
                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="${this.settings?.smtp_host || ""}">
              </div>
              <div class="col-md-6">
                <label for="smtp_port" class="form-label">SMTP Port</label>
                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="${this.settings?.smtp_port || "587"}">
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="smtp_user" class="form-label">SMTP User</label>
                <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="${this.settings?.smtp_user || ""}">
              </div>
              <div class="col-md-6">
                <label for="smtp_password" class="form-label">SMTP Password</label>
                <input type="password" class="form-control" id="smtp_password" name="smtp_password">
              </div>
            </div>
          </form>
        `
        break
      case "payment":
        html = `
          <h5 class="mb-4">Payment Settings</h5>
          <form id="paymentSettingsForm">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="stripe_api_key" class="form-label">Stripe API Key</label>
                <input type="text" class="form-control" id="stripe_api_key" name="stripe_api_key" value="${this.settings?.stripe_api_key || ""}">
              </div>
              <div class="col-md-6">
                <label for="paypal_client_id" class="form-label">PayPal Client ID</label>
                <input type="text" class="form-control" id="paypal_client_id" name="paypal_client_id" value="${this.settings?.paypal_client_id || ""}">
              </div>
            </div>
          </form>
        `
        break
      case "api":
        html = `
          <h5 class="mb-4">API Settings</h5>
          <form id="apiSettingsForm">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="python_api_url" class="form-label">Python API URL</label>
                <input type="text" class="form-control" id="python_api_url" name="python_api_url" value="${this.settings?.python_api_url || "http://localhost:5000"}">
              </div>
              <div class="col-md-6">
                <label for="python_api_key" class="form-label">Python API Key</label>
                <input type="text" class="form-control" id="python_api_key" name="python_api_key" value="${this.settings?.python_api_key || ""}">
              </div>
            </div>
          </form>
        `
        break
      case "security":
        html = `
          <h5 class="mb-4">Security Settings</h5>
          <form id="securitySettingsForm">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="login_attempts_limit" class="form-label">Login Attempts Limit</label>
                <input type="number" class="form-control" id="login_attempts_limit" name="login_attempts_limit" value="${this.settings?.login_attempts_limit || "5"}">
              </div>
              <div class="col-md-6">
                <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="${this.settings?.session_timeout || "30"}">
              </div>
            </div>
          </form>
        `
        break
      default:
        html = '<p class="text-muted text-center py-3">No settings available</p>'
        break
    }

    contentDiv.innerHTML = html
  }

  async loadAnalytics() {
    try {
      const section = document.getElementById("analytics-section")
      if (!section) return

      section.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-bar me-2"></i>Analytics Dashboard</h2>
            <div>
                <select class="form-select" id="analyticsPeriod" style="width: 150px;">
                    <option value="day">Last 24 Hours</option>
                    <option value="week">Last Week</option>
                    <option value="month" selected>Last Month</option>
                    <option value="year">Last Year</option>
                </select>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="analytics-users">0</div>
                    <div class="stat-label">New Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="analytics-jobs">0</div>
                    <div class="stat-label">Jobs Completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="analytics-revenue">$0</div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="analytics-conversion">0%</div>
                    <div class="stat-label">Conversion Rate</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="admin-card">
                    <h5 class="mb-3">User Signups</h5>
                    <canvas id="userSignupsChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card">
                    <h5 class="mb-3">Job Completions</h5>
                    <canvas id="jobCompletionsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="admin-card">
                    <h5 class="mb-3">Revenue Trends</h5>
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="admin-card">
                    <h5 class="mb-3">Popular Job Types</h5>
                    <canvas id="jobTypesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
      `

      // Add event listener for period change
      document.getElementById("analyticsPeriod").addEventListener("change", (e) => {
        this.loadAnalyticsData(e.target.value)
      })

      // Load initial data
      this.loadAnalyticsData("month")
    } catch (error) {
      console.error("Failed to load analytics:", error)
      this.showError("Failed to load analytics")
    }
  }

  async loadAnalyticsData(period) {
    try {
      const response = await fetch(`api/analytics.php?period=${period}`)
      const data = await response.json()

      if (data.success) {
        // Update stats
        const analytics = data.analytics
        document.getElementById("analytics-users").textContent =
          analytics.user_signups?.reduce((sum, item) => sum + (item.count || 0), 0) || 0
        document.getElementById("analytics-jobs").textContent =
          analytics.job_completions?.reduce((sum, item) => sum + (item.count || 0), 0) || 0
        document.getElementById("analytics-revenue").textContent =
          "$" +
          (analytics.revenue?.reduce((sum, item) => sum + (Number.parseFloat(item.total) || 0), 0).toFixed(2) || "0.00")
        document.getElementById("analytics-conversion").textContent = "12.5%"

        // Create charts (simplified version without Chart.js dependency)
        this.createSimpleChart("userSignupsChart", analytics.user_signups || [])
        this.createSimpleChart("jobCompletionsChart", analytics.job_completions || [])
        this.createSimpleChart("revenueChart", analytics.revenue || [])
        this.createSimpleChart("jobTypesChart", analytics.job_types || [])
      } else {
        this.showError(data.error || "Failed to load analytics data")
      }
    } catch (error) {
      console.error("Failed to load analytics data:", error)
      this.showError("Failed to load analytics data")
    }
  }

  createSimpleChart(canvasId, data) {
    const canvas = document.getElementById(canvasId)
    if (!canvas) return

    const ctx = canvas.getContext("2d")
    const width = canvas.width
    const height = canvas.height

    // Clear canvas
    ctx.clearRect(0, 0, width, height)

    if (!data || data.length === 0) {
      ctx.fillStyle = "#6c757d"
      ctx.font = "16px Arial"
      ctx.textAlign = "center"
      ctx.fillText("No data available", width / 2, height / 2)
      return
    }

    // Simple bar chart
    const maxValue = Math.max(...data.map((item) => item.count || item.total || 0))
    const barWidth = (width / data.length) * 0.8
    const barSpacing = (width / data.length) * 0.2

    data.forEach((item, index) => {
      const value = item.count || item.total || 0
      const barHeight = (value / maxValue) * (height - 40)
      const x = index * (barWidth + barSpacing) + barSpacing / 2
      const y = height - barHeight - 20

      // Draw bar
      ctx.fillStyle = "#00ffd1"
      ctx.fillRect(x, y, barWidth, barHeight)

      // Draw value
      ctx.fillStyle = "#333"
      ctx.font = "12px Arial"
      ctx.textAlign = "center"
      ctx.fillText(value.toString(), x + barWidth / 2, y - 5)
    })
  }

  async loadSystemLogs() {
    try {
      const section = document.getElementById("logs-section")
      if (!section) return

      section.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-file-alt me-2"></i>System Logs</h2>
            <div>
                <button class="action-btn me-2" onclick="clearLogs()">
                    <i class="fas fa-trash me-1"></i> Clear Logs
                </button>
                <button class="action-btn" onclick="exportLogs()">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
        </div>
        
        <div class="admin-card">
            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-select" id="logTypeFilter">
                        <option value="">All Types</option>
                        <option value="error">Errors</option>
                        <option value="warning">Warnings</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="date" class="form-control" id="logDateFilter">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="logSearch" placeholder="Search logs...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading logs...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <nav id="logsPagination" class="mt-3">
                <ul class="pagination justify-content-center"></ul>
            </nav>
        </div>
      `

      // Add event listeners
      document.getElementById("logTypeFilter").addEventListener("change", () => {
        this.loadSystemLogsData()
      })

      document.getElementById("logDateFilter").addEventListener("change", () => {
        this.loadSystemLogsData()
      })

      document.getElementById("logSearch").addEventListener(
        "input",
        this.debounce(() => {
          this.loadSystemLogsData()
        }, 500),
      )

      // Load initial data
      this.loadSystemLogsData()
    } catch (error) {
      console.error("Failed to load system logs:", error)
      this.showError("Failed to load system logs")
    }
  }

  async loadSystemLogsData(page = 1) {
    try {
      const type = document.getElementById("logTypeFilter")?.value || ""
      const date = document.getElementById("logDateFilter")?.value || ""
      const search = document.getElementById("logSearch")?.value || ""

      const params = new URLSearchParams({
        page: page,
        type: type,
        date: date,
        search: search,
      })

      const response = await fetch(`api/system_logs.php?${params}`)
      const data = await response.json()

      if (data.success) {
        this.displaySystemLogs(data.logs)
        this.setupLogsPagination(data.pagination)
      } else {
        this.showError(data.error || "Failed to load system logs")
      }
    } catch (error) {
      console.error("Failed to load system logs:", error)
      this.showError("Failed to load system logs")
    }
  }

  displaySystemLogs(logs) {
    const tbody = document.getElementById("logsTableBody")

    if (!tbody) return

    if (!logs || logs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No logs found</td></tr>'
      return
    }

    tbody.innerHTML = logs
      .map(
        (log) => `
        <tr>
          <td>${log.id}</td>
          <td>
            <span class="status-badge ${this.getLogTypeClass(log.type)}">
              ${log.type}
            </span>
          </td>
          <td>
            <div class="text-truncate" style="max-width: 300px;" title="${log.description}">
              ${log.description}
            </div>
          </td>
          <td>${log.user_name || "System"}</td>
          <td>${log.ip_address || "N/A"}</td>
          <td>${new Date(log.created_at).toLocaleString()}</td>
          <td>
            <button class="btn btn-outline-info btn-sm" onclick="viewLog(${log.id})" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
          </td>
        </tr>
      `,
      )
      .join("")
  }

  getLogTypeClass(type) {
    switch (type) {
      case "error":
        return "status-inactive"
      case "warning":
        return "status-pending"
      case "info":
        return "status-active"
      default:
        return "status-active"
    }
  }

  setupLogsPagination(pagination) {
    const paginationElement = document.getElementById("logsPagination")
    if (!paginationElement || !pagination) return

    const { current_page, total_pages } = pagination

    if (total_pages <= 1) {
      paginationElement.innerHTML = ""
      return
    }

    let html = '<ul class="pagination">'

    // Previous button
    html += `
      <li class="page-item ${current_page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page - 1}">&laquo;</a>
      </li>
    `

    // Page numbers
    const startPage = Math.max(1, current_page - 2)
    const endPage = Math.min(total_pages, current_page + 2)

    for (let i = startPage; i <= endPage; i++) {
      html += `<li class="page-item ${i === current_page ? "active" : ""}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`
    }

    // Next button
    html += `
      <li class="page-item ${current_page === total_pages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${current_page + 1}">&raquo;</a>
      </li>
    `

    html += "</ul>"
    paginationElement.innerHTML = html

    // Add event listeners
    paginationElement.querySelectorAll(".page-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault()
        const page = Number.parseInt(link.dataset.page)
        if (!isNaN(page)) {
          this.loadSystemLogsData(page)
        }
      })
    })
  }

  async saveSettings() {
    try {
      const activeTab = document.querySelector(".settings-nav .list-group-item.active")?.dataset.settingsTab
      const form = document.getElementById(`${activeTab}SettingsForm`)

      if (!form) return

      const formData = new FormData(form)
      formData.append("tab", activeTab)

      const response = await fetch("api/save_settings.php", {
        method: "POST",
        body: formData,
      })

      const result = await response.json()

      if (result.success) {
        this.showSuccess("Settings saved successfully")
      } else {
        this.showError(result.error || "Failed to save settings")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  // User management methods with full functionality
  async editUser(userId) {
    try {
      const response = await fetch(`api/edit_user.php?id=${userId}`)
      const result = await response.json()

      if (result.success) {
        const user = result.user
        const modalBody = document.getElementById("userEditModalBody")

        modalBody.innerHTML = `
          <form id="editUserForm">
            <input type="hidden" id="editUserId" value="${user.id}">
            <div class="mb-3">
              <label for="editUserName" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="editUserName" value="${user.full_name}" required>
            </div>
            <div class="mb-3">
              <label for="editUserEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="editUserEmail" value="${user.email}" required>
            </div>
            <div class="mb-3">
              <label for="editUserRole" class="form-label">Role</label>
              <select class="form-control" id="editUserRole">
                <option value="user" ${user.role === "user" ? "selected" : ""}>User</option>
                <option value="admin" ${user.role === "admin" ? "selected" : ""}>Admin</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="editUserCoins" class="form-label">Coins</label>
              <input type="number" class="form-control" id="editUserCoins" value="${user.coins}" min="0">
            </div>
          </form>
        `

        // Setup save button event
        document.getElementById("saveUserBtn").onclick = () => this.saveUserChanges()

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById("userEditModal"))
        modal.show()
      } else {
        this.showError(result.error || "Failed to load user data")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  async saveUserChanges() {
    try {
      const userId = document.getElementById("editUserId").value
      const fullName = document.getElementById("editUserName").value
      const email = document.getElementById("editUserEmail").value
      const role = document.getElementById("editUserRole").value
      const coins = document.getElementById("editUserCoins").value

      const response = await fetch("api/edit_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: userId,
          full_name: fullName,
          email: email,
          role: role,
          coins: coins,
        }),
      })

      const result = await response.json()

      if (result.success) {
        this.showSuccess("User updated successfully")
        const modal = bootstrap.Modal.getInstance(document.getElementById("userEditModal"))
        modal.hide()
        this.loadUsers() // Refresh the users list
      } else {
        this.showError(result.error || "Failed to update user")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  async viewUser(userId) {
    try {
      const response = await fetch(`api/view_user.php?id=${userId}`)
      const result = await response.json()

      if (result.success) {
        const { user, subscription, job_stats, recent_jobs, payments } = result
        const modalBody = document.getElementById("userViewModalBody")

        modalBody.innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6>User Information</h6>
              <table class="table table-sm">
                <tr><td><strong>ID:</strong></td><td>${user.id}</td></tr>
                <tr><td><strong>Name:</strong></td><td>${user.full_name}</td></tr>
                <tr><td><strong>Email:</strong></td><td>${user.email}</td></tr>
                <tr><td><strong>Role:</strong></td><td><span class="badge ${user.role === "admin" ? "bg-warning" : "bg-primary"}">${user.role || "user"}</span></td></tr>
                <tr><td><strong>Coins:</strong></td><td>${user.coins}</td></tr>
                <tr><td><strong>Joined:</strong></td><td>${new Date(user.created_at).toLocaleDateString()}</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6>Current Subscription</h6>
              ${
                subscription
                  ? `
                <table class="table table-sm">
                  <tr><td><strong>Plan:</strong></td><td>${subscription.plan_name}</td></tr>
                  <tr><td><strong>Price:</strong></td><td>$${subscription.price}</td></tr>
                  <tr><td><strong>Start Date:</strong></td><td>${new Date(subscription.start_date).toLocaleDateString()}</td></tr>
                  <tr><td><strong>End Date:</strong></td><td>${new Date(subscription.end_date).toLocaleDateString()}</td></tr>
                  <tr><td><strong>Status:</strong></td><td><span class="badge ${subscription.active ? "bg-success" : "bg-danger"}">${subscription.active ? "Active" : "Inactive"}</span></td></tr>
                </table>
              `
                  : '<p class="text-muted">No active subscription</p>'
              }
            </div>
          </div>
          
          <div class="row mt-4">
            <div class="col-md-6">
              <h6>Job Statistics</h6>
              <div class="row text-center">
                <div class="col-3">
                  <div class="border rounded p-2">
                    <div class="h5 mb-0">${job_stats.total_jobs || 0}</div>
                    <small>Total</small>
                  </div>
                </div>
                <div class="col-3">
                  <div class="border rounded p-2">
                    <div class="h5 mb-0 text-success">${job_stats.completed_jobs || 0}</div>
                    <small>Completed</small>
                  </div>
                </div>
                <div class="col-3">
                  <div class="border rounded p-2">
                    <div class="h5 mb-0 text-danger">${job_stats.failed_jobs || 0}</div>
                    <small>Failed</small>
                  </div>
                </div>
                <div class="col-3">
                  <div class="border rounded p-2">
                    <div class="h5 mb-0 text-warning">${job_stats.processing_jobs || 0}</div>
                    <small>Processing</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Recent Jobs</h6>
              ${
                recent_jobs && recent_jobs.length > 0
                  ? `
                <div class="list-group list-group-flush">
                  ${recent_jobs
                    .map(
                      (job) => `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <div class="fw-semibold">${job.original_filename || "Unknown"}</div>
                        <small class="text-muted">${new Date(job.created_at).toLocaleDateString()}</small>
                      </div>
                      <span class="badge ${this.getJobStatusClass(job.status)}">${job.status}</span>
                    </div>
                  `,
                    )
                    .join("")}
                </div>
              `
                  : '<p class="text-muted">No recent jobs</p>'
              }
            </div>
          </div>
          
          <div class="row mt-4">
            <div class="col-12">
              <h6>Payment History</h6>
              ${
                payments && payments.length > 0
                  ? `
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Method</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${payments
                        .map(
                          (payment) => `
                        <tr>
                          <td>${new Date(payment.paid_at).toLocaleDateString()}</td>
                          <td>${payment.plan_name}</td>
                          <td>$${payment.amount}</td>
                          <td>${payment.payment_method}</td>
                        </tr>
                      `,
                        )
                        .join("")}
                    </tbody>
                  </table>
                </div>
              `
                  : '<p class="text-muted">No payment history</p>'
              }
            </div>
          </div>
        `

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById("userViewModal"))
        modal.show()
      } else {
        this.showError(result.error || "Failed to load user details")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  async deleteUser(userId) {
    try {
      const response = await fetch("api/delete_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: userId }),
      })

      const result = await response.json()

      if (result.success) {
        this.showSuccess("User deleted successfully")
        this.loadUsers()
      } else {
        this.showError(result.error || "Failed to delete user")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  // Job action methods with modal implementation
  async retryJob(jobId) {
    try {
      const response = await fetch("api/retry_job.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ job_id: jobId }),
      })

      const result = await response.json()

      if (result.success) {
        this.showSuccess("Job retry initiated")
        this.loadJobs()
      } else {
        this.showError(result.error || "Failed to retry job")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  async viewJob(jobId) {
    try {
      const response = await fetch(`api/view_job.php?id=${jobId}`)
      const result = await response.json()

      if (result.success) {
        // Show job details in modal
        const modalBody = document.getElementById("jobModalBody")
        const job = result.job

        modalBody.innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6>Job Information</h6>
              <table class="table table-sm">
                <tr><td><strong>ID:</strong></td><td>${job.id}</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="status-badge ${this.getJobStatusClass(job.status)}">${job.status}</span></td></tr>
                <tr><td><strong>Filename:</strong></td><td>${job.original_filename || "Unknown"}</td></tr>
                <tr><td><strong>Coins Used:</strong></td><td>${job.coins_used || 1}</td></tr>
                <tr><td><strong>Created:</strong></td><td>${new Date(job.created_at).toLocaleString()}</td></tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6>User Information</h6>
              <table class="table table-sm">
                <tr><td><strong>User:</strong></td><td>${job.user_name || "Unknown"}</td></tr>
                <tr><td><strong>Email:</strong></td><td>${job.user_email || "Unknown"}</td></tr>
                <tr><td><strong>User ID:</strong></td><td>${job.user_id}</td></tr>
              </table>
            </div>
          </div>
          ${
            job.error_message
              ? `
            <div class="mt-3">
              <h6>Error Details</h6>
              <div class="alert alert-danger">
                ${job.error_message}
              </div>
            </div>
          `
              : ""
          }
        `

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById("jobModal"))
        modal.show()
      } else {
        this.showError(result.error || "Failed to load job details")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  async deleteJob(jobId) {
    try {
      const response = await fetch("api/delete_job.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ job_id: jobId }),
      })

      const result = await response.json()

      if (result.success) {
        this.showSuccess("Job deleted successfully")
        this.loadJobs()
      } else {
        this.showError(result.error || "Failed to delete job")
      }
    } catch (error) {
      this.showError("Network error occurred")
    }
  }

  showSuccess(message) {
    this.showAlert(message, "success")
  }

  showError(message) {
    this.showAlert(message, "danger")
  }

  showAlert(message, type) {
    const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show alert-custom" role="alert">
                <i class="fas ${type === "success" ? "fa-check-circle" : "fa-exclamation-triangle"} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `

    const container = document.querySelector(".flex-grow-1")
    if (container) {
      container.insertAdjacentHTML("afterbegin", alertHtml)

      // Auto-remove after 5 seconds
      setTimeout(() => {
        const alert = container.querySelector(".alert")
        if (alert) {
          alert.remove()
        }
      }, 5000)
    }
  }

  debounce(func, wait) {
    let timeout
    return function (...args) {
      clearTimeout(timeout)
      timeout = setTimeout(() => func.apply(this, args), wait)
    }
  }
}

// Global functions for button actions
function editUser(userId) {
  admin.editUser(userId)
}

function viewUser(userId) {
  admin.viewUser(userId)
}

function deleteUser(userId) {
  if (
    confirm(
      "Are you sure you want to delete this user? This will also delete all their jobs, payments, and related data.",
    )
  ) {
    admin.deleteUser(userId)
  }
}

function retryJob(jobId) {
  admin.retryJob(jobId)
}

function viewJob(jobId) {
  admin.viewJob(jobId)
}

function deleteJob(jobId) {
  if (confirm("Are you sure you want to delete this job?")) {
    admin.deleteJob(jobId)
  }
}

function viewLog(logId) {
  alert("View log details functionality coming soon")
}

function exportSubscriptions() {
  window.open("api/export_subscriptions.php", "_blank")
}

function exportLogs() {
  window.open("api/export_logs.php", "_blank")
}

function clearLogs() {
  if (confirm("Clear all system logs?")) {
    alert("Clear logs functionality coming soon")
  }
}

// Initialize admin dashboard
let admin
document.addEventListener("DOMContentLoaded", () => {
  admin = new AdminDashboard()
})
