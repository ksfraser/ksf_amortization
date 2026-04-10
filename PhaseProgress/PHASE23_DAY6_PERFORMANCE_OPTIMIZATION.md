# Phase 23 Day 6: Performance Optimization & Lighthouse Audit

## Overview

This document outlines the performance optimization strategy for Phase 23 Day 6, focusing on:
- Frontend bundle optimization
- Lighthouse audit execution
- Core Web Vitals improvement
- Network performance optimization
- Build size analysis and optimization

## 1. Performance Baseline

### Current Setup
- **Build Tool**: Vite 5.0 (fast, modern bundling)
- **Framework**: Vue 3.4 with Composition API (tree-shakeable)
- **Styling**: TailwindCSS 3.3 (PurgeCSS enabled)
- **State Management**: Pinia 2.1 (lightweight)
- **Testing**: Vitest 1.0 (fast unit tests)
- **E2E Testing**: Playwright 1.40 (modern, efficient)

### Expected Performance Targets
| Metric | Target | Current |
|--------|--------|---------|
| Lighthouse Score | 90+ | ✅ Expected |
| First Contentful Paint (FCP) | <1.5s | TBD |
| Largest Contentful Paint (LCP) | <2.5s | TBD |
| Cumulative Layout Shift (CLS) | <0.1 | TBD |
| Time to Interactive (TTI) | <3.5s | TBD |
| Total Bundle Size | <300KB | TBD |
| Main (JS) Bundle | <200KB | TBD |

## 2. Bundle Analysis & Optimization

### 2.1 Install & Run Bundle Analyzer

```bash
# Install the analyzer
npm install --save-dev rollup-plugin-visualizer

# Add to vite.config.js
import { visualizer } from 'rollup-plugin-visualizer'

export default {
  plugins: [
    visualizer({
      open: true,
      gzipSize: true,
      brotliSize: true,
      filename: 'dist/stats.html'
    })
  ]
}

# Build and analyze
npm run build
```

### 2.2 Bundle Analysis Checklist

- [ ] Generate bundle visualization
- [ ] Identify large dependencies (> 50KB)
- [ ] Check for duplicate packages
- [ ] Analyze CSS bundle size
- [ ] Check for unused imports
- [ ] Verify tree-shaking effectiveness

### 2.3 Optimization Opportunities

**1. Code Splitting by Route** (if not already done)
```javascript
// router/index.js - Update component imports to be lazy
const LoginPage = () => import('../pages/LoginPage.vue')
const DashboardPage = () => import('../pages/DashboardPage.vue')
const AdminPage = () => import('../pages/admin/AdminPage.vue')
```

**2. Dynamic Component Loading**
```javascript
// For rarely-used modals or dialogs
const AdvancedModal = defineAsyncComponent(() =>
  import('../components/AdvancedModal.vue')
)
```

**3. Remove Unused CSS**
```javascript
// tailwind.config.js - Already optimized
content: [
  "./index.html",
  "./src/**/*.{vue,js,ts,jsx,tsx}",
]
// Should already be purging unused CSS
```

**4. Optimize Dependencies**
- Replace moment.js with date-fns (already using lightweight approach)
- Use tree-shaking friendly libraries
- Remove dev dependencies from production builds

## 3. Lighthouse Audit Execution

### 3.1 Chrome DevTools Audit

**Steps**:
1. Start dev server: `npm run dev`
2. Open Chrome DevTools (F12)
3. Go to Lighthouse tab
4. Select "Mobile" or "Desktop"
5. Run audit
6. Review results and recommendations
7. Export report as JSON/HTML

### 3.2 CLI Lighthouse

```bash
# Install globally
npm install -g @lhci/cli@latest

# Run audit
lhci autorun

# Or use npx
npx lighthouse http://localhost:5173 --chrome-flags="--headless=new" --output-path=./lh-report.html
```

### 3.3 Lighthouse Audit Checklist

- [ ] Performance score (target: 90+)
- [ ] Accessibility score (target: 95+)
- [ ] Best Practices score (target: 95+)
- [ ] SEO score (target: 90+)

## 4. Core Web Vitals Optimization

### 4.1 Largest Contentful Paint (LCP) Optimization

**Current Strategy**:
```javascript
// Preload critical resources
<link rel="preload" href="/fonts/RobotoMono-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/css/critical.css" as="style">

// Compress images
// SVG used where possible (already doing)
// Lazy load non-critical images
<img loading="lazy" src="..." />
```

**Checklist**:
- [ ] Preload critical resources
- [ ] Lazy load images below fold
- [ ] Optimize image format (WebP with fallback)
- [ ] Remove render-blocking resources
- [ ] Use modern CSS delivery (critical CSS inline)

### 4.2 First Input Delay (FID) / Interaction to Next Paint (INP)

**Current Strategy**:
```javascript
// Use requestIdleCallback for non-urgent work
if (window.requestIdleCallback) {
  requestIdleCallback(() => {
    // Non-urgent initialization
  })
}

// Avoid long tasks (>50ms)
// Break up work into smaller chunks
```

**Checklist**:
- [ ] Minimize JavaScript execution time
- [ ] Break up long tasks
- [ ] Use Web Workers for heavy computation
- [ ] Defer non-critical initialization

### 4.3 Cumulative Layout Shift (CLS) Optimization

**Current Strategy**:
```css
/* Reserve space for dynamic content */
.modal-container {
  min-height: 300px; /* or use aspect-ratio */
}

/* Avoid inserting content above existing content */
.notification {
  margin-top: 0; /* Never use negative margins that shift layout */
}
```

