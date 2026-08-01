# APSOne Core Dashboard Wireframes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add six connected monochrome core-product wireframes to the existing Figma file.

**Architecture:** Create six 1440 × 900 top-level frames in a second row below the authentication flow. Build a consistent grayscale sidebar/header shell, then add simplified screen-specific cards, tables, calendar cells, profile details, and chart placeholders.

**Tech Stack:** Figma Design, Figma Plugin API through `use_figma`, Inter typography.

## Global Constraints

- Use only white, black, and neutral gray fills and strokes.
- Use 1440 × 900 frames and arrange them horizontally below the authentication frames.
- Do not reproduce production layouts pixel-for-pixel.
- Keep every node inside its top-level frame.
- Connect Dashboard, Profile, Schedule, Today's Schedule, Create / Update Schedule, and Shift through prototype navigation.

---

### Task 1: Inspect and Create Wrappers

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Approved core-dashboard wireframe specification.
- Produces: Six top-level screen frame IDs.

- [ ] **Step 1: Inspect existing Figma screens and available fonts**
- [ ] **Step 2: Create six named 1440 × 900 wrappers in a second horizontal row**
- [ ] **Step 3: Verify wrapper names, dimensions, and positions**

### Task 2: Build the Shared Application Shell

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Six wrapper IDs.
- Produces: Sidebar, utility header, active navigation state, page title, and wireframe annotation on every screen.

- [ ] **Step 1: Add the shared shell to Dashboard, Profile, and Today's Schedule**
- [ ] **Step 2: Add the shared shell to Schedule List, Create / Update Schedule, and Shift Management**
- [ ] **Step 3: Validate shell consistency**

### Task 3: Build Dashboard, Profile, and Today's Schedule

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Shared application shell.
- Produces: Three complete monochrome wireframes.

- [ ] **Step 1: Build Dashboard station cards, KPI cards, and chart placeholders**
- [ ] **Step 2: Build Profile identity summary, tabs, and detail cards**
- [ ] **Step 3: Build Today's Schedule summary, metrics, shift panel, and staff list**
- [ ] **Step 4: Validate content hierarchy and bounds**

### Task 4: Build Schedule List, Create / Update Schedule, and Shift Management

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Shared application shell.
- Produces: Three complete monochrome wireframes.

- [ ] **Step 1: Build Schedule List metrics, period strip, and conceptual calendar**
- [ ] **Step 2: Build Create / Update Schedule actions, search, and user table**
- [ ] **Step 3: Build Shift Management summary, create action, and shift table**
- [ ] **Step 4: Validate content hierarchy and bounds**

### Task 5: Prototype Links and Visual QA

**Files:**
- Inspect and modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Six completed screen frames.
- Produces: Connected, visually verified Figma wireframes.

- [ ] **Step 1: Add navigation reactions between the six screens**
- [ ] **Step 2: Capture screenshots for all six screens**
- [ ] **Step 3: Audit grayscale paints, bounds, labels, and reaction counts**
- [ ] **Step 4: Correct any issues and report the Figma URL**
