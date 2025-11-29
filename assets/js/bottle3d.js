/**
 * 3D Bottle Customizer with Three.js
 * Supports 360-degree rotation, texture overlay, and real-time preview
 */

class Bottle3DCustomizer {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            bottleImage: options.bottleImage || null,
            initialRotation: options.initialRotation || 0,
            autoRotate: options.autoRotate || false,
            controls: options.controls !== false,
            ...options
        };

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.bottleMesh = null;
        this.bottleGroup = null;
        this.customTexture = null;
        this.controls = null;
        this.isDragging = false;
        this.previousMousePosition = { x: 0, y: 0 };
        this.rotationSpeed = 0.005;

        this.init();
    }

    init() {
        // Create scene with gradient background for better transparency visibility
        this.scene = new THREE.Scene();
        
        // Create gradient background for transparent bottles
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 256;
        const ctx = canvas.getContext('2d');
        
        // Create gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 256);
        gradient.addColorStop(0, '#1a1a2e');
        gradient.addColorStop(0.5, '#16213e');
        gradient.addColorStop(1, '#0f0f1e');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 256, 256);
        
        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;
        this.scene.background = texture;
        
        // Alternative: solid dark background
        // this.scene.background = new THREE.Color(0x1a1a2e);

        // Create camera
        const width = this.container.clientWidth;
        const height = this.container.clientHeight || 600;
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        this.camera.position.set(0, 0, 5);

        // Create renderer with transparency support
        this.renderer = new THREE.WebGLRenderer({ 
            antialias: true,
            alpha: true,
            powerPreference: "high-performance"
        });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping; // Better color rendering
        this.renderer.toneMappingExposure = 1.2; // Slight exposure boost for transparency
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        this.container.appendChild(this.renderer.domElement);

        // Add lighting
        this.setupLighting();

        // Create bottle geometry from 2D image
        this.createBottleGeometry();

        // Setup controls
        if (this.options.controls) {
            this.setupControls();
        }

        // Setup mouse/touch controls
        this.setupInteraction();

        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());

        // Start render loop
        this.animate();
    }

    setupLighting() {
        // Enhanced lighting for transparent bottles
        // Ambient light
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
        this.scene.add(ambientLight);

        // Main directional light (key light)
        const mainLight = new THREE.DirectionalLight(0xffffff, 1.2);
        mainLight.position.set(5, 10, 5);
        mainLight.castShadow = true;
        this.scene.add(mainLight);

        // Fill light (softer)
        const fillLight = new THREE.DirectionalLight(0xffffff, 0.5);
        fillLight.position.set(-5, 5, -5);
        this.scene.add(fillLight);

        // Rim light for depth and edge definition
        const rimLight = new THREE.DirectionalLight(0xffffff, 0.6);
        rimLight.position.set(0, 0, -10);
        this.scene.add(rimLight);
        
        // Additional back light for transparency
        const backLight = new THREE.DirectionalLight(0xffffff, 0.4);
        backLight.position.set(0, 5, 10);
        this.scene.add(backLight);
        
        // Point light for highlights on glass
        const pointLight = new THREE.PointLight(0xffffff, 0.8, 20);
        pointLight.position.set(3, 5, 3);
        this.scene.add(pointLight);
    }

    createBottleGeometry() {
        this.bottleGroup = new THREE.Group();
        this.bottleStyle = this.options.bottleStyle || 'smooth';
        
        // Store current wrapper texture
        this.wrapperTexture = null;
        this.wrapperMaterial = null;

        // Create bottle body based on style
        let bottleGeometry;
        switch(this.bottleStyle) {
            case 'ribbed':
                // Ribbed bottle with indentations (like Bisleri)
                bottleGeometry = this.createRibbedBottleGeometry();
                break;
            case 'smooth':
            default:
                // Smooth cylindrical bottle
                bottleGeometry = new THREE.CylinderGeometry(0.8, 0.9, 2.5, 32);
                break;
        }
        
        // Load bottle texture if provided
        let bottleTexture = null;
        if (this.options.bottleImage) {
            const textureLoader = new THREE.TextureLoader();
            bottleTexture = textureLoader.load(this.options.bottleImage, (texture) => {
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.RepeatWrapping;
            });
        }

        // Create transparent bottle material (like Bisleri) - fully clear
        const bottleMaterial = new THREE.MeshPhysicalMaterial({
            color: 0xffffff,
            transparent: true,
            opacity: 0.1, // Almost fully transparent like clear plastic bottle
            roughness: 0.05, // Very smooth, glossy surface like PET plastic
            metalness: 0.0,
            clearcoat: 1.0, // Glass-like coating
            clearcoatRoughness: 0.05,
            transmission: 0.95, // Very high transmission for clear plastic
            thickness: 0.3, // Thin wall like real bottle
            side: THREE.DoubleSide,
            map: bottleTexture,
            ior: 1.5 // Index of refraction for plastic
        });

        this.bottleMesh = new THREE.Mesh(bottleGeometry, bottleMaterial);
        this.bottleMesh.castShadow = true;
        this.bottleMesh.receiveShadow = true;
        this.bottleMesh.renderOrder = 1; // Render after water
        this.bottleGroup.add(this.bottleMesh);

        // Add bottle cap
        const capGeometry = new THREE.CylinderGeometry(0.85, 0.85, 0.15, 32);
        const capMaterial = new THREE.MeshPhongMaterial({
            color: 0x333333,
            shininess: 150
        });
        const cap = new THREE.Mesh(capGeometry, capMaterial);
        cap.position.y = 1.325;
        cap.castShadow = true;
        this.bottleGroup.add(cap);

        // Add bottle neck (transparent, like bottle body)
        const neckGeometry = new THREE.CylinderGeometry(0.3, 0.4, 0.3, 16);
        const neckMaterial = new THREE.MeshPhysicalMaterial({
            color: 0xffffff,
            transparent: true,
            opacity: 0.1, // Same as bottle body
            roughness: 0.05,
            metalness: 0.0,
            clearcoat: 1.0,
            clearcoatRoughness: 0.05,
            transmission: 0.95,
            thickness: 0.3,
            side: THREE.DoubleSide,
            ior: 1.5
        });
        const neck = new THREE.Mesh(neckGeometry, neckMaterial);
        neck.position.y = 1.6;
        neck.castShadow = true;
        this.bottleGroup.add(neck);
        
        // Add water fill inside bottle (realistic effect)
        const waterGeometry = new THREE.CylinderGeometry(0.75, 0.85, 2.3, 32);
        const waterMaterial = new THREE.MeshPhysicalMaterial({
            color: 0x88c9f0, // Light blue water tint
            transparent: true,
            opacity: 0.3,
            roughness: 0.0,
            metalness: 0.0,
            transmission: 0.95,
            thickness: 2.0,
            side: THREE.DoubleSide
        });
        this.waterMesh = new THREE.Mesh(waterGeometry, waterMaterial);
        this.waterMesh.position.y = -0.1; // Slightly lower than bottle
        this.waterMesh.castShadow = false;
        this.waterMesh.receiveShadow = true;
        this.waterMesh.renderOrder = 0; // Render first (inside bottle)
        this.bottleGroup.add(this.waterMesh);

        this.scene.add(this.bottleGroup);

        // Initial rotation
        this.bottleGroup.rotation.y = this.options.initialRotation;
    }

    createRibbedBottleGeometry() {
        // Create ribbed bottle with horizontal indentations
        // Using a combination of torus and cylinder for ribbed effect
        const segments = 32;
        const height = 2.5;
        const topRadius = 0.8;
        const bottomRadius = 0.9;
        
        // Create main body
        const geometry = new THREE.CylinderGeometry(topRadius, bottomRadius, height, segments);
        
        // Modify vertices to create ribbed effect
        const positions = geometry.attributes.position;
        const vertex = new THREE.Vector3();
        
        // Add horizontal rib indentations
        for (let i = 0; i < positions.count; i++) {
            vertex.fromBufferAttribute(positions, i);
            const y = vertex.y;
            
            // Create wave pattern for ribs
            const ribFrequency = 8; // Number of ribs
            const ribDepth = 0.02; // Depth of indentations
            const angle = (y / height) * ribFrequency * Math.PI * 2;
            const offset = Math.sin(angle) * ribDepth;
            
            // Apply radial offset for ribbed effect
            const distance = Math.sqrt(vertex.x * vertex.x + vertex.z * vertex.z);
            if (distance > 0.01) {
                const scale = 1 + offset / distance;
                vertex.x *= scale;
                vertex.z *= scale;
            }
        }
        
        geometry.attributes.position.needsUpdate = true;
        geometry.computeVertexNormals();
        
        return geometry;
    }

    setupControls() {
        // OrbitControls for desktop - will be loaded separately if needed
        // For now, we use manual mouse controls
        // If OrbitControls is loaded later, it can be initialized here
    }

    setupInteraction() {
        const canvas = this.renderer.domElement;
        
        // Mouse controls
        canvas.addEventListener('mousedown', (e) => {
            this.isDragging = true;
            this.previousMousePosition = {
                x: e.clientX,
                y: e.clientY
            };
            canvas.style.cursor = 'grabbing';
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!this.isDragging) return;
            
            const deltaX = e.clientX - this.previousMousePosition.x;
            this.bottleGroup.rotation.y += deltaX * this.rotationSpeed;
            
            this.previousMousePosition = {
                x: e.clientX,
                y: e.clientY
            };
        });

        canvas.addEventListener('mouseup', () => {
            this.isDragging = false;
            canvas.style.cursor = 'grab';
        });

        canvas.addEventListener('mouseleave', () => {
            this.isDragging = false;
            canvas.style.cursor = 'grab';
        });

        // Touch controls for mobile
        let touchStartX = 0;
        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            touchStartX = e.touches[0].clientX;
            this.isDragging = true;
        });

        canvas.addEventListener('touchmove', (e) => {
            if (!this.isDragging) return;
            e.preventDefault();
            const deltaX = e.touches[0].clientX - touchStartX;
            this.bottleGroup.rotation.y += deltaX * this.rotationSpeed * 2;
            touchStartX = e.touches[0].clientX;
        });

        canvas.addEventListener('touchend', () => {
            this.isDragging = false;
        });

        canvas.style.cursor = 'grab';
    }

    applyTexture(textureUrl) {
        const textureLoader = new THREE.TextureLoader();
        textureLoader.load(
            textureUrl,
            (texture) => {
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.RepeatWrapping;
                texture.repeat.set(1, 1);

                // Apply texture to bottle body
                if (this.bottleMesh) {
                    this.customTexture = texture;
                    this.bottleMesh.material.map = texture;
                    this.bottleMesh.material.needsUpdate = true;
                }
            },
            undefined,
            (error) => {
                console.error('Error loading texture:', error);
            }
        );
    }

    applyWrapperTexture(wrapperUrl) {
        const textureLoader = new THREE.TextureLoader();
        textureLoader.load(
            wrapperUrl,
            (texture) => {
                // Remove existing wrapper if any
                if (this.wrapperMesh) {
                    this.bottleGroup.remove(this.wrapperMesh);
                }
                
                // Configure texture for proper cylindrical wrapping (like real bottle label)
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.ClampToEdgeWrapping;
                texture.repeat.set(1, 1);
                
                // Create label geometry - positioned exactly on bottle surface
                // Height: ~1.2 (covers middle section like Bisleri label)
                // Radius: matches bottle radius exactly (0.8-0.9) so it sits on surface
                const wrapperGeometry = new THREE.CylinderGeometry(0.801, 0.901, 1.3, 32);
                
                // Create material for label - fully opaque, matte finish like paper/plastic label
                this.wrapperMaterial = new THREE.MeshPhongMaterial({
                    map: texture,
                    transparent: false, // Fully opaque label
                    opacity: 1.0,
                    side: THREE.DoubleSide,
                    shininess: 10, // Matte finish like real label
                    specular: 0x111111,
                    flatShading: false
                });
                
                // Create wrapper mesh
                this.wrapperMesh = new THREE.Mesh(wrapperGeometry, this.wrapperMaterial);
                
                // Position label on bottle body (middle section, like real water bottle)
                this.wrapperMesh.position.y = -0.1; // Slightly lower to cover middle area
                
                // Make sure label renders after bottle but before logos
                this.wrapperMesh.renderOrder = 2; // Render after bottle (1) but before logos (3)
                this.wrapperMesh.castShadow = false;
                this.wrapperMesh.receiveShadow = true;
                this.wrapperMesh.userData = { type: 'wrapper', url: wrapperUrl };
                
                // Add to bottle group (but ensure it's clearly visible)
                this.bottleGroup.add(this.wrapperMesh);
                
                // Store reference
                this.wrapperTexture = texture;
            },
            undefined,
            (error) => {
                console.error('Error loading wrapper texture:', error);
            }
        );
    }

    applyLogoTexture(logoUrl, position = { x: 0, y: 0 }, scale = 1) {
        const textureLoader = new THREE.TextureLoader();
        textureLoader.load(
            logoUrl,
            (texture) => {
                // Create a plane for the logo that sits on the label
                const logoGeometry = new THREE.PlaneGeometry(0.3 * scale, 0.3 * scale);
                const logoMaterial = new THREE.MeshPhongMaterial({
                    map: texture,
                    transparent: true,
                    opacity: 1,
                    side: THREE.DoubleSide
                });
                const logoMesh = new THREE.Mesh(logoGeometry, logoMaterial);
                // Position logo on label surface (slightly outward from wrapper)
                logoMesh.position.set(position.x, position.y, 0.91);
                logoMesh.renderOrder = 3; // Render on top
                logoMesh.userData = { type: 'logo', url: logoUrl };
                this.bottleGroup.add(logoMesh);
            },
            undefined,
            (error) => {
                console.error('Error loading logo texture:', error);
            }
        );
    }

    setBottleStyle(style) {
        this.bottleStyle = style;
        // Remove old bottle
        if (this.bottleMesh) {
            this.bottleGroup.remove(this.bottleMesh);
        }
        
        // Recreate with new style (transparent)
        const bottleColor = this.bottleMesh ? this.bottleMesh.material.color.getHex() : 0xffffff;
        const bottleTexture = this.bottleMesh ? this.bottleMesh.material.map : null;
        
        let bottleGeometry;
        switch(style) {
            case 'ribbed':
                bottleGeometry = this.createRibbedBottleGeometry();
                break;
            case 'smooth':
            default:
                bottleGeometry = new THREE.CylinderGeometry(0.8, 0.9, 2.5, 32);
                break;
        }
        
        // Always use transparent material for Bisleri-like bottles - fully clear
        const bottleMaterial = new THREE.MeshPhysicalMaterial({
            color: bottleColor,
            transparent: true,
            opacity: 0.1, // Almost fully transparent
            roughness: 0.05, // Very smooth like PET plastic
            metalness: 0.0,
            clearcoat: 1.0,
            clearcoatRoughness: 0.05,
            transmission: 0.95, // Very high transmission
            thickness: 0.3, // Thin wall
            side: THREE.DoubleSide,
            map: bottleTexture,
            ior: 1.5 // Index of refraction for plastic
        });
        
        this.bottleMesh = new THREE.Mesh(bottleGeometry, bottleMaterial);
        this.bottleMesh.castShadow = true;
        this.bottleMesh.receiveShadow = true;
        this.bottleGroup.add(this.bottleMesh);
        
        // Re-add wrapper if exists
        if (this.wrapperMesh && this.wrapperTexture) {
            this.applyWrapperTexture(this.wrapperMesh.userData.url);
        }
        
        // Ensure water mesh exists
        if (!this.waterMesh) {
            const waterGeometry = new THREE.CylinderGeometry(0.75, 0.85, 2.3, 32);
            const waterMaterial = new THREE.MeshPhysicalMaterial({
                color: 0x88c9f0,
                transparent: true,
                opacity: 0.3,
                roughness: 0.0,
                transmission: 0.95,
                thickness: 2.0,
                side: THREE.DoubleSide
            });
            this.waterMesh = new THREE.Mesh(waterGeometry, waterMaterial);
            this.waterMesh.position.y = -0.1;
            this.bottleGroup.add(this.waterMesh);
        }
    }

    setBottleColor(color) {
        // For transparent bottles, color affects tint very subtly
        if (this.bottleMesh) {
            // Keep transparency but adjust color tint slightly
            this.bottleMesh.material.color.setHex(color);
            // Make it very subtle for transparent effect
            this.bottleMesh.material.opacity = 0.15;
        }
        // Also update water color if exists
        if (this.waterMesh) {
            // Water gets a slight tint
            const r = (color >> 16) & 255;
            const g = (color >> 8) & 255;
            const b = color & 255;
            // Lighten and blend with blue for water effect
            const waterColor = new THREE.Color(
                Math.min(255, r + 50) / 255 * 0.5 + 0.5,
                Math.min(255, g + 100) / 255 * 0.7 + 0.3,
                Math.min(255, b + 150) / 255 * 0.8 + 0.2
            );
            this.waterMesh.material.color.copy(waterColor);
        }
    }

    resetRotation() {
        if (this.bottleGroup) {
            this.bottleGroup.rotation.y = 0;
        }
    }

    onWindowResize() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight || 600;
        
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        // Auto-rotate if enabled
        if (this.options.autoRotate && !this.isDragging) {
            this.bottleGroup.rotation.y += 0.005;
        }

        // Update controls
        if (this.controls) {
            this.controls.update();
        }

        // Render
        this.renderer.render(this.scene, this.camera);
    }

    dispose() {
        // Clean up resources
        if (this.renderer) {
            this.renderer.dispose();
        }
        if (this.controls) {
            this.controls.dispose();
        }
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Bottle3DCustomizer;
}