**Checklist**:
- [ ] Define dimensions for images and video
- [ ] Avoid inserting content above existing content
- [ ] Use `font-display: swap` for web fonts
- [ ] Avoid animations that trigger layout changes

## 5. Network Performance

### 5.1 HTTP Caching Strategy

```javascript
// .env.production - Add cache headers configuration
VITE_CACHE_DURATION_ASSETS=31536000  # 1 year for versioned assets
VITE_CACHE_DURATION_HTML=3600        # 1 hour for HTML
VITE_CACHE_DURATION_API=300          # 5 minutes for API responses
```

### 5.2 Compression

```bash
# Verify gzip compression enabled in vite.config.js
# Brotli compression (even better)
npm install --save-dev rollup-plugin-brotli
```

### 5.3 CDN Optimization (if applicable)

- [ ] Use CDN for static assets
- [ ] Enable browser caching headers
- [ ] Set appropriate Cache-Control headers
- [ ] Use versioned asset names (Vite does this by default)

## 6. Performance Monitoring

### 6.1 Web Vitals Monitoring

```javascript
// src/utils/performance.js
import { getCLS, getFID, getFCP, getLCP, getTTFB } from 'web-vitals'

export function initPerformanceMonitoring() {
  getCLS(console.log)  // Cumulative Layout Shift
  getFID(console.log)  // First Input Delay
  getFCP(console.log)  // First Contentful Paint
  getLCP(console.log)  // Largest Contentful Paint
  getTTFB(console.log) // Time to First Byte
}

// Use in main.js
if (import.meta.env.PROD) {
  initPerformanceMonitoring()
}
```

### 6.2 Performance API Usage

```javascript
// Track custom metrics
const perfObserver = new PerformanceObserver((list) => {
  for (const entry of list.getEntries()) {
    console.log('Performance Entry:', entry)
    // Send to analytics
  }
})

perfObserver.observe({ entryTypes: ['measure', 'navigation'] })
```

### 6.3 Analytics Integration

- [ ] Send Core Web Vitals to analytics
- [ ] Monitor performance over time
- [ ] Set up alerts for performance regressions
- [ ] Track by device type and network speed

## 7. Build Optimization Checklist

### 7.1 Vite Configuration

```javascript
// vite.config.js optimizations
export default {
  build: {
    target: 'esnext',  // Modern browsers
    minify: 'terser',  // Better minification
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['vue', 'vue-router', 'pinia'],
          'utils': ['axios']
        }
      }
    }
  }
}
```

- [ ] Enable code splitting
- [ ] Configure chunk size warnings
- [ ] Set appropriate targets
- [ ] Enable minification

### 7.2 Dependencies Review

```bash
# Find unused dependencies
npm audit

# Check outdated packages
npm outdated

# Analyze bundle
npm run build -- --analyze

# Check duplicate packages
npm ls
```

- [ ] Remove unused dependencies
- [ ] Update to latest stable versions
- [ ] Check for memory leaks
- [ ] Verify no dev dependencies in production

## 8. Testing Performance Improvements

### 8.1 Baseline Measurements

```bash
# Before optimization
npm run build
# Record bundle sizes and Lighthouse scores
```

### 8.2 Apply Optimizations

- [ ] Implement code splitting
- [ ] Remove unused packages
- [ ] Optimize images
- [ ] Minimize CSS/JS

### 8.3 Post-Optimization Measurements

```bash
# After optimization
npm run build
# Compare metrics with baseline
```

### 8.4 Performance Report

Create a summary document showing:
- Before/after bundle sizes
- Core Web Vitals improvements
- Lighthouse score changes
- Load time improvements

## 9. Phase 23 Day 6 Tasks

### Morning Session
- [ ] Run initial Lighthouse audit (desktop + mobile)
- [ ] Generate bundle analysis visualization
- [ ] Document baseline metrics
- [ ] Identify top 5 optimization opportunities

### Afternoon Session
- [ ] Implement code splitting by route
- [ ] Remove unused dependencies
- [ ] Optimize CSS delivery
- [ ] Configure caching headers

### Final Review
- [ ] Run final Lighthouse audit
- [ ] Compare pre/post metrics
- [ ] Document improvements
- [ ] Create Performance Optimization Report

## 10. Success Criteria

✅ **Day 6 Complete When**:
- Lighthouse Performance score: 90+
- LCP < 2.5s
- FCP < 1.5s
- CLS < 0.1
- Total bundle < 300KB
- Performance bottlenecks identified and documented

## 11. Commands Reference

```bash
# Development
npm run dev              # Start dev server
npm run build           # Build for production
npm run preview         # Preview production build

# Testing
npm test                # Run unit tests
npm run test:coverage   # Generate coverage report
npm run test:e2e        # Run E2E tests (when ready)

# Performance
npm run build           # Build and analyze with visualizer
npx lighthouse http://localhost:5173 --chrome-flags="--headless=new"

# Code Quality
npm run lint            # Lint code
npm run type-check      # Type checking
npm run format          # Format code
```

## 12. Performance Optimization Resources

- [Web Vitals Documentation](https://web.dev/vitals/)
- [Lighthouse Best Practices](https://developers.google.com/web/tools/lighthouse)
- [Vite Performance Guide](https://vitejs.dev/guide/build.html)
- [Vue 3 Performance](https://v3.vuejs.org/guide/best-practices/performance.html)
- [Playwright Performance](https://playwright.dev/docs/chrome-extensions)

---

**Status**: Phase 23 Day 6 Ready - Ready to execute performance optimization tasks
**Duration**: ~4-6 hours for comprehensive optimization
**Focus**: Bundle size, Core Web Vitals, Lighthouse scores
**Output**: Performance Optimization Report with before/after metrics
