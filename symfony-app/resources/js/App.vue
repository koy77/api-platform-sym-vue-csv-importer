<template>
  <div id="app">
    <div class="header">
      <div class="container">
        <h1>📦 Product Import Dashboard</h1>
        <p>Manage and monitor your product imports</p>
      </div>
    </div>

    <div class="container">
      <!-- Statistics -->
      <div class="stats">
        <div class="stat-card">
          <h3>{{ stats.total }}</h3>
          <p>Total Products</p>
        </div>
        <div class="stat-card">
          <h3>{{ stats.active }}</h3>
          <p>Active Products</p>
        </div>
        <div class="stat-card">
          <h3>{{ stats.discontinued }}</h3>
          <p>Discontinued</p>
        </div>
        <div class="stat-card">
          <h3>{{ stats.totalValue }}</h3>
          <p>Total Value (GBP)</p>
        </div>
      </div>

      <!-- Import Section -->
      <div class="card">
        <h2>Import Products from CSV</h2>
        <div class="form-group">
          <label for="csvFile">Select CSV File</label>
          <input
            type="file"
            id="csvFile"
            accept=".csv"
            @change="handleFileSelect"
            ref="fileInput"
          />
        </div>
        <button
          class="btn btn-primary"
          @click="uploadFile"
          :disabled="!selectedFile || uploading"
        >
          {{ uploading ? 'Uploading...' : 'Upload & Import' }}
        </button>
        <button
          class="btn btn-secondary"
          @click="testImport"
          :disabled="!selectedFile || uploading"
          style="margin-left: 10px;"
        >
          Test Import (No DB Insert)
        </button>

        <div v-if="importResult" class="alert" :class="importResult.type">
          <h3>{{ importResult.title }}</h3>
          <p><strong>Total:</strong> {{ importResult.total }}</p>
          <p><strong>Successful:</strong> {{ importResult.successful }}</p>
          <p><strong>Skipped:</strong> {{ importResult.skipped }}</p>
          <p><strong>Failed:</strong> {{ importResult.failed }}</p>
        </div>
      </div>

      <!-- Products Table -->
      <div class="card">
        <h2>Products</h2>
        <div style="margin-bottom: 20px;">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search products..."
            class="form-group input"
            style="max-width: 300px;"
          />
        </div>

        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <p>Loading products...</p>
        </div>

        <div v-else-if="error" class="alert alert-error">
          {{ error }}
        </div>

        <div v-else>
          <div v-if="filteredProducts.length === 0" class="alert alert-info">
            <p>No products found. Import a CSV file to get started.</p>
          </div>
          <table v-else class="table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Description</th>
                <th>Stock</th>
                <th>Price (GBP)</th>
                <th>Status</th>
                <th>Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in filteredProducts" :key="product.id || product['@id']">
                <td>{{ product.productCode || '-' }}</td>
                <td>{{ product.productName || '-' }}</td>
                <td>{{ product.productDesc || '-' }}</td>
                <td>{{ product.stock !== null && product.stock !== undefined ? product.stock : 0 }}</td>
                <td>£{{ product.price ? parseFloat(product.price).toFixed(2) : '0.00' }}</td>
                <td>
                  <span
                    class="badge"
                    :class="product.discontinued ? 'badge-danger' : 'badge-success'"
                  >
                    {{ product.discontinued ? 'Discontinued' : 'Active' }}
                  </span>
                </td>
                <td>{{ formatDate(product.added) }}</td>
                <td>
                  <button
                    class="btn btn-danger"
                    style="padding: 6px 12px; font-size: 0.875rem;"
                    @click="deleteProduct(product.id || product['@id']?.split('/').pop())"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="pagination" v-if="pagination">
            <button
              @click="loadPage(pagination.page - 1)"
              :disabled="!pagination.prev"
            >
              Previous
            </button>
            <span>Page {{ pagination.page }} of {{ pagination.lastPage }}</span>
            <button
              @click="loadPage(pagination.page + 1)"
              :disabled="!pagination.next"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

// Configure axios base URL
// In production, use relative paths (nginx will proxy /api to PHP)
// In development, Vite proxy handles it
const apiUrl = import.meta.env.VITE_API_URL || ''
if (apiUrl) {
  axios.defaults.baseURL = apiUrl
} else {
  // Use relative paths - nginx will handle proxying
  axios.defaults.baseURL = ''
}

