# Service Vault Theme & Design System Implementation Todo

## Phase 1: Theme Settings UI Foundation
- [ ] Add "Appearance" tab to Settings (/settings?tab=appearance)
  - [ ] Add SunIcon/MoonIcon to heroicons imports in Settings/Index.vue
  - [ ] Update tabs array to include theme tab
  - [ ] Create ThemeSettings.vue component
  - [ ] Add theme tab content to Settings/Index.vue

## Phase 2: Theme Settings Component
- [ ] Create `/Pages/Settings/Components/ThemeSettings.vue`
  - [ ] Light/Dark mode toggle with system preference detection
  - [ ] Primary color picker (6-8 preset options + custom HEX input)
  - [ ] Secondary color picker for accent colors
  - [ ] Style preset selector: "Modern" vs "Business"
  - [ ] Component sizing options (Compact/Standard/Comfortable)
  - [ ] Border radius selector (None/Small/Medium/Large/Full)
  - [ ] Typography scale selector (Small/Medium/Large)

## Phase 3: Enhanced CSS Custom Properties
- [ ] Expand `/resources/css/app.css` theme variables
  - [ ] Add complete color scale system (50-900 shades)
  - [ ] Add component sizing variables (--button-size-*, --card-radius, etc)
  - [ ] Add typography scale variables (--text-scale, --font-weight-base)
  - [ ] Add style preset variables (modern vs business)
  - [ ] Enhance dark theme variables with proper contrast ratios

## Phase 4: Tailwind Configuration Enhancement
- [ ] Update `/tailwind.config.js`
  - [ ] Extend theme with CSS custom properties
  - [ ] Create theme-aware utility classes
  - [ ] Add component variants for style presets
  - [ ] Configure color palette based on CSS variables

## Phase 5: Vue Theme Composable
- [ ] Create `/Composables/useTheme.js`
  - [ ] Theme state management with Vue reactive
  - [ ] Theme persistence (localStorage + database)
  - [ ] System preference detection
  - [ ] Color calculation utilities (shade generation)
  - [ ] Theme switching with smooth transitions
  - [ ] Live preview functionality

## Phase 6: Database & API Support
- [ ] Create theme database migration
  - [ ] Add `themes` table for system-wide settings
  - [ ] Add theme columns to users table for preferences
  - [ ] Add theme columns to accounts table for branding
- [ ] Create theme API endpoints
  - [ ] GET /api/settings/theme
  - [ ] PUT /api/settings/theme
  - [ ] GET/PUT /api/users/{id}/theme-preferences

## Phase 7: Design System Components
- [ ] Create standardized theme-aware components
  - [ ] `/Components/Theme/ThemeButton.vue` - Unified button styles
  - [ ] `/Components/Theme/ThemeCard.vue` - Consistent card styling
  - [ ] `/Components/Theme/ThemeInput.vue` - Form input standardization
  - [ ] `/Components/Theme/ThemeModal.vue` - Modal appearance consistency
  - [ ] `/Components/Theme/ThemeBadge.vue` - Badge component

## Phase 8: Update User Badge Dropdown
- [ ] Update UserBadgeDropdown.vue to use theme system
  - [ ] Replace hardcoded gradient with theme variables
  - [ ] Use CSS custom properties for colors
  - [ ] Implement style preset variations (modern/business)
  - [ ] Add proper theme switching animations

## Phase 9: Component Standardization (145+ files)
- [ ] Update all buttons to use ThemeButton component
- [ ] Update all cards to use ThemeCard component
- [ ] Update all form inputs to use ThemeInput component
- [ ] Update all modals to use ThemeModal component
- [ ] Replace hardcoded colors with CSS custom properties
- [ ] Standardize border radius usage
- [ ] Standardize typography scales
- [ ] Update spacing to use theme variables

## Phase 10: Advanced Features
- [ ] Live theme preview system
  - [ ] Preview modal with sample components
  - [ ] Real-time color updates
  - [ ] Style preset switching
- [ ] Account-specific branding capability
  - [ ] Customer account theme overrides
  - [ ] Brand color injection system
  - [ ] Multi-tenant theme switching

## Phase 11: Testing & Refinement
- [ ] Cross-browser theme testing
- [ ] Accessibility compliance (WCAG contrast ratios)
- [ ] Performance optimization for theme switching
- [ ] Mobile responsiveness verification
- [ ] Theme persistence testing across sessions

## Phase 12: Documentation & Training
- [ ] Create theme system documentation
- [ ] Document design token usage
- [ ] Create component usage guidelines
- [ ] Update CLAUDE.md with theme system info

---

## Technical Architecture Notes

### Theme Structure
```css
:root {
  /* Core Brand Colors */
  --color-primary-50: calc(from var(--color-primary-500) l c h);
  --color-primary-500: #3b82f6; /* User selected */
  --color-primary-900: calc(from var(--color-primary-500) l c h);
  
  /* Component Sizing */
  --button-size-sm: 0.75rem;
  --button-size-md: 1rem;
  --button-radius: var(--radius-base);
  --card-radius: var(--radius-base);
  
  /* Style Presets */
  --radius-base: 0.375rem; /* Modern: 0.75rem, Business: 0.125rem */
  --shadow-base: 0 1px 3px 0 rgb(0 0 0 / 0.1); /* Modern: enhanced, Business: minimal */
}
```

### Component Integration
- All 145+ Vue files need systematic updates
- Use CSS custom properties instead of Tailwind hardcoded classes where applicable
- Maintain backward compatibility during transition
- Implement progressive enhancement approach

### Benefits
- **Consistent UX**: Unified styling across 3000+ style occurrences
- **Enterprise Branding**: Account-specific theme capability
- **Accessibility**: Proper contrast ratios and WCAG compliance
- **Developer Productivity**: Standardized component library
- **User Personalization**: Individual theme preferences
- **Multi-tenant Ready**: Account-level branding overrides