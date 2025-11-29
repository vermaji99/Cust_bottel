# 3D Bottle Customizer - Documentation

## Overview
A fully interactive 3D bottle customizer that allows users to:
- View bottles in realistic 3D
- Rotate bottles 360° with mouse/touch
- Upload custom logos and designs
- See real-time preview with photorealistic rendering
- Download preview images

## Features

### 1. 3D Bottle Viewer (`bottle3d.js`)
- **360° Rotation**: Click and drag to rotate
- **Touch Support**: Works on mobile devices
- **Photorealistic Rendering**: High-quality 3D graphics using Three.js
- **Dynamic Lighting**: Multiple light sources for realistic appearance
- **Custom Textures**: Apply logos and designs to bottle surface

### 2. Customizer Interface (`bottle3d-customizer.js`)
- **Logo Upload**: Upload PNG/JPG logos
- **Color Selection**: Change bottle color instantly
- **Real-time Preview**: See changes immediately
- **Download Preview**: Save preview as PNG

### 3. Integration
- **Product Pages**: Direct link from product pages
- **Featured Bottles**: "3D View" button on product cards
- **Standalone Page**: Full-featured customizer at `/customize-3d.php`

## Files Structure

```
Cust_bottel/
├── customize-3d.php          # Main 3D customizer page
├── assets/
│   └── js/
│       ├── bottle3d.js       # Core 3D viewer class
│       └── bottle3d-customizer.js  # UI controls and interface
└── 3D_CUSTOMIZER_README.md   # This file
```

## Usage

### Basic Implementation

1. **Include Three.js** (already included in `customize-3d.php`):
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
```

2. **Include Customizer Scripts**:
```html
<script src="assets/js/bottle3d.js"></script>
<script src="assets/js/bottle3d-customizer.js"></script>
```

3. **Initialize Customizer**:
```javascript
const customizer = new Bottle3DCustomizerUI('container-id', productId);
```

### From Product Pages

Add a link to the 3D customizer:
```html
<a href="customize-3d.php?id=<?= $product['id'] ?>">3D Customizer</a>
```

### Direct Access

Users can access the 3D customizer directly:
```
http://localhost/custom_bottel/Cust_bottel/customize-3d.php?id=1
```

## Technical Details

### 3D Bottle Geometry
- **Base Shape**: Cylinder-based geometry
- **Components**: Body, cap, and neck
- **Materials**: Phong material for realistic lighting
- **Shadows**: Enabled for depth perception

### Texture Mapping
- **Bottle Texture**: Base bottle texture (optional)
- **Logo Overlay**: Separate plane for logos
- **UV Mapping**: Proper texture coordinates
- **Transparency**: Support for PNG with alpha channel

### Controls
- **Mouse Drag**: Horizontal rotation
- **Touch Swipe**: Mobile-friendly rotation
- **Smooth Rotation**: Interpolated movement
- **Auto-rotate**: Optional automatic rotation

## Customization Options

### Colors
- Pre-defined color palette
- Custom color selection (future enhancement)
- Real-time color updates

### Logo Upload
- **Format**: PNG (recommended), JPG
- **Transparency**: PNG with alpha channel supported
- **Position**: Centered on bottle by default
- **Scale**: Adjustable (future enhancement)

### Export
- **Format**: PNG
- **Resolution**: Canvas resolution
- **Quality**: High-quality rendering

## Browser Compatibility

- **Chrome/Edge**: ✅ Full support
- **Firefox**: ✅ Full support
- **Safari**: ✅ Full support (iOS 12+)
- **Mobile**: ✅ Touch controls supported

## Performance

- **Optimized Rendering**: 60 FPS on modern devices
- **Adaptive Quality**: Reduces quality on slower devices
- **Lazy Loading**: Textures loaded on demand
- **Memory Management**: Proper cleanup on dispose

## Future Enhancements

1. **Logo Positioning**: Drag logo on bottle surface
2. **Multiple Logos**: Add multiple logo layers
3. **Text Overlay**: Add text directly on bottle
4. **3D Model Export**: Export 3D model files
5. **AR Preview**: Augmented Reality preview
6. **Animation**: Smooth transitions and effects
7. **Lighting Controls**: Adjust scene lighting
8. **Environment Maps**: Different background environments

## Troubleshooting

### Three.js Not Loading
- Check internet connection (CDN)
- Verify script tag is included
- Check browser console for errors

### Bottle Not Rendering
- Check browser WebGL support
- Try different browser
- Clear browser cache

### Logo Not Appearing
- Verify image format (PNG/JPG)
- Check file size (recommended: < 5MB)
- Ensure image is loaded before applying

### Performance Issues
- Close other browser tabs
- Reduce browser zoom level
- Use Chrome/Edge for best performance

## Support

For issues or questions:
1. Check browser console for errors
2. Verify all scripts are loaded
3. Test in different browsers
4. Check file paths are correct

---

**Note**: The 3D customizer requires modern browsers with WebGL support. Older browsers may not support this feature.