export default {
  name: 'App',
  data() {
    return {
      products: [],
      loading: false,
      error: null,
      searchQuery: '',
      selectedFile: null,
      uploading: false,
      importResult: null,
      stats: {
        total: 0,
        active: 0,
        discontinued: 0,
        totalValue: '0.00'
      },
      pagination: null,
      currentPage: 1
    }
  },
  computed: {
    filteredProducts() {
      if (!this.searchQuery) {
        return this.products
      }
      const query = this.searchQuery.toLowerCase()
      return this.products.filter(product =>
        product.productCode.toLowerCase().includes(query) ||
        product.productName.toLowerCase().includes(query) ||
        product.productDesc.toLowerCase().includes(query)
      )
    }
  },
  mounted() {
    this.loadProducts()
    this.loadStats()
  },
  methods: {
    async loadProducts(page = 1) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/products', {
          params: { page },
          headers: {
            'Accept': 'application/ld+json'
          }
        })
        // Handle JSON-LD (Hydra) format from API Platform
        if (response.data['hydra:member']) {
          this.products = response.data['hydra:member'] || []
          const totalItems = response.data['hydra:totalItems'] || this.products.length
          const itemsPerPage = 30
          this.pagination = {
            page: page,
            lastPage: Math.ceil(totalItems / itemsPerPage),
            next: !!response.data['hydra:view']?.['hydra:next'],
            prev: !!response.data['hydra:view']?.['hydra:previous']
          }
        } else if (Array.isArray(response.data)) {
          this.products = response.data
          this.pagination = {
            page: page,
            lastPage: 1,
            next: false,
            prev: false
          }
        } else {
          this.products = []
          this.pagination = null
        }
        console.log('Loaded products:', this.products.length)
      } catch (error) {
        this.error = 'Failed to load products: ' + (error.response?.data?.message || error.message)
        console.error('Error loading products:', error)
        console.error('Error details:', error.response?.data)
        this.products = []
      } finally {
        this.loading = false
      }
    },
    async loadStats() {
      try {
        // Get all products for stats (with pagination)
        let allProducts = []
        let page = 1
        let hasMore = true
        
        while (hasMore) {
          const response = await axios.get('/api/products', {
            params: { page, itemsPerPage: 100 },
            headers: {
              'Accept': 'application/ld+json'
            }
          })
          
          // Ensure we have valid data
          if (!response.data) {
            break
          }
          
          // Handle both Hydra format and plain array
          let products = []
          if (response.data['hydra:member'] && Array.isArray(response.data['hydra:member'])) {
            products = response.data['hydra:member']
          } else if (Array.isArray(response.data)) {
            products = response.data
          }
          
          // Ensure products is an array before concatenating
          if (Array.isArray(products)) {
            allProducts = allProducts.concat(products)
          }
          
          hasMore = !!response.data['hydra:view']?.['hydra:next']
          page++
          
          // Safety limit
          if (page > 100) break
        }
        
        // Ensure allProducts is an array before using array methods
        if (!Array.isArray(allProducts)) {
          allProducts = []
        }
        
        this.stats.total = allProducts.length
        this.stats.active = allProducts.filter(p => p && !p.discontinued).length
        this.stats.discontinued = allProducts.filter(p => p && p.discontinued).length
        this.stats.totalValue = allProducts
          .filter(p => p && p.price)
          .reduce((sum, p) => sum + parseFloat(p.price || 0), 0)
          .toFixed(2)
      } catch (error) {
        console.error('Failed to load stats:', error)
        // Set defaults on error
        this.stats = {
          total: 0,
          active: 0,
          discontinued: 0,
          totalValue: '0.00'
        }
      }
    },
    handleFileSelect(event) {
      this.selectedFile = event.target.files[0]
      this.importResult = null
    },
    async uploadFile() {
      if (!this.selectedFile) return

      this.uploading = true
      this.importResult = null

      try {
        const formData = new FormData()
        formData.append('file', this.selectedFile)

        const response = await axios.post('/api/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        this.importResult = {
          type: 'alert-success',
          title: 'Import Successful!',
          ...response.data
        }

        // Reload products and stats
        await this.loadProducts()
        await this.loadStats()
      } catch (error) {
        this.importResult = {
          type: 'alert-error',
          title: 'Import Failed',
          total: 0,
          successful: 0,
          skipped: 0,
          failed: 1,
          message: error.response?.data?.message || error.message
        }
      } finally {
        this.uploading = false
      }
    },
    async testImport() {
      if (!this.selectedFile) return

      this.uploading = true
      this.importResult = null

      try {
        const formData = new FormData()
        formData.append('file', this.selectedFile)

        const response = await axios.post('/api/import?test=1', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        this.importResult = {
          type: 'alert-info',
          title: 'Test Import Results (No DB Insert)',
          ...response.data
        }
      } catch (error) {
        this.importResult = {
          type: 'alert-error',
          title: 'Test Import Failed',
          message: error.response?.data?.message || error.message
        }
      } finally {
        this.uploading = false
      }
    },
    async deleteProduct(id) {
      if (!confirm('Are you sure you want to delete this product?')) return

      try {
        // Handle both numeric ID and IRI format
        const productId = typeof id === 'string' && id.includes('/') 
          ? id.split('/').pop() 
          : id
        await axios.delete(`/api/products/${productId}`)
        await this.loadProducts(this.currentPage)
        await this.loadStats()
      } catch (error) {
        alert('Failed to delete product: ' + (error.response?.data?.message || error.message))
      }
    },
    loadPage(page) {
      if (page >= 1) {
        this.loadProducts(page)
      }
    },
    formatDate(dateString) {
      if (!dateString) return '-'
      const date = new Date(dateString)
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
    }
  }
}
</script>

