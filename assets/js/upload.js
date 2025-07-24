// Enhanced Upload handler with complete error prevention
function debugLog(message, data = null) {
  console.log(`[ImageUploader] ${message}`, data || "")
}

class ImageUploader {
  constructor() {
    debugLog("🚀 Initializing ImageUploader...")

    // Initialize properties with safe defaults
    this.isProcessing = false
    this.currentMode = "single"
    this.bulkFiles = []
    this.bulkResultsArray = []
    this.currentImageData = null
    this.initialized = false
    this.isValidPage = false

    // Safe initialization
    this.safeInit()
  }

  safeInit() {
    try {
      const initWhenReady = () => {
        if (this.shouldInitialize()) {
          this.isValidPage = true
          this.performInitialization()
        } else {
          debugLog("❌ Not an upload page, skipping initialization")
        }
      }

      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initWhenReady)
      } else {
        setTimeout(initWhenReady, 200)
      }
    } catch (error) {
      debugLog("❌ Initialization failed safely:", error.message)
    }
  }

  shouldInitialize() {
    // Check for upload-related elements
    const uploadIndicators = [
      "#uploadForm",
      "#bulkForm",
      "#uploadArea",
      "#bulkUploadArea",
      ".upload-container",
      "[data-upload]",
    ]

    return uploadIndicators.some((selector) => {
      try {
        return document.querySelector(selector) !== null
      } catch (e) {
        return false
      }
    })
  }

  performInitialization() {
    try {
      // Get DOM elements safely
      this.form = this.safeGetElement("uploadForm")
      this.bulkForm = this.safeGetElement("bulkForm")
      this.uploadArea = this.safeGetElement("uploadArea")
      this.bulkUploadArea = this.safeGetElement("bulkUploadArea")
      this.fileInput = this.safeGetElement("imageFile")
      this.bulkFileInput = this.safeGetElement("bulkImageFiles")
      this.urlInput = this.safeGetElement("imageUrl")
      this.submitBtn = this.safeGetElement("vectorizeBtn")
      this.bulkSubmitBtn = this.safeGetElement("bulkVectorizeBtn")
      this.progressArea = this.safeGetElement("progressArea")
      this.resultArea = this.safeGetElement("resultArea")
      this.errorArea = this.safeGetElement("errorArea")
      this.coinsCount = this.safeGetElement("coinsCount")
      this.imagePreviewArea = this.safeGetElement("imagePreviewArea")
      this.originalImagePreview = this.safeGetElement("originalImagePreview")
      this.vectorizedImagePreview = this.safeGetElement("vectorizedImagePreview")

      // Bulk upload elements
      this.bulkPreviewGrid = this.safeGetElement("bulkPreviewGrid")
      this.bulkProgress = this.safeGetElement("bulkProgress")
      this.bulkProgressBar = this.safeGetElement("bulkProgressBar")
      this.bulkProgressText = this.safeGetElement("bulkProgressText")
      this.bulkResultsDiv = this.safeGetElement("bulkResults")
      this.bulkDownloadBtn = this.safeGetElement("bulkDownloadBtn")

      // Mode toggle elements
      this.singleModeBtn = this.safeGetElement("singleModeBtn")
      this.bulkModeBtn = this.safeGetElement("bulkModeBtn")
      this.singleUploadForm = this.safeGetElement("singleUploadForm")
      this.bulkUploadForm = this.safeGetElement("bulkUploadForm")

      if (!this.form) {
        debugLog("❌ No upload form found")
        return
      }

      this.setupEventListeners()
      this.updateSubmitButton()
      this.initialized = true
      debugLog("✅ ImageUploader initialized successfully")
    } catch (error) {
      debugLog("❌ Initialization failed:", error.message)
    }
  }

  setupEventListeners() {
    if (!this.isValidPage) return

    debugLog("🔧 Setting up event listeners...")

    try {
      // Mode toggle with safety checks
      this.safeAddEventListener(this.singleModeBtn, "click", () => {
        debugLog("🔄 Switching to single mode")
        this.switchMode("single")
      })

      this.safeAddEventListener(this.bulkModeBtn, "click", () => {
        debugLog("🔄 Switching to bulk mode")
        this.switchMode("bulk")
      })

      // Single upload events
      this.safeAddEventListener(this.uploadArea, "click", () => {
        if (this.fileInput) this.fileInput.click()
      })

      this.safeAddEventListener(this.uploadArea, "dragover", this.handleDragOver.bind(this))
      this.safeAddEventListener(this.uploadArea, "dragleave", this.handleDragLeave.bind(this))
      this.safeAddEventListener(this.uploadArea, "drop", this.handleDrop.bind(this))

      this.safeAddEventListener(this.fileInput, "change", this.handleFileSelect.bind(this))
      this.safeAddEventListener(this.urlInput, "input", this.handleUrlInput.bind(this))
      this.safeAddEventListener(this.form, "submit", this.handleSubmit.bind(this))

      // Bulk upload events
      this.safeAddEventListener(this.bulkUploadArea, "click", (e) => {
        if (!e.target.closest("button") && this.bulkFileInput) {
          debugLog("🖱️ Bulk upload area clicked")
          this.bulkFileInput.click()
        }
      })

      this.safeAddEventListener(this.bulkUploadArea, "dragover", this.handleBulkDragOver.bind(this))
      this.safeAddEventListener(this.bulkUploadArea, "dragleave", this.handleBulkDragLeave.bind(this))
      this.safeAddEventListener(this.bulkUploadArea, "drop", this.handleBulkDrop.bind(this))

      this.safeAddEventListener(this.bulkFileInput, "change", this.handleBulkFileSelect.bind(this))

      this.safeAddEventListener(this.bulkForm, "submit", (e) => {
        debugLog("📤 Bulk form submit event triggered!")
        e.preventDefault()
        this.handleBulkSubmit(e)
      })

      this.safeAddEventListener(this.bulkDownloadBtn, "click", this.downloadAllSVGs.bind(this))

      debugLog("✅ Event listeners setup complete")
    } catch (error) {
      debugLog("❌ Error setting up event listeners:", error.message)
    }
  }

  safeAddEventListener(element, event, handler) {
    if (!element || !handler) return

    try {
      element.addEventListener(event, handler)
    } catch (error) {
      debugLog(`⚠️ Failed to add ${event} listener:`, error.message)
    }
  }

  switchMode(mode) {
    if (!this.isValidPage) return

    debugLog(`🔄 Switching to mode: ${mode}`)

    try {
      this.currentMode = mode

      if (mode === "single") {
        this.safeToggleClass(this.singleModeBtn, "active", true)
        this.safeToggleClass(this.bulkModeBtn, "active", false)
        this.safeToggleClass(this.singleUploadForm, "d-none", false)
        this.safeToggleClass(this.bulkUploadForm, "d-none", true)
      } else {
        this.safeToggleClass(this.bulkModeBtn, "active", true)
        this.safeToggleClass(this.singleModeBtn, "active", false)
        this.safeToggleClass(this.singleUploadForm, "d-none", true)
        this.safeToggleClass(this.bulkUploadForm, "d-none", false)
      }

      this.resetForms()
    } catch (error) {
      debugLog("❌ Error switching mode:", error.message)
    }
  }

  safeToggleClass(element, className, add) {
    if (!element) return

    try {
      if (add) {
        element.classList.add(className)
      } else {
        element.classList.remove(className)
      }
    } catch (error) {
      debugLog(`⚠️ Failed to toggle class ${className}:`, error.message)
    }
  }

  resetForms() {
    if (!this.isValidPage) return

    debugLog("🔄 Resetting forms")

    try {
      // Reset single form
      if (this.fileInput) this.fileInput.value = ""
      if (this.urlInput) this.urlInput.value = ""
      this.resetUploadArea()
      this.hideImagePreview()

      // Reset bulk form
      this.bulkFiles = []
      this.bulkResultsArray = []
      if (this.bulkFileInput) this.bulkFileInput.value = ""
      this.updateBulkPreview()
      this.hideBulkAreas()

      this.updateSubmitButton()
    } catch (error) {
      debugLog("❌ Error resetting forms:", error.message)
    }
  }

  // Single upload methods with safety checks
  handleDragOver(e) {
    if (!e || !this.uploadArea) return
    e.preventDefault()
    this.safeToggleClass(this.uploadArea, "dragover", true)
  }

  handleDragLeave(e) {
    if (!e || !this.uploadArea) return
    e.preventDefault()
    this.safeToggleClass(this.uploadArea, "dragover", false)
  }

  handleDrop(e) {
    if (!e || !this.uploadArea || !this.fileInput) return

    e.preventDefault()
    this.safeToggleClass(this.uploadArea, "dragover", false)

    try {
      const files = e.dataTransfer.files
      if (files && files.length > 0) {
        this.fileInput.files = files
        this.handleFileSelect()
      }
    } catch (error) {
      debugLog("❌ Error handling drop:", error.message)
    }
  }

  handleFileSelect() {
    if (!this.fileInput || !this.fileInput.files || !this.fileInput.files.length) return

    try {
      const file = this.fileInput.files[0]
      debugLog("📁 File selected:", file.name)

      if (!this.validateFile(file)) return

      if (this.urlInput) this.urlInput.value = ""
      this.updateUploadArea(file.name)
      this.showOriginalImagePreview(file)
      this.updateSubmitButton()
      this.hideAllAreas()
    } catch (error) {
      debugLog("❌ Error handling file select:", error.message)
    }
  }

  handleUrlInput() {
    if (!this.urlInput) return

    try {
      const url = this.urlInput.value.trim()
      if (url) {
        try {
          const urlObj = new URL(url)
          if (!["http:", "https:"].includes(urlObj.protocol)) {
            this.showError("Please enter a valid HTTP or HTTPS URL.")
            return
          }
        } catch (e) {
          if (url.length > 10) {
            this.showError("Please enter a valid URL.")
          }
          return
        }

        if (this.fileInput) this.fileInput.value = ""
        this.updateUploadArea("Image from URL")
        this.showOriginalImagePreview(url)
        this.hideAllAreas()
      } else {
        this.resetUploadArea()
        this.hideImagePreview()
      }
      this.updateSubmitButton()
    } catch (error) {
      debugLog("❌ Error handling URL input:", error.message)
    }
  }

  // Bulk upload methods with safety checks
  handleBulkDragOver(e) {
    if (!e || !this.bulkUploadArea) return
    e.preventDefault()
    this.safeToggleClass(this.bulkUploadArea, "dragover", true)
  }

  handleBulkDragLeave(e) {
    if (!e || !this.bulkUploadArea) return
    e.preventDefault()
    this.safeToggleClass(this.bulkUploadArea, "dragover", false)
  }

  handleBulkDrop(e) {
    if (!e || !this.bulkUploadArea) return

    e.preventDefault()
    this.safeToggleClass(this.bulkUploadArea, "dragover", false)

    try {
      const files = Array.from(e.dataTransfer.files)
      debugLog("📁 Files dropped:", files.length)
      this.addBulkFiles(files)
    } catch (error) {
      debugLog("❌ Error handling bulk drop:", error.message)
    }
  }

  handleBulkFileSelect() {
    if (!this.bulkFileInput || !this.bulkFileInput.files || !this.bulkFileInput.files.length) return

    try {
      const files = Array.from(this.bulkFileInput.files)
      debugLog("📁 Files selected:", files.length)
      this.addBulkFiles(files)
    } catch (error) {
      debugLog("❌ Error handling bulk file select:", error.message)
    }
  }

  addBulkFiles(files) {
    if (!files || !Array.isArray(files)) return

    debugLog("➕ Adding bulk files:", files.length)

    try {
      const validFiles = files.filter((file) => this.validateFile(file, false))

      if (this.bulkFiles.length + validFiles.length > 12) {
        this.showError(`Maximum 12 images allowed. You can add ${12 - this.bulkFiles.length} more.`)
        return
      }

      validFiles.forEach((file) => {
        const fileData = {
          file: file,
          id: Date.now() + Math.random(),
          status: "pending",
          preview: null,
          result: null,
        }

        try {
          const reader = new FileReader()
          reader.onload = (e) => {
            fileData.preview = e.target.result
            this.updateBulkPreview()
          }
          reader.readAsDataURL(file)
        } catch (error) {
          debugLog("❌ Error reading file:", error.message)
        }

        this.bulkFiles.push(fileData)
      })

      this.updateBulkPreview()
      this.updateSubmitButton()
    } catch (error) {
      debugLog("❌ Error adding bulk files:", error.message)
    }
  }

  updateBulkPreview() {
    if (!this.bulkPreviewGrid) return

    try {
      if (this.bulkFiles.length === 0) {
        this.safeToggleClass(this.bulkPreviewGrid, "d-none", true)
        this.safeToggleClass(this.bulkSubmitBtn, "d-none", true)
        return
      }

      this.safeToggleClass(this.bulkPreviewGrid, "d-none", false)
      this.safeToggleClass(this.bulkSubmitBtn, "d-none", false)

      this.bulkPreviewGrid.innerHTML = this.bulkFiles.map((fileData) => this.createBulkPreviewItem(fileData)).join("")
    } catch (error) {
      debugLog("❌ Error updating bulk preview:", error.message)
    }
  }

  createBulkPreviewItem(fileData) {
    if (!fileData) return ""

    try {
      return `
        <div class="bulk-preview-item" data-id="${fileData.id}">
          ${
            fileData.preview
              ? `<img src="${fileData.preview}" alt="${fileData.file.name}">`
              : '<div class="preview-placeholder">Loading...</div>'
          }
          <button type="button" class="remove-btn" onclick="window.imageUploader?.removeBulkFile('${fileData.id}')">&times;</button>
          <div class="status ${fileData.status}">${this.getStatusText(fileData.status)}</div>
        </div>
      `
    } catch (error) {
      debugLog("❌ Error creating bulk preview item:", error.message)
      return ""
    }
  }

  removeBulkFile(id) {
    if (!id) return

    debugLog("🗑️ Removing bulk file:", id)
    try {
      this.bulkFiles = this.bulkFiles.filter((file) => file.id !== id)
      this.updateBulkPreview()
      this.updateSubmitButton()
    } catch (error) {
      debugLog("❌ Error removing bulk file:", error.message)
    }
  }

  getStatusText(status) {
    switch (status) {
      case "pending":
        return "Ready"
      case "processing":
        return "Processing..."
      case "completed":
        return "Completed"
      case "failed":
        return "Failed"
      default:
        return "Unknown"
    }
  }

  validateFile(file, showError = true) {
    if (!file) return false

    try {
      const allowedTypes = ["image/png", "image/jpeg", "image/jpg"]
      if (!allowedTypes.includes(file.type)) {
        if (showError) this.showError("Please select PNG or JPEG image files.")
        return false
      }

      if (file.size > 5 * 1024 * 1024) {
        if (showError) this.showError("File size must be less than 5MB.")
        return false
      }

      return true
    } catch (error) {
      debugLog("❌ Error validating file:", error.message)
      return false
    }
  }

  async handleBulkSubmit(e) {
    if (!e || !this.isValidPage) return

    e.preventDefault()
    debugLog("🚀 Bulk form submitted!")

    if (this.isProcessing) {
      debugLog("❌ Already processing, ignoring submission")
      return
    }

    if (this.bulkFiles.length === 0) {
      debugLog("❌ No files to process")
      this.showError("Please select at least one image to vectorize.")
      return
    }

    debugLog(`✅ Starting bulk upload with ${this.bulkFiles.length} files`)

    try {
      this.setProcessingState(true)
      this.hideBulkAreas()
      this.showBulkProgress()
      this.initializeProcessingQueue()

      const groupId = Date.now().toString()
      let completed = 0
      let failed = 0
      this.bulkResultsArray = []

      this.updateCurrentProcessingStatus("Initializing bulk upload...", "Setting up processing queue")

      for (let i = 0; i < this.bulkFiles.length; i++) {
        const fileData = this.bulkFiles[i]

        debugLog(`📤 Processing file ${i + 1}/${this.bulkFiles.length}: ${fileData.file.name}`)

        this.updateCurrentProcessingStatus(
          `Processing image ${i + 1} of ${this.bulkFiles.length}`,
          `Working on: ${fileData.file.name}`,
        )

        fileData.status = "processing"
        this.updateProcessingQueue()
        this.updateBulkPreview()

        try {
          const result = await this.processIndividualImage(fileData, groupId, i)

          if (result && result.success) {
            fileData.status = "completed"
            fileData.result = result
            this.bulkResultsArray.push(result)
            completed++

            debugLog(`✅ Completed: ${fileData.file.name}`)
            this.updateCurrentProcessingStatus(
              `✅ Completed: ${fileData.file.name}`,
              `Successfully vectorized (${completed}/${this.bulkFiles.length} done)`,
            )
          } else {
            throw new Error(result?.error || "Processing failed - no result returned")
          }
        } catch (error) {
          debugLog(`❌ Failed: ${fileData.file.name}`, error.message)
          fileData.status = "failed"
          failed++

          this.updateCurrentProcessingStatus(
            `❌ Failed: ${fileData.file.name}`,
            `Error: ${error.message} (${failed} failed)`,
          )
        }

        this.updateProcessingQueue()
        this.updateBulkPreview()
        this.updateBulkProgress(i + 1, this.bulkFiles.length)

        if (i < this.bulkFiles.length - 1) {
          await new Promise((resolve) => setTimeout(resolve, 1000))
        }
      }

      debugLog(`🎉 Bulk processing complete: ${completed} successful, ${failed} failed`)
      this.updateCurrentProcessingStatus("🎉 Bulk processing complete!", `${completed} successful, ${failed} failed`)

      setTimeout(() => {
        this.setProcessingState(false)
        this.hideBulkProgress()
        this.showBulkResults(completed, failed)
      }, 2000)
    } catch (error) {
      debugLog("💥 Bulk processing error:", error.message)
      this.setProcessingState(false)
      this.hideBulkProgress()
      this.showError(`Bulk processing failed: ${error.message}`)
    }
  }

  async processIndividualImage(fileData, groupId, position) {
    if (!fileData || !fileData.file) {
      throw new Error("Invalid file data")
    }

    debugLog(`🔄 Processing individual image: ${fileData.file.name}`)

    try {
      const formData = new FormData()
      const csrfToken = document.querySelector('input[name="csrf_token"]')

      if (!csrfToken) {
        throw new Error("CSRF token not found")
      }

      formData.append("csrf_token", csrfToken.value)
      formData.append("upload_mode", "bulk")
      formData.append("bulk_group_id", groupId)
      formData.append("bulk_position", position.toString())
      formData.append("image", fileData.file)

      debugLog("📡 Sending request to upload_handler.php")

      const controller = new AbortController()
      const timeoutId = setTimeout(() => {
        debugLog("⏰ Request timeout for", fileData.file.name)
        controller.abort()
      }, 300000)

      const response = await fetch("php/upload_handler.php", {
        method: "POST",
        body: formData,
        signal: controller.signal,
      })

      clearTimeout(timeoutId)

      debugLog(`📨 Response received for ${fileData.file.name}:`, {
        status: response.status,
        ok: response.ok,
      })

      if (!response.ok) {
        throw new Error(`Server error: ${response.status} ${response.statusText}`)
      }

      const responseText = await response.text()
      debugLog(`📄 Response text for ${fileData.file.name}:`, responseText.substring(0, 200) + "...")

      let result
      try {
        result = JSON.parse(responseText)
      } catch (parseError) {
        debugLog("❌ JSON parse error for", fileData.file.name, parseError.message)
        throw new Error("Invalid response from server - not valid JSON")
      }

      debugLog(`✅ Parsed result for ${fileData.file.name}:`, result)

      if (!result.success) {
        throw new Error(result.error || "Unknown server error")
      }

      return result
    } catch (error) {
      if (error.name === "AbortError") {
        throw new Error("Processing timed out - image may be too large")
      }
      debugLog(`💥 Request failed for ${fileData.file.name}:`, error.message)
      throw error
    }
  }

  initializeProcessingQueue() {
    const queueList = document.getElementById("queueList")
    if (!queueList) return

    try {
      queueList.innerHTML = this.bulkFiles.map((fileData) => this.createQueueItem(fileData)).join("")
    } catch (error) {
      debugLog("❌ Error initializing processing queue:", error.message)
    }
  }

  createQueueItem(fileData) {
    if (!fileData) return ""

    try {
      return `
        <div class="queue-item pending" data-id="${fileData.id}">
          <span class="queue-item-icon">📄</span>
          <span class="queue-item-name">${fileData.file.name}</span>
          <span class="queue-item-status">Pending</span>
        </div>
      `
    } catch (error) {
      debugLog("❌ Error creating queue item:", error.message)
      return ""
    }
  }

  updateProcessingQueue() {
    try {
      this.bulkFiles.forEach((fileData) => {
        const queueItem = document.querySelector(`[data-id="${fileData.id}"]`)
        if (!queueItem) return

        queueItem.classList.remove("pending", "processing", "completed", "failed")
        queueItem.classList.add(fileData.status)

        const icon = queueItem.querySelector(".queue-item-icon")
        const status = queueItem.querySelector(".queue-item-status")

        switch (fileData.status) {
          case "pending":
            if (icon) icon.textContent = "📄"
            if (status) status.textContent = "Pending"
            break
          case "processing":
            if (icon) icon.textContent = "⚙️"
            if (status) status.textContent = "Processing"
            break
          case "completed":
            if (icon) icon.textContent = "✅"
            if (status) status.textContent = "Completed"
            break
          case "failed":
            if (icon) icon.textContent = "❌"
            if (status) status.textContent = "Failed"
            break
        }
      })
    } catch (error) {
      debugLog("❌ Error updating processing queue:", error.message)
    }
  }

  updateCurrentProcessingStatus(mainText, detailText) {
    try {
      const currentProcessingText = document.getElementById("currentProcessingText")
      const currentProcessingDetails = document.getElementById("currentProcessingDetails")

      if (currentProcessingText) currentProcessingText.textContent = mainText
      if (currentProcessingDetails) currentProcessingDetails.textContent = detailText
    } catch (error) {
      debugLog("❌ Error updating processing status:", error.message)
    }
  }

  showBulkProgress() {
    this.safeToggleClass(this.bulkProgress, "d-none", false)
    this.updateBulkProgress(0, this.bulkFiles.length)
    this.updateCurrentProcessingStatus("Starting bulk upload...", "Preparing to process images")
  }

  hideBulkProgress() {
    this.safeToggleClass(this.bulkProgress, "d-none", true)
  }

  updateBulkProgress(current, total) {
    if (!this.bulkProgressBar || !this.bulkProgressText) return

    try {
      const percentage = (current / total) * 100

      this.bulkProgressBar.style.width = `${percentage}%`

      if (percentage < 50) {
        this.bulkProgressBar.className = "progress-bar progress-bar-striped progress-bar-animated bg-info"
      } else if (percentage < 100) {
        this.bulkProgressBar.className = "progress-bar progress-bar-striped progress-bar-animated bg-warning"
      } else {
        this.bulkProgressBar.className = "progress-bar progress-bar-striped bg-success"
      }

      this.bulkProgressText.textContent = `${current} / ${total}`
    } catch (error) {
      debugLog("❌ Error updating bulk progress:", error.message)
    }
  }

  showBulkResults(completed, failed) {
    debugLog("📊 Showing bulk results:", { completed, failed, totalResults: this.bulkResultsArray.length })

    if (!this.bulkResultsDiv) return

    try {
      this.safeToggleClass(this.bulkResultsDiv, "d-none", false)
      const alert = this.bulkResultsDiv.querySelector(".alert")

      if (!alert) return

      if (failed === 0) {
        alert.className = "alert alert-success"
        const h5 = alert.querySelector("h5")
        const p = alert.querySelector("p")
        if (h5) h5.textContent = "✅ All Images Processed Successfully!"
        if (p) p.textContent = `${completed} images have been vectorized. Click below to download all SVG files.`
        if (this.bulkDownloadBtn) this.bulkDownloadBtn.style.display = "block"
      } else if (completed === 0) {
        alert.className = "alert alert-danger"
        const h5 = alert.querySelector("h5")
        const p = alert.querySelector("p")
        if (h5) h5.textContent = "❌ Processing Failed"
        if (p) p.textContent = `All ${failed} images failed to process. Please try again.`
        if (this.bulkDownloadBtn) this.bulkDownloadBtn.style.display = "none"
      } else {
        alert.className = "alert alert-warning"
        const h5 = alert.querySelector("h5")
        const p = alert.querySelector("p")
        if (h5) h5.textContent = "⚠️ Partial Success"
        if (p)
          p.textContent = `${completed} images processed successfully, ${failed} failed. You can download the successful ones.`
        if (this.bulkDownloadBtn) this.bulkDownloadBtn.style.display = "block"
      }
    } catch (error) {
      debugLog("❌ Error showing bulk results:", error.message)
    }
  }

  async downloadAllSVGs() {
    debugLog("📥 Starting bulk download")

    try {
      const completedFiles = this.bulkFiles.filter((file) => file.status === "completed" && file.result)

      if (completedFiles.length === 0) {
        alert("No completed files to download")
        return
      }

      if (this.bulkDownloadBtn) {
        this.bulkDownloadBtn.disabled = true
        this.bulkDownloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Downloading...'
      }

      let downloadCount = 0

      for (const fileData of completedFiles) {
        if (fileData.result && fileData.result.svg_url) {
          try {
            const link = document.createElement("a")
            link.href = fileData.result.svg_url
            link.download = fileData.result.svg_filename || `${fileData.file.name.split(".")[0]}.svg`
            link.style.display = "none"
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)

            downloadCount++

            if (this.bulkDownloadBtn) {
              this.bulkDownloadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Downloading... (${downloadCount}/${completedFiles.length})`
            }

            await new Promise((resolve) => setTimeout(resolve, 300))
          } catch (error) {
            debugLog(`❌ Failed to download ${fileData.result.svg_filename}:`, error.message)
          }
        }
      }

      if (this.bulkDownloadBtn) {
        this.bulkDownloadBtn.disabled = false
        this.bulkDownloadBtn.innerHTML = `📥 Download All SVGs (${downloadCount})`
      }

      debugLog(`✅ Bulk download complete: ${downloadCount} files downloaded`)
    } catch (error) {
      debugLog("❌ Error in bulk download:", error.message)
    }
  }

  hideBulkAreas() {
    this.safeToggleClass(this.bulkProgress, "d-none", true)
    this.safeToggleClass(this.bulkResultsDiv, "d-none", true)
  }

  showOriginalImagePreview(source) {
    if (!this.imagePreviewArea) return

    try {
      this.safeToggleClass(this.imagePreviewArea, "d-none", false)

      if (typeof source === "string") {
        if (this.originalImagePreview) {
          this.originalImagePreview.innerHTML = `<img src="${source}" alt="Original Image" class="preview-image" onclick="openImageModal?.('${source}', 'Original Image')" onerror="this.parentElement.innerHTML='<div class=\\'preview-placeholder\\'>Failed to load image</div>'">`
        }
        this.currentImageData = { type: "url", source: source }
      } else {
        const reader = new FileReader()
        reader.onload = (e) => {
          if (this.originalImagePreview) {
            this.originalImagePreview.innerHTML = `<img src="${e.target.result}" alt="Original Image" class="preview-image" onclick="openImageModal?.('${e.target.result}', 'Original Image')">`
          }
          this.currentImageData = { type: "file", source: e.target.result }
        }
        reader.readAsDataURL(source)
      }

      if (this.vectorizedImagePreview) {
        this.vectorizedImagePreview.innerHTML =
          '<div class="preview-placeholder">Vectorized image will appear here</div>'
      }
    } catch (error) {
      debugLog("❌ Error showing image preview:", error.message)
    }
  }

  hideImagePreview() {
    this.safeToggleClass(this.imagePreviewArea, "d-none", true)
    this.currentImageData = null
  }

  updateUploadArea(fileName) {
    if (!this.uploadArea) return

    try {
      const content = this.uploadArea.querySelector(".upload-content")
      if (content) {
        content.innerHTML = `
          <i class="upload-icon">✅</i>
          <p class="mb-2"><strong>${fileName}</strong></p>
          <small class="text-muted">Click to choose a different file</small>
        `
      }
    } catch (error) {
      debugLog("❌ Error updating upload area:", error.message)
    }
  }

  resetUploadArea() {
    if (!this.uploadArea) return

    try {
      const content = this.uploadArea.querySelector(".upload-content")
      if (content) {
        content.innerHTML = `
          <i class="upload-icon">📁</i>
          <p class="mb-2">Drop your image here or click to browse</p>
          <small class="text-muted">PNG, JPG up to 5MB</small>
        `
      }
    } catch (error) {
      debugLog("❌ Error resetting upload area:", error.message)
    }
  }

  updateSubmitButton() {
    try {
      if (this.currentMode === "single") {
        const hasFile = this.fileInput && this.fileInput.files && this.fileInput.files.length > 0
        const hasUrl = this.urlInput && this.urlInput.value.trim().length > 0
        const isValid = (hasFile || hasUrl) && !this.isProcessing

        if (this.submitBtn) this.submitBtn.disabled = !isValid
      } else {
        const hasFiles = this.bulkFiles.length > 0
        const isValid = hasFiles && !this.isProcessing

        if (this.bulkSubmitBtn) this.bulkSubmitBtn.disabled = !isValid
      }
    } catch (error) {
      debugLog("❌ Error updating submit button:", error.message)
    }
  }

  async handleSubmit(e) {
    if (!e || !this.isValidPage) return

    e.preventDefault()
    if (this.isProcessing) return

    try {
      this.setProcessingState(true)
      this.hideAllAreas()
      this.showProgress()

      const formData = new FormData(this.form)
      const controller = new AbortController()
      const timeoutId = setTimeout(() => controller.abort(), 300000)

      const response = await fetch("php/upload_handler.php", {
        method: "POST",
        body: formData,
        signal: controller.signal,
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        throw new Error(`Server error: ${response.status} ${response.statusText}`)
      }

      const responseText = await response.text()
      let result

      try {
        result = JSON.parse(responseText)
      } catch (parseError) {
        debugLog("❌ JSON parse error:", parseError.message)
        throw new Error("Invalid response from server")
      }

      if (result.success) {
        this.showVectorizedResult(result.svg_url)
        this.showResult(result.svg_url, result.svg_filename)
      } else {
        this.showError(result.error || "Upload failed")
      }
    } catch (error) {
      if (error.name === "AbortError") {
        this.showError("Processing timed out. Please try with a smaller image or try again.")
      } else {
        this.showError("Network error: " + error.message)
      }
    }
  }

  showVectorizedResult(svgUrl) {
    if (!this.vectorizedImagePreview || !svgUrl) return

    try {
      this.vectorizedImagePreview.innerHTML = `<img src="${svgUrl}" alt="Vectorized Image" class="preview-image" onclick="openImageModal?.('${svgUrl}', 'Vectorized Result (SVG)')" onerror="this.parentElement.innerHTML='<div class=\\'preview-placeholder\\'>Failed to load vectorized image</div>'">`
    } catch (error) {
      debugLog("❌ Error showing vectorized result:", error.message)
    }
  }

  setProcessingState(processing) {
    this.isProcessing = processing

    try {
      if (this.currentMode === "single" && this.submitBtn) {
        const btnText = this.submitBtn.querySelector(".btn-text")
        const spinner = this.submitBtn.querySelector(".spinner-border")

        if (processing) {
          if (btnText) btnText.textContent = "Processing..."
          this.safeToggleClass(spinner, "d-none", false)
          this.submitBtn.disabled = true

          if (this.currentImageData && this.vectorizedImagePreview) {
            this.vectorizedImagePreview.innerHTML =
              '<div class="preview-placeholder"><div class="spinner-border spinner-border-sm me-2"></div>Processing...</div>'
          }
        } else {
          if (btnText) btnText.textContent = "Vectorize Image"
          this.safeToggleClass(spinner, "d-none", true)
          this.updateSubmitButton()
        }
      } else if (this.currentMode === "bulk" && this.bulkSubmitBtn) {
        const btnText = this.bulkSubmitBtn.querySelector(".btn-text")
        const spinner = this.bulkSubmitBtn.querySelector(".spinner-border")

        if (processing) {
          if (btnText) btnText.textContent = "Processing..."
          this.safeToggleClass(spinner, "d-none", false)
          this.bulkSubmitBtn.disabled = true
        } else {
          if (btnText) btnText.textContent = "Vectorize All Images"
          this.safeToggleClass(spinner, "d-none", true)
          this.updateSubmitButton()
        }
      }
    } catch (error) {
      debugLog("❌ Error setting processing state:", error.message)
    }
  }

  hideAllAreas() {
    this.safeToggleClass(this.progressArea, "d-none", true)
    this.safeToggleClass(this.resultArea, "d-none", true)
    this.safeToggleClass(this.errorArea, "d-none", true)
  }

  showProgress() {
    this.hideAllAreas()
    this.safeToggleClass(this.progressArea, "d-none", false)
  }

  showResult(svgUrl, svgFilename) {
    this.hideAllAreas()
    this.setProcessingState(false)

    try {
      const downloadLink = document.getElementById("downloadLink")
      if (downloadLink) {
        downloadLink.href = svgUrl
        if (svgFilename) {
          const downloadText = downloadLink.querySelector(".btn-text") || downloadLink
          downloadText.textContent = `Download ${svgFilename}`
        }
      }

      this.safeToggleClass(this.resultArea, "d-none", false)
      this.updateCoinsCount()
    } catch (error) {
      debugLog("❌ Error showing result:", error.message)
    }
  }

  showError(message) {
    this.hideAllAreas()
    this.setProcessingState(false)

    try {
      if (this.currentImageData && this.vectorizedImagePreview) {
        this.vectorizedImagePreview.innerHTML =
          '<div class="preview-placeholder">Vectorized image will appear here</div>'
      }

      const errorMessage = document.getElementById("errorMessage")
      if (errorMessage) errorMessage.textContent = message
      this.safeToggleClass(this.errorArea, "d-none", false)
    } catch (error) {
      debugLog("❌ Error showing error:", error.message)
    }
  }

  updateCoinsCount() {
    if (!this.coinsCount) return

    try {
      const current = Number.parseInt(this.coinsCount.textContent) || 0
      this.coinsCount.textContent = Math.max(0, current - 1)
    } catch (error) {
      debugLog("❌ Error updating coins count:", error.message)
    }
  }

  safeGetElement(id) {
    try {
      const element = document.getElementById(id)
      if (!element) {
        debugLog(`⚠️ Element with ID '${id}' not found`)
      }
      return element
    } catch (error) {
      debugLog(`❌ Error getting element '${id}':`, error.message)
      return null
    }
  }
}

// Safe initialization with multiple fallbacks
let imageUploader

function initializeImageUploader() {
  if (imageUploader) return // Already initialized

  try {
    imageUploader = new ImageUploader()
    // Make it globally available for onclick handlers
    window.imageUploader = imageUploader
  } catch (error) {
    debugLog("❌ Failed to initialize ImageUploader:", error.message)
  }
}

// Multiple initialization attempts
document.addEventListener("DOMContentLoaded", initializeImageUploader)

if (document.readyState !== "loading") {
  setTimeout(initializeImageUploader, 100)
}

// Fallback initialization
setTimeout(() => {
  if (!imageUploader) {
    initializeImageUploader()
  }
}, 1000)

// Utility functions with safety checks
window.VectorizeUtils = {
  async apiCall(url, options = {}) {
    try {
      const response = await fetch(url, {
        headers: {
          "Content-Type": "application/json",
          ...options.headers,
        },
        ...options,
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      return await response.json()
    } catch (error) {
      console.error("API call failed:", error)
      throw error
    }
  },

  showToast(message, type = "info") {
    try {
      const toast = document.createElement("div")
      toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`
      toast.style.zIndex = "9999"
      toast.textContent = message

      document.body.appendChild(toast)

      setTimeout(() => {
        try {
          toast.remove()
        } catch (e) {
          // Ignore removal errors
        }
      }, 5000)
    } catch (error) {
      console.warn("Failed to show toast:", error.message)
    }
  },

  formatDate(dateString) {
    try {
      return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      })
    } catch (error) {
      console.warn("Failed to format date:", error.message)
      return "Unknown"
    }
  },

  formatFileSize(bytes) {
    try {
      if (bytes === 0) return "0 Bytes"
      const k = 1024
      const sizes = ["Bytes", "KB", "MB", "GB"]
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
    } catch (error) {
      console.warn("Failed to format file size:", error.message)
      return "Unknown size"
    }
  },
}
