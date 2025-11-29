/**
 * 3D Bottle Customizer Interface
 * Full-featured customizer with texture overlay controls
 */

class Bottle3DCustomizerUI {
    constructor(containerId, productId = null) {
        this.containerId = containerId;
        this.productId = productId;
        this.customizer = null;
        this.currentLogo = null;
        this.currentTexture = null;

        this.init();
    }

    init() {
        // Wait for Three.js to load
        const checkThree = () => {
            if (typeof THREE === 'undefined') {
                console.warn('Three.js is loading...');
                setTimeout(checkThree, 100);
                return;
            }
            
            // Initialize 3D customizer after Three.js is loaded
            this.initializeCustomizer();
        };
        
        checkThree();
    }
    
    initializeCustomizer() {
        // Initialize 3D customizer
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error(`Container #${this.containerId} not found`);
            return;
        }

        // Create 3D viewer container
        const viewerContainer = document.createElement('div');
        viewerContainer.id = 'bottle3d-viewer';
        viewerContainer.style.width = '100%';
        viewerContainer.style.height = '600px';
        viewerContainer.style.background = 'linear-gradient(180deg, #0a0a0a 0%, #111111 100%)';
        viewerContainer.style.borderRadius = '12px';
        viewerContainer.style.overflow = 'hidden';
        viewerContainer.style.position = 'relative';

        container.appendChild(viewerContainer);

        // Initialize customizer with transparent bottle
        this.customizer = new Bottle3DCustomizer('bottle3d-viewer', {
            autoRotate: false,
            controls: false,
            bottleStyle: 'smooth',
            transparent: true // Enable transparent bottle mode
        });

        // Setup UI controls
        this.setupUIControls(container);
        
