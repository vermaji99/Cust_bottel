# Photorealistic 3D Bottle - Exact Specifications

## ✅ Implementation Complete

A photorealistic 3D water bottle generator with **exact Bisleri-style specifications**.

## Bottle Specifications

### Dimensions:
- **Height**: 210mm (2.1 units in 3D)
- **Body Diameter**: 65mm (0.65 units)
- **Neck Diameter**: 28mm (0.28 units)
- **Cap Diameter**: 30mm (0.30 units)
- **Cap Height**: 12mm (0.12 units)
- **Shoulder Starts**: 150mm from base
- **Wall Thickness**: ~0.9mm

### Material Properties:
- **Type**: Transparent PET plastic
- **Color**: Slight green/blue tint (#e8f5f9)
- **IOR (Index of Refraction)**: 1.49 (PET plastic)
- **Transmission**: 0.95 (high light transmission)
- **Roughness**: 0.05 (very smooth, glossy)
- **Clearcoat**: 1.0 (glass-like coating)

### Structural Features:

1. **Horizontal Ribs**:
   - Spacing: Every 18mm
   - Depth: 0.2mm indentations
   - Full circumference wrapping

2. **Petaloid Base**:
   - Concave base design
   - 5 small feet for stability
   - Realistic bottom structure

3. **Cap**:
   - Green screw-on cap (#4caf50)
   - Vertical ridges for grip (20 ridges)
   - 12mm height

4. **Label Area**:
   - **Height**: Exactly 60mm
   - **Position**: From 75mm to 135mm from base
   - **Circumference**: 205mm (wraps 360°)
   - **Material**: Laminated, glossy finish

## Image Auto-Fit System

### Label Wrapper Behavior:

When you upload an image:

1. **Automatic Processing**:
   - Image is analyzed for aspect ratio
   - Automatically cropped to fit 60mm × 205mm dimensions
   - Maintains proportional scaling (no distortion)

2. **Target Dimensions**:
   - Height: 60mm (600px at 10px/mm)
   - Width: 205mm (2050px - wraps full circumference)

3. **Cropping Logic**:
   - If image is wider: crops width, centers vertically
   - If image is taller: crops height, centers horizontally
   - Maintains aspect ratio perfectly

4. **Wrapping**:
   - Image wraps 360° around bottle
   - No distortion or stretching
   - Clean, straight edges
   - Perfectly follows cylindrical curvature

5. **Material Properties**:
   - **Opacity**: 1.0 (fully opaque)
   - **Roughness**: 0.1 (glossy)
   - **Clearcoat**: 1.0 (laminated look)
   - Appears printed on PET film

## Rendering Features

### Studio Lighting:
- **White Background**: Pure white (#ffffff)
- **Key Light**: Front-right (2.0 intensity)
- **Fill Light**: Front-left (0.8 intensity)
- **Rim Light**: Back (0.5 intensity)
- **Ambient Light**: Soft overall illumination (0.6 intensity)
- **Shadows**: Enabled for realism

### Camera Views:
- **3/4 Angle View**: Default position (3, 2, 4)
- **Front View**: Direct front position (0, 1, 5)
- Switchable via UI buttons

### Realistic Effects:
- ✅ Proper refraction (IOR 1.49)
- ✅ Light reflections on PET surface
- ✅ Shadows cast on white background
- ✅ Laminated label appearance
- ✅ Water visible inside (slight blue tint)
- ✅ Glossy, smooth surfaces

## Usage

### Step 1: Open 3D Customizer
```
http://localhost/custom_bottel/Cust_bottel/customize-3d.php?id=1
```

### Step 2: Upload Label Image
1. Click "Upload Your Label Image"
2. Select any image file (PNG/JPG recommended)
3. Image will automatically:
   - Crop to fit 60mm × 205mm
   - Wrap 360° around bottle
   - Apply laminated, glossy finish

### Step 3: Rotate & View
- **Drag** to rotate bottle 360°
- Switch between **3/4 View** and **Front View**
- See your label perfectly wrapped

## Technical Details

### File Structure:
```
Cust_bottel/
├── assets/
│   └── js/
│       └── bottle3d-photorealistic.js  # Photorealistic generator
├── customize-3d.php                    # Main page
└── PHOTOREALISTIC_BOTTLE_SPECS.md     # This file
```

### Bottle Components:

1. **Body Mesh**:
   - Cylinder with 64 segments (high detail)
   - Horizontal ribs at 18mm intervals
   - Transparent PET material

2. **Neck Mesh**:
   - Tapered cylinder
   - Connects body to cap
   - Same transparent material

3. **Cap Mesh**:
   - Green plastic material
   - Vertical ridges for grip
   - Screw-on design

4. **Base Mesh**:
   - Petaloid concave shape
   - 5 feet for stability
   - Transparent material

5. **Water Mesh**:
   - Inner cylinder
   - Slight blue tint
   - IOR 1.33 (water)

6. **Label Mesh**:
   - Cylinder at exact dimensions
   - Positioned 75-135mm from base
   - Laminated, glossy material

## Image Requirements

### Recommended:
- **Format**: PNG (preferred) or JPG
- **Quality**: High resolution (2000+ pixels wide)
- **Aspect Ratio**: Any (will be auto-cropped)
- **Background**: Transparent or solid (auto-cropped)

### Auto-Fit Process:
1. Image loaded into canvas
2. Aspect ratio calculated
3. Cropped to match 205mm × 60mm ratio
4. Scaled to exact dimensions
5. Applied as texture on label mesh
6. Wrapped 360° around bottle

## Result

✅ **Photorealistic 3D render** where:
- Your uploaded image appears perfectly wrapped as printed label
- Bottle has exact Bisleri-style specifications
- Transparent PET with proper refraction (IOR 1.49)
- Label appears laminated and glossy
- White studio background
- Multiple camera angles
- High-quality, realistic rendering

---

**Perfect for**: Product visualization, marketing, e-commerce, design previews!

