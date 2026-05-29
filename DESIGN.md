# DESIGN & STYLE GUIDE
### Fadhil Antigravity Design System

> A clean futuristic interface system inspired by Google Antigravity, modern SaaS dashboards, floating UI, soft motion, and calm professional aesthetics.

---

# 1. CORE DESIGN PHILOSOPHY

The interface should feel:

- Clean but not empty
- Futuristic but still human
- Premium without looking corporate
- Soft and floating instead of rigid
- Minimal but visually alive
- Professional with subtle personality
- Calm, breathable, and elegant

The goal is:

> "Technology that feels lightweight, intelligent, and pleasant to interact with."

---

# 2. VISUAL IDENTITY

## Main Style Direction

Combination of:

- Google Antigravity
- Linear App
- Vercel
- Apple UI softness
- Modern AI dashboard interfaces
- Floating glassmorphism
- Soft neumorphic depth
- Motion-driven layouts

---

# 3. DESIGN PRINCIPLES

## 3.1 Floating Layouts

Avoid rigid boxed layouts.

Prefer:
- Floating cards
- Soft containers
- Rounded panels
- Detached navigation
- Layered depth

### Preferred
```css
rounded-[28px]
shadow-[0_10px_40px_rgba(0,0,0,0.08)]
border border-white/60
bg-white/80
backdrop-blur-xl
```

---

## 3.2 Soft Contrast

Never use harsh black & white contrast.

### Avoid
```css
bg-black text-white
```

### Prefer
```css
bg-[#202124]
text-[#f8f9fa]
```

---

## 3.3 Motion First

Every interface should feel alive.

Use:
- Smooth transitions
- Floating movement
- Hover expansion
- Scroll transformations
- Magnetic interactions
- Opacity fades
- Blur transitions

### Standard Motion
```css
transition-all duration-500 ease-[cubic-bezier(.22,1,.36,1)]
```

---

## 3.4 Airy Spacing

Whitespace is mandatory.

Use generous spacing:
```css
py-24 md:py-32
gap-6
gap-10
space-y-8
```

Never overcrowd layouts.

---

# 4. COLOR SYSTEM

## Primary Palette

### Google Inspired Colors

```css
Blue    #4285f4
Red     #ea4335
Yellow  #fbbc05
Green   #34a853
```

---

## Neutral Colors

```css
Background      #f8fafd
Foreground      #202124
Muted            #5f6368
Border           #e8eaed
Surface          #f1f3f4
```

---

## Accent Usage Rules

Use color minimally.

Most UI should remain:
- White
- Soft gray
- Neutral

Color should only:
- Guide attention
- Highlight actions
- Show status
- Create identity

---

# 5. TYPOGRAPHY

## Typography Personality

The typography should feel:

- Calm
- Smart
- Modern
- Confident
- Spacious

---

## Heading Style

### Preferred
```css
text-4xl md:text-6xl
font-[650]
tracking-tight
leading-[1.05]
```

### Example Feel
```txt
Selected work, presented like a product lab.
```

---

## Body Text

Readable and breathable.

```css
text-base leading-8
text-muted-foreground
```

---

## Labels

Use:
```css
uppercase
tracking-wider
text-xs
font-bold
```

---

# 6. COMPONENT STYLE RULES

---

# 6.1 Navbar

Navbar should:
- Float
- Blur background
- Transform while scrolling
- Become compact/folded
- Feel intelligent

### Desired Behavior

Before scroll:
- Horizontal floating navbar

After scroll:
- Morph into floating sidebar
- Animate folding transition
- Compact icon-based navigation

---

## Navbar Style

```css
bg-white/75
backdrop-blur-2xl
border border-white/60
shadow-[0_10px_40px_rgba(0,0,0,0.08)]
```

---

# 6.2 Cards

Cards should NEVER feel flat.

Use:
```css
rounded-[28px]
border border-[#eceff1]
bg-white/88
shadow-[0_8px_30px_rgba(0,0,0,0.06)]
```

Hover:
```css
hover:-translate-y-1
hover:shadow-[0_18px_45px_rgba(0,0,0,0.10)]
```

---

# 6.3 Buttons

Buttons should feel soft and tactile.

### Primary Button
```css
bg-[#202124]
text-white
rounded-full
```

### Secondary Button
```css
bg-white
border border-[#e8eaed]
```

---

# 6.4 Chips & Tags

```css
rounded-full
px-3 py-1
text-xs font-semibold
bg-[#f1f3f4]
```

---

