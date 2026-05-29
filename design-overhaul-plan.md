# Design Overhaul Plan: Fadhil Antigravity Design System

## Objective
Implement a comprehensive visual overhaul of the 'keuangan' application to align strictly with the `DESIGN.md` specifications. The goal is to create a clean, human-centric, floating interface while explicitly removing purple/neon gradients and ensuring card boundaries remain clearly visible. No backend logic or application flow will be altered.

## Scope & Impact
- **Target Files:** `assets/css/style.css`, `dashboard.php`, layout files (`includes/header.php`, `includes/sidebar.php`), and various page files containing inline gradient styles (e.g., `logout.php`, `pages/installments/pay.php`).
- **Impact:** Significant visual changes. Improved readability, softer contrast, and a more professional, less "techy/AI" aesthetic.

## Proposed Solution

### 1. Update Core CSS (`assets/css/style.css`)
- **Theme Variables:** Refine the color palette to ensure soft contrast. Ensure `--card-border` and `--glass-border` are subtle but visible enough to separate cards from the background.
- **Background System:** Tone down the complex background radial gradients if they feel too "AI/techy", leaning towards a simpler, softer mesh or plain background as specified in the "calm" guidelines.
- **Card Styling:** Enforce `border: 1px solid var(--border)` and soft shadows (`shadow-[0_10px_30px_rgba(0,0,0,0.08)]` equivalent) on all card classes (`.stat-card`, `.chart-card`, `.transactions-card`, `.welcome-card`, `.prediction-card`) so they float but don't disappear into the background.
- **Remove Purple/Violet:** Search and destroy any references to purple/violet/magenta hex codes (like `#8b5cf6`, `#a855f7`, `#d946ef`) in CSS and JS chart configurations.
- **Buttons & Tags:** Soften button colors and ensure they look tactile without using harsh gradients.

### 2. Clean Up Inline Gradients
- Several files currently use inline `linear-gradient` styles that violate the new "human/calm" design principles. These need to be removed or replaced with subtle solid colors or soft surface backgrounds.
- **Target Files for Gradient Removal:**
  - `dashboard.php` (Prediction card currently uses a dark linear gradient).
  - `logout.php`
  - `forgot-password.php`, `login.php`, `register.php`, `reset-password.php`
  - `pages/emergency_fund/index.php`, `pages/emergency_fund/withdraw.php`
  - `pages/installments/index.php`, `pages/installments/pay.php`
  - `pages/reports/index.php`
  - `pages/transactions/index.php`

### 3. Layout Adjustments (`dashboard.php`)
- Redesign the **Prediction Card**: Currently, it looks like a dark AI box. Change it to a light, clean card with a subtle highlight (e.g., a thin blue top border or a soft blue icon background) to make it feel like a helpful assistant rather than a robotic black box.
- Ensure the sidebar and header maintain their glassmorphism effect but with enough opacity to remain legible.
- Update the chart colors in `dashboard.php` to remove the purple color (`#8b5cf6`) from `$category_colors`.

## Phased Implementation Plan

1. **Phase 1: CSS Foundations**
   - Update `assets/css/style.css` variables, shadows, and card base classes.
   - Adjust global background to be calmer.
2. **Phase 2: Component Refinement (Dashboard)**
   - Update `dashboard.php` to remove the dark gradient from the prediction card.
   - Adjust chart color arrays in `dashboard.php`.
   - Verify card borders and shadows on the dashboard.
3. **Phase 3: Global Gradient Cleanup**
   - Iterate through the identified files and replace inline `linear-gradient` and complex `radial-gradient` styles with standard class-based styling or subtle solid colors.
4. **Phase 4: Review and Polish**
   - Final check across dark and light modes to ensure text legibility and card boundary visibility.

## Verification
- Visually inspect the dashboard and a few subpages in both light and dark modes.
- Confirm no purple/violet accents remain.
- Confirm all cards have a visible border defining their edge against the background.
- Ensure no PHP logic errors were introduced during HTML structure updates.
