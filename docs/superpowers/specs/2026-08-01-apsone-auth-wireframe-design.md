# APSOne Authentication Wireframe Design

## Objective

Create four low-fidelity authentication mockups in the existing Figma file `mockup apsone`. The finished application screenshots are references for content and user flow only; the mockups must not reproduce their final visual design.

## Screens

1. Employee Login
2. Forgot Password
3. OTP Verification
4. Create New Password

The prototype flow is Login → Forgot Password → OTP Verification → Create New Password → Login.

## Visual Direction

- Desktop frames at 1440 × 900, arranged horizontally in flow order.
- Monochrome palette only: white, light gray, medium gray, dark gray, and black.
- No blue, gradients, photographic assets, polished shadows, or production-level decoration.
- Modern low-fidelity wireframe language using simple outlines, flat fills, placeholder icons, and restrained typography.
- APS/SIAPS branding represented as simple text placeholders rather than the final logo artwork.
- Layout must differ from the finished application: use a compact utility header, a centered content region, and a visible recovery-step indicator instead of copying the final split-screen composition.

## Shared Structure

Each frame contains:

- A thin utility header with the APS/SIAPS placeholder and a screen/status label.
- A centered authentication panel with a descriptive heading and short helper text.
- Clearly labeled form controls with neutral placeholder content.
- A primary dark button and secondary text action.
- A small annotation/footer indicating that the design is an early wireframe.

The three recovery screens include a three-step indicator: Identify Account, Verify OTP, and New Password. The active step changes per screen.

## Screen Content

### Employee Login

- NIP field
- Password field
- Login button
- Forgot-password link

### Forgot Password

- NIP field
- Send OTP button
- Back-to-login link
- Step 1 active

### OTP Verification

- Neutral success/info message stating that the OTP was sent
- Read-only NIP summary
- OTP code field
- Verify OTP button
- Back-to-login link
- Step 2 active

### Create New Password

- Neutral success/info message stating that OTP verification succeeded
- New password field
- Confirm new password field
- Save Password button
- Back-to-login link
- Step 3 active

## Fidelity and Success Criteria

- The four screens communicate the full authentication recovery flow without resembling production screenshots pixel-for-pixel.
- All important labels, states, and actions remain understandable.
- Visual treatment is visibly provisional and monochrome.
- Frames are consistently named, aligned, and ready for prototype connections in Figma.
- No unrelated dashboard or employee-management screens are included in this pass.