# 6.5 Sections

Every section should:
- Have breathing room
- Feel separated softly
- Use subtle gradients
- Avoid sharp transitions

Preferred:
```css
relative overflow-hidden bg-white py-24 md:py-32
```

---

# 7. ANIMATION SYSTEM

---

# 7.1 Preferred Animations

Use:
- Floating
- Fade up
- Scale in
- Blur reveal
- Soft slide
- Glow pulse
- Gradient movement

---

# 7.2 Hover Philosophy

Hover should:
- Enhance
- Never distract
- Feel lightweight

Preferred:
```css
hover:scale-[1.02]
hover:-translate-y-1
```

Avoid:
- Aggressive bouncing
- Large rotations
- Excessive parallax

---

# 7.3 Scroll Effects

Important principle:

> Scroll should transform the interface.

Examples:
- Navbar folding
- Sidebar morphing
- Opacity reveals
- Background blur changes
- Floating movement

---

# 8. GLASSMORPHISM RULES

Glass effects should be subtle.

Preferred:
```css
bg-white/70
backdrop-blur-xl
border border-white/50
```

Avoid:
- Strong transparency
- Overly frosted UI
- Neon effects

---

# 9. SHADOW SYSTEM

Shadows should feel soft and atmospheric.

### Primary Shadow
```css
shadow-[0_10px_30px_rgba(0,0,0,0.08)]
```

### Floating Shadow
```css
shadow-[0_20px_60px_rgba(0,0,0,0.12)]
```

Avoid:
```css
shadow-2xl
```

---

# 10. BORDER SYSTEM

Borders should be subtle separators.

Preferred:
```css
border border-[#e8eaed]
```

Avoid:
```css
border-black
```

---

# 11. ICONOGRAPHY

Use:
- Lucide icons
- Thin line icons
- Rounded visuals

Preferred sizes:
```css
h-4 w-4
h-5 w-5
h-6 w-6
```

---

# 12. BACKGROUND SYSTEM

Backgrounds should never be plain empty white.

Use:
- Radial gradients
- Blur glows
- Floating lights
- Soft mesh gradients

Example:
```css
bg-[radial-gradient(circle_at_top,rgba(66,133,244,0.10),transparent_70%)]
```

---

# 13. UI PERSONALITY

The interface personality should feel:

| Trait | Description |
|---|---|
| Intelligent | Feels system-driven |
| Calm | No visual chaos |
| Premium | High-end modern UI |
| Lightweight | Breathable layouts |
| Interactive | Motion-aware |
| Futuristic | Slightly AI-inspired |
| Human | Friendly and soft |

---

# 14. DEVELOPMENT STACK PREFERENCE

Preferred technologies:

- React
- Tailwind CSS
- Framer Motion
- Lucide React
- TypeScript
- Vite
- Firebase
- Laravel API

---

# 15. MOTION GUIDELINES

## Timing

Preferred:
```css
duration-500
duration-700
```

Avoid:
```css
duration-150
```

---

## Easing

Preferred:
```css
ease-[cubic-bezier(.22,1,.36,1)]
```

---

# 16. MOBILE DESIGN RULES

Mobile should:
- Keep floating feel
- Use stacked cards
- Keep blur effects
- Use bottom spacing generously
- Maintain premium feeling

---

# 17. UX PRINCIPLES

The interface should:
- Reduce friction
- Feel intuitive immediately
- Prioritize clarity
- Keep interactions obvious
- Never overwhelm users

---

# 18. WHAT TO AVOID

❌ Avoid:
- Overly colorful UI
- Brutalist design
- Heavy gradients
- Sharp edges
- Neon cyberpunk
- Dense dashboards
- Excessive animations
- Flat bootstrap look
- Corporate enterprise style
- Generic templates

---

# 19. TARGET EMOTIONAL FEELING

When someone opens the website, they should feel:

```txt
"This feels modern, intelligent, smooth, and thoughtfully crafted."
```

Not:
```txt
"This looks like a normal portfolio template."
```

---

# 20. FINAL DESIGN IDENTITY

## Design Keywords

- Antigravity
- Floating UI
- Calm tech
- Soft futuristic
- Intelligent minimalism
- Premium motion
- Google-inspired
- AI workspace aesthetic
- Elegant engineering
- Product-lab interface

---

# 21. PERSONAL RULE

For every future project:

> Keep the interface clean enough to feel professional,
> but alive enough to feel memorable.

And:

> Simplicity first.
> Motion second.
> Decoration last.
```