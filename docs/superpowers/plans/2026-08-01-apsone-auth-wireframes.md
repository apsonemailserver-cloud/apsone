# APSOne Authentication Wireframes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build four connected monochrome authentication wireframes in the existing Figma file `mockup apsone`.

**Architecture:** Use one Figma page containing four 1440 × 900 top-level frames arranged horizontally. Each frame uses primitive auto-layout containers and text nodes so the result remains editable, visibly low-fidelity, and structurally different from the production screenshots.

**Tech Stack:** Figma Design, Figma Plugin API through `use_figma`, Inter typography.

## Global Constraints

- Use only white, black, and neutral gray fills/strokes.
- Do not use blue, gradients, images, or polished production effects.
- Use the screenshots only for copy and flow, not pixel-level layout.
- Build Login, Forgot Password, OTP Verification, and Create New Password.
- Arrange frames horizontally in flow order and name them consistently.

---

### Task 1: Create the Four Screen Wrappers

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Approved wireframe specification.
- Produces: Four top-level frame IDs used by later tasks.

- [ ] **Step 1: Inspect the target Figma page**

Return the page name, existing top-level children, and available fonts without mutating the canvas.

- [ ] **Step 2: Create four 1440 × 900 frames**

Create frames named `01 — Login`, `02 — Forgot Password`, `03 — Verify OTP`, and `04 — New Password`, positioned with a 160 px horizontal gap.

- [ ] **Step 3: Verify wrapper geometry**

Return every created node ID and confirm dimensions and positions.

### Task 2: Build Login and Forgot Password

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Frame IDs from Task 1.
- Produces: Completed Login and Forgot Password wireframes.

- [ ] **Step 1: Build Login structure**

Add a utility header, centered form panel, NIP and password controls, dark Login button, forgot-password action, and wireframe footer.

- [ ] **Step 2: Build Forgot Password structure**

Add the shared header, a three-step recovery indicator with step 1 active, NIP field, Send OTP button, back-to-login action, and footer.

- [ ] **Step 3: Validate content and node bounds**

Return all created node IDs and screen summary.

### Task 3: Build OTP and New Password

**Files:**
- Modify: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Frame IDs from Task 1 and the shared visual language from Task 2.
- Produces: Completed OTP Verification and Create New Password wireframes.

- [ ] **Step 1: Build OTP Verification structure**

Add the shared header, step 2 active, neutral OTP-sent message, NIP summary, OTP field, Verify OTP button, back action, and footer.

- [ ] **Step 2: Build Create New Password structure**

Add the shared header, step 3 active, neutral verification-success message, two password fields, Save Password button, back action, and footer.

- [ ] **Step 3: Validate content and node bounds**

Return all created node IDs and screen summary.

### Task 4: Visual QA and Handoff

**Files:**
- Inspect: Figma file `LUwE7rbkNsbTtHwblL9ODv`

**Interfaces:**
- Consumes: Four completed screen frames.
- Produces: Verified editable Figma wireframes and file URL.

- [ ] **Step 1: Capture a screenshot of each screen**

Render each top-level frame for visual inspection.

- [ ] **Step 2: Check monochrome fidelity and consistency**

Confirm no non-neutral colors, clipping, overflow, overlap, or missing labels.

- [ ] **Step 3: Apply any required corrections**

Correct only issues found during inspection and return all mutated node IDs.

- [ ] **Step 4: Report the final Figma link**

Provide `https://www.figma.com/design/LUwE7rbkNsbTtHwblL9ODv` after verification.