        // Load wrapper library
        setTimeout(() => {
            this.loadWrapperLibrary();
        }, 100);
    }

    setupUIControls(container) {
        // Create controls panel
        const controlsPanel = document.createElement('div');
        controlsPanel.className = 'bottle3d-controls';
        controlsPanel.style.cssText = `
            margin-top: 20px;
            padding: 20px;
            background: rgba(20, 20, 20, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(0, 188, 212, 0.2);
        `;

        controlsPanel.innerHTML = `
            <h3 style="color: #00bcd4; margin-top: 0; margin-bottom: 20px;">
                <i class="fas fa-palette"></i> Customize Your Bottle
            </h3>
            
            <div class="control-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #e0e0e0; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-flask"></i> Bottle Style
                </label>
                <select id="bottleStyle" style="width: 100%; padding: 10px; border-radius: 8px; 
                    border: 1px solid rgba(0, 188, 212, 0.3); 
                    background: rgba(0, 0, 0, 0.3); color: #fff; cursor: pointer;">
                    <option value="smooth">Smooth Bottle</option>
                    <option value="ribbed">Ribbed Bottle (Premium)</option>
                </select>
                <small style="color: #999; display: block; margin-top: 5px;">
                    Choose your preferred bottle style
                </small>
            </div>
            
            <div class="control-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #e0e0e0; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-tags"></i> Select Wrapper/Label
                </label>
                <div id="wrapperLibrary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px;">
                    <div class="wrapper-preview" data-wrapper="none" style="padding: 10px; border: 2px solid rgba(0, 188, 212, 0.3); border-radius: 8px; cursor: pointer; text-align: center; background: rgba(0, 0, 0, 0.3);">
                        <i class="fas fa-ban" style="font-size: 2rem; color: #666;"></i>
                        <div style="font-size: 0.8rem; color: #999; margin-top: 5px;">None</div>
                    </div>
                    <!-- Wrapper previews will be added dynamically -->
                </div>
                <input type="file" id="wrapperUpload" accept="image/*" 
                    style="width: 100%; padding: 10px; border-radius: 8px; 
                    border: 1px solid rgba(0, 188, 212, 0.3); 
                    background: rgba(0, 0, 0, 0.3); color: #fff; cursor: pointer;">
                <small style="color: #999; display: block; margin-top: 5px;">
                    Upload your custom wrapper/label (PNG/JPG)
                </small>
            </div>
            
            <div class="control-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #e0e0e0; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-image"></i> Upload Logo/Design (Optional)
                </label>
                <input type="file" id="logoUpload" accept="image/*" 
                    style="width: 100%; padding: 10px; border-radius: 8px; 
                    border: 1px solid rgba(0, 188, 212, 0.3); 
                    background: rgba(0, 0, 0, 0.3); color: #fff; cursor: pointer;">
                <small style="color: #999; display: block; margin-top: 5px;">
                    Upload logo to place on wrapper (recommended: transparent PNG)
                </small>
            </div>

            <div class="control-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #e0e0e0; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-tint"></i> Water Color Tint (Optional)
                </label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="color-btn" data-color="#ffffff" style="background: #fff; border: 2px solid #ddd;" title="Clear Water">
                        <span style="font-size: 0.7rem;">Clear</span>
                    </button>
                    <button class="color-btn" data-color="#88c9f0" style="background: #88c9f0;" title="Light Blue"></button>
                    <button class="color-btn" data-color="#b3e5fc" style="background: #b3e5fc;" title="Sky Blue"></button>
                    <button class="color-btn" data-color="#e1f5fe" style="background: #e1f5fe;" title="Pale Blue"></button>
                </div>
                <small style="color: #999; display: block; margin-top: 5px;">
                    Bottle is transparent. Adjust water tint if needed (subtle effect).
                </small>
            </div>

            <div class="control-group" style="margin-bottom: 20px;">
                <label style="display: block; color: #e0e0e0; margin-bottom: 8px; font-weight: 500;">
                    <i class="fas fa-undo"></i> Controls
                </label>
                <div style="display: flex; gap: 10px;">
                    <button id="resetRotation" class="action-btn">
                        <i class="fas fa-undo"></i> Reset View
                    </button>
                    <button id="downloadPreview" class="action-btn">
                        <i class="fas fa-download"></i> Download Preview
                    </button>
                </div>
            </div>

            <div style="background: rgba(0, 188, 212, 0.1); padding: 15px; border-radius: 8px; margin-top: 20px;">
                <p style="color: #00bcd4; margin: 0; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Tip:</strong> Click and drag to rotate the bottle 360°. Upload your logo to see it on the bottle in real-time!
                </p>
            </div>
        `;

        container.appendChild(controlsPanel);

        // Setup event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Bottle style selector
        const bottleStyle = document.getElementById('bottleStyle');
        if (bottleStyle) {
            bottleStyle.addEventListener('change', (e) => {
                if (this.customizer) {
                    this.customizer.setBottleStyle(e.target.value);
                    this.showMessage('Bottle style updated!', 'success');
                }
            });
        }

        // Wrapper upload
        const wrapperUpload = document.getElementById('wrapperUpload');
        if (wrapperUpload) {
            wrapperUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.handleWrapperUpload(file);
                }
            });
        }

        // Wrapper library selection
        document.querySelectorAll('.wrapper-preview').forEach(preview => {
            preview.addEventListener('click', () => {
                const wrapperUrl = preview.dataset.wrapper;
                this.selectWrapper(wrapperUrl, preview);
            });
        });

        // Logo upload
        const logoUpload = document.getElementById('logoUpload');
        if (logoUpload) {
            logoUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.handleLogoUpload(file);
                }
            });
        }

        // Color buttons
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const color = e.target.dataset.color;
                this.setBottleColor(color);
                
                // Update active state
                document.querySelectorAll('.color-btn').forEach(b => {
                    b.style.border = '2px solid transparent';
                    b.style.transform = 'scale(1)';
                });
                e.target.style.border = '2px solid #00bcd4';
                e.target.style.transform = 'scale(1.1)';
            });
        });

        // Reset rotation
        const resetBtn = document.getElementById('resetRotation');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (this.customizer) {
                    this.customizer.resetRotation();
                }
            });
        }

        // Download preview
        const downloadBtn = document.getElementById('downloadPreview');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                this.downloadPreview();
            });
        }
    }

    handleWrapperUpload(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const imageUrl = e.target.result;
            
            // Apply wrapper texture
            if (this.customizer) {
                this.customizer.applyWrapperTexture(imageUrl);
            }

            // Show success message
            this.showMessage('Wrapper uploaded successfully!', 'success');
            
            // Update active state
            document.querySelectorAll('.wrapper-preview').forEach(p => {
                p.style.borderColor = 'rgba(0, 188, 212, 0.3)';
            });
        };
        reader.readAsDataURL(file);
    }

    selectWrapper(wrapperUrl, previewElement) {
        if (wrapperUrl === 'none') {
            // Remove wrapper
            if (this.customizer && this.customizer.wrapperMesh) {
                this.customizer.bottleGroup.remove(this.customizer.wrapperMesh);
                this.customizer.wrapperMesh = null;
            }
        } else {
            // Apply wrapper
            if (this.customizer) {
                this.customizer.applyWrapperTexture(wrapperUrl);
            }
        }

        // Update active state
        document.querySelectorAll('.wrapper-preview').forEach(p => {
            p.style.borderColor = 'rgba(0, 188, 212, 0.3)';
            p.style.background = 'rgba(0, 0, 0, 0.3)';
        });
        if (previewElement) {
            previewElement.style.borderColor = '#00bcd4';
            previewElement.style.background = 'rgba(0, 188, 212, 0.2)';
        }
    }

    loadWrapperLibrary() {
        // Load wrappers from library or use defaults
        const wrappers = typeof WRAPPER_LIBRARY !== 'undefined' ? WRAPPER_LIBRARY : [
            { id: 'classic-white', name: 'Classic White', url: 'assets/images/white-label.png', category: 'basic' },
            { id: 'premium-teal', name: 'Premium Teal', url: 'assets/images/teal-label.png', category: 'premium' },
            { id: 'royal-blue', name: 'Royal Blue', url: 'assets/images/blue-label.png', category: 'premium' }
        ];

        const library = document.getElementById('wrapperLibrary');
        if (!library) return;

        // Color mapping for preview
        const colorMap = {
            'classic-white': '#ffffff',
            'premium-teal': '#00bcd4',
            'royal-blue': '#007bff',
            'green-natural': '#2e7d32',
            'red-bold': '#c62828',
            'minimal-clear': 'rgba(255,255,255,0.3)'
        };

        wrappers.forEach((wrapper) => {
            const preview = document.createElement('div');
            preview.className = 'wrapper-preview';
            preview.dataset.wrapper = wrapper.url || wrapper.id;
            const color = colorMap[wrapper.id] || '#00bcd4';
            
            preview.innerHTML = `
                <div style="width: 60px; height: 60px; margin: 0 auto; background: ${color}; border-radius: 4px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    <i class="fas fa-tag" style="font-size: 1.5rem; color: ${wrapper.id === 'classic-white' ? '#333' : '#fff'};"></i>
                </div>
                <div style="font-size: 0.8rem; color: #999; margin-top: 5px;">${wrapper.name}</div>
            `;
            preview.style.cssText = `
                padding: 10px; 
                border: 2px solid rgba(0, 188, 212, 0.3); 
                border-radius: 8px; 
                cursor: pointer; 
                text-align: center; 
                background: rgba(0, 0, 0, 0.3);
                transition: all 0.3s;
            `;
            preview.addEventListener('mouseenter', () => {
                if (preview.style.borderColor !== 'rgb(0, 188, 212)') {
                    preview.style.borderColor = 'rgba(0, 188, 212, 0.6)';
                    preview.style.transform = 'scale(1.05)';
                }
            });
            preview.addEventListener('mouseleave', () => {
                if (preview.style.borderColor !== 'rgb(0, 188, 212)') {
                    preview.style.borderColor = 'rgba(0, 188, 212, 0.3)';
                    preview.style.transform = 'scale(1)';
                }
            });
            preview.addEventListener('click', () => {
                this.selectWrapper(wrapper.url || wrapper.id, preview);
            });
            
            library.appendChild(preview);
        });
    }

    handleLogoUpload(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const imageUrl = e.target.result;
            this.currentLogo = imageUrl;
            
            // Remove existing logos
            if (this.customizer && this.customizer.bottleGroup) {
                const logos = this.customizer.bottleGroup.children.filter(
                    child => child.userData && child.userData.type === 'logo'
                );
                logos.forEach(logo => this.customizer.bottleGroup.remove(logo));
            }

            // Apply new logo
            if (this.customizer) {
                this.customizer.applyLogoTexture(imageUrl, { x: 0, y: 0 }, 1);
            }

            // Show success message
            this.showMessage('Logo uploaded successfully!', 'success');
        };
        reader.readAsDataURL(file);
    }

    setBottleColor(colorHex) {
        // Remove # if present and convert to number
        const color = colorHex.replace('#', '');
        const colorNumber = parseInt(color, 16);
        
        if (this.customizer) {
            this.customizer.setBottleColor(colorNumber);
        }
    }

    downloadPreview() {
        if (!this.customizer || !this.customizer.renderer) return;

        // Capture at higher resolution
        const canvas = this.customizer.renderer.domElement;
        const originalWidth = canvas.width;
        const originalHeight = canvas.height;
        
        // Render at 2x resolution
        this.customizer.renderer.setSize(originalWidth * 2, originalHeight * 2);
        this.customizer.renderer.render(this.customizer.scene, this.customizer.camera);
        
        // Capture
        const dataURL = canvas.toDataURL('image/png', 1.0);
        
        // Reset size
        this.customizer.renderer.setSize(originalWidth, originalHeight);
        this.customizer.renderer.render(this.customizer.scene, this.customizer.camera);

        // Create download link
        const link = document.createElement('a');
        link.download = `bottle-preview-${Date.now()}.png`;
        link.href = dataURL;
        link.click();
    }

    showMessage(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#00bcd4' : '#333'};
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .color-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .color-btn:hover {
        transform: scale(1.15);
        box-shadow: 0 4px 12px rgba(0, 188, 212, 0.4);
    }
    
    .action-btn {
        padding: 10px 20px;
        background: rgba(0, 188, 212, 0.2);
        border: 1px solid rgba(0, 188, 212, 0.4);
        color: #00bcd4;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        flex: 1;
    }
    
    .action-btn:hover {
        background: rgba(0, 188, 212, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 188, 212, 0.2);
    }
`;
document.head.appendChild(style);

