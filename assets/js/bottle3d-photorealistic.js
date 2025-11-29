/**
 * Photorealistic 3D Bottle Generator
 * Bisleri-style 500ml bottle with exact specifications
 * Height: 210mm, Body: 65mm diameter, Label: 60mm × 205mm
 */

class PhotorealisticBottle {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        // Bottle specifications (in Three.js units: 1 unit = 10mm for scale)
        // Bisleri-style: Height ≈ 3× diameter, cylindrical with rounded shoulders
        this.specs = {
            height: 2.1, // 210mm = 21cm = 2.1 units
            bodyDiameter: 0.65, // 65mm = 6.5cm = 0.65 units (height/diameter ≈ 3.23)
            neckDiameter: 0.28, // 28mm - slim neck
            capDiameter: 0.3, // 30mm - small round cap
            shoulderHeight: 1.5, // 150mm - rounded shoulders
            labelHeight: 0.6, // 60mm
            labelStartY: 0.75, // 75mm from base
            labelEndY: 1.35, // 135mm from base
            labelCircumference: 2.05, // 205mm
            wallThickness: 0.009, // 0.9mm
            capHeight: 0.12, // 12mm - small cap
            verticalRidgeCount: 20, // Vertical ridges for grip (smooth)
        };

        this.options = {
            labelImage: options.labelImage || null,
            bottleColor: options.bottleColor || 0xffffff,
            ...options,
        };

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.bottleGroup = null;
        this.labelMesh = null;
        this.controls = null;
        this.outlineMeshes = []; // Store outline meshes for toggling

        this.init();
    }

    init() {
        // Create scene with white/gradient background for product display
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xffffff);

        // Add subtle gradient background (white to light gray)
        const gradientCanvas = document.createElement("canvas");
        gradientCanvas.width = 256;
        gradientCanvas.height = 256;
        const gradientCtx = gradientCanvas.getContext("2d");
        const gradient = gradientCtx.createLinearGradient(0, 0, 0, 256);
        gradient.addColorStop(0, "#ffffff"); // White top
        gradient.addColorStop(1, "#f5f5f5"); // Light gray bottom
        gradientCtx.fillStyle = gradient;
        gradientCtx.fillRect(0, 0, 256, 256);
        const gradientTexture = new THREE.CanvasTexture(gradientCanvas);
        this.scene.background = gradientTexture;

        // Create camera (3/4 angle view)
        const width = this.container.clientWidth;
        const height = this.container.clientHeight || 600;
        this.camera = new THREE.PerspectiveCamera(
            45,
            width / height,
            0.01,
            100
        );
        // Position camera to view 210mm bottle (2.1 units)
        // Scale: 1 unit = 100mm, so bottle is 2.1 units tall
        this.camera.position.set(3, 1.05, 4); // Camera at bottle center height
        this.camera.lookAt(0, 1.05, 0); // Look at bottle center

        // Create renderer with high-quality settings
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: false,
            powerPreference: "high-performance",
            preserveDrawingBuffer: true, // For better quality export
        });
        this.renderer.setSize(width, height);
        // Use higher pixel ratio for better quality (up to 3x for retina displays)
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 3));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.0;
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        // Enable high-quality rendering
        this.renderer.physicallyCorrectLights = true;
        this.container.appendChild(this.renderer.domElement);

        // Setup studio lighting
        this.setupStudioLighting();

        // Create bottle geometry
        this.createBottle();

        // Setup controls
        this.setupControls();

        // Handle resize
        window.addEventListener("resize", () => this.onWindowResize());

        // Start render loop
        this.animate();
    }

    setupStudioLighting() {
        // Soft studio lighting with single shadow (Bisleri-style product photography)
        // Main key light (front-right) - soft and diffused - ONLY shadow caster
        const keyLight = new THREE.DirectionalLight(0xffffff, 1.8);
        keyLight.position.set(4, 4, 4);
        keyLight.castShadow = true;
        keyLight.shadow.mapSize.width = 2048;
        keyLight.shadow.mapSize.height = 2048;
        keyLight.shadow.camera.near = 0.5;
        keyLight.shadow.camera.far = 50;
        keyLight.shadow.radius = 8; // Soft shadows
        keyLight.shadow.bias = -0.0001; // Fix shadow artifacts
        this.scene.add(keyLight);

        // Fill light (front-left, softer) - NO shadows
        const fillLight = new THREE.DirectionalLight(0xffffff, 0.6);
        fillLight.position.set(-4, 3, 3);
        fillLight.castShadow = false; // No shadow from fill light
        this.scene.add(fillLight);

        // Rim light (back) - NO shadows
        const rimLight = new THREE.DirectionalLight(0xffffff, 0.4);
        rimLight.position.set(0, 2, -4);
        rimLight.castShadow = false; // No shadow from rim light
        this.scene.add(rimLight);

        // Ambient light - soft overall illumination
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        this.scene.add(ambientLight);

        // Additional point light for reflections on glossy surface - NO shadows
        const reflectionLight = new THREE.PointLight(0xffffff, 0.5, 10);
        reflectionLight.position.set(2, 3, 2);
        reflectionLight.castShadow = false; // No shadow from point light
        this.scene.add(reflectionLight);

        // Ground plane for shadows - white/gray for product display
        const groundGeometry = new THREE.PlaneGeometry(10, 10);
        const groundMaterial = new THREE.MeshStandardMaterial({
            color: 0xf5f5f5, // Light gray ground
            roughness: 0.6,
        });
        const ground = new THREE.Mesh(groundGeometry, groundMaterial);
        ground.rotation.x = -Math.PI / 2;
        ground.position.y = -0.01;
        ground.receiveShadow = true;
        this.scene.add(ground);
    }

    createBottle() {
        this.bottleGroup = new THREE.Group();

        // Create bottle body with ribs
        this.createBottleBody();

        // Create shoulder and neck
        this.createBottleNeck();

        // Create cap
        this.createCap();

        // Create petaloid base
        this.createPetaloidBase();

        // Create water fill
        this.createWaterFill();

        this.scene.add(this.bottleGroup);

        // Apply label if provided
        if (this.options.labelImage) {
            this.applyLabel(this.options.labelImage);
        }
    }

    createBottleBody() {
        // Create main body cylinder with vertical ridges for grip
        const bodyRadius = this.specs.bodyDiameter / 2;
        const bodyHeight = this.specs.shoulderHeight;
        const segments = 128; // High detail for smooth vertical ridges

        // Create geometry with custom vertices for vertical ridges
        const geometry = new THREE.CylinderGeometry(
            bodyRadius,
            bodyRadius,
            bodyHeight,
            segments
        );

        // Add smooth vertical ridges for grip (Bisleri-style)
        const positions = geometry.attributes.position;
        const vertex = new THREE.Vector3();
        const ridgeDepth = 0.0015; // 0.15mm ridge depth - subtle
        const ridgeCount = this.specs.verticalRidgeCount;

        for (let i = 0; i < positions.count; i++) {
            vertex.fromBufferAttribute(positions, i);

            // Calculate angle around the cylinder
            const angle = Math.atan2(vertex.z, vertex.x);
            // Create vertical ridges using sine wave
            const ridgePattern = Math.sin(angle * ridgeCount);
            const ridgeOffset = ridgePattern * ridgeDepth;

            // Apply ridge offset
            const distance = Math.sqrt(
                vertex.x * vertex.x + vertex.z * vertex.z
            );
            if (distance > 0.01) {
                const scale = 1 + ridgeOffset / distance;
                vertex.x *= scale;
                vertex.z *= scale;
            }
        }

        geometry.attributes.position.needsUpdate = true;
        geometry.computeVertexNormals();

        // Create PET material with greenish tint (Bisleri-style transparent greenish plastic)
        const bottleMaterial = new THREE.MeshPhysicalMaterial({
            color: 0x90d0a0, // Darker greenish tint for better visibility
            transparent: true,
            opacity: 0.5, // Increased opacity for better visibility
            roughness: 0.02, // Very glossy finish
            metalness: 0.0,
            clearcoat: 1.0, // Maximum clearcoat for glossy reflections
            clearcoatRoughness: 0.02, // Very smooth for reflections
            transmission: 0.75, // Reduced transmission for better visibility
            thickness: 0.3,
            ior: 1.49, // PET plastic index of refraction
            side: THREE.DoubleSide,
            reflectivity: 0.9, // High reflectivity for glossy look
        });

        const bodyMesh = new THREE.Mesh(geometry, bottleMaterial);
        // Position body so base is at y=0, extends upward
        bodyMesh.position.y = bodyHeight / 2;
        bodyMesh.castShadow = true;
        bodyMesh.receiveShadow = true;
        this.bottleGroup.add(bodyMesh);

        // Outline disabled - removed for cleaner look

        // Position bottle group so base is at y=0
        this.bottleGroup.position.y = 0;

        this.bottleBodyMesh = bodyMesh;
    }

    createBottleNeck() {
        const neckRadius = this.specs.neckDiameter / 2;
        const neckHeight = this.specs.height - this.specs.shoulderHeight;
        // Rounded shoulders - taper smoothly from body to neck
        const shoulderTaper = 0.15; // Smooth transition
        const neckGeometry = new THREE.CylinderGeometry(
            neckRadius,
            (this.specs.bodyDiameter / 2) * 0.75, // Rounded shoulder transition
            neckHeight,
            64 // Higher detail for smooth rounded shoulders
        );

        const neckMaterial = new THREE.MeshPhysicalMaterial({
            color: 0x90d0a0, // Darker greenish tint matching body
            transparent: true,
            opacity: 0.5,
            roughness: 0.02, // Very glossy
            clearcoat: 1.0,
            clearcoatRoughness: 0.02,
            transmission: 0.75,
            thickness: 0.3,
            ior: 1.49,
            reflectivity: 0.9,
        });

        const neckMesh = new THREE.Mesh(neckGeometry, neckMaterial);
        neckMesh.position.y = this.specs.shoulderHeight + neckHeight / 2;
        neckMesh.castShadow = true;
        this.bottleGroup.add(neckMesh);
    }

    createCap() {
        const capRadius = this.specs.capDiameter / 2;
        const capHeight = this.specs.capHeight;

        // Small round screw-on plastic cap
        const capGeometry = new THREE.CylinderGeometry(
            capRadius,
            capRadius,
            capHeight,
            64
        );

        // Add vertical ridges for grip (screw-on cap texture)
        const positions = capGeometry.attributes.position;
        for (let i = 0; i < positions.count; i++) {
            const vertex = new THREE.Vector3();
            vertex.fromBufferAttribute(positions, i);
            const angle = Math.atan2(vertex.z, vertex.x);
            const ridgeCount = 24; // More ridges for better grip
            const ridge = Math.sin(angle * ridgeCount) * 0.002;
            const distance = Math.sqrt(
                vertex.x * vertex.x + vertex.z * vertex.z
            );
            if (distance > 0.01 && Math.abs(vertex.y) < capHeight / 2 - 0.001) {
                vertex.x += Math.cos(angle) * ridge;
                vertex.z += Math.sin(angle) * ridge;
            }
        }
        capGeometry.attributes.position.needsUpdate = true;
        capGeometry.computeVertexNormals();

        // Plastic cap material - glossy finish
        const capMaterial = new THREE.MeshPhysicalMaterial({
            color: 0x4caf50, // Green plastic cap (Bisleri-style)
            roughness: 0.2, // Glossy plastic
            metalness: 0.0,
            clearcoat: 0.8,
            clearcoatRoughness: 0.1,
        });

        const capMesh = new THREE.Mesh(capGeometry, capMaterial);
        capMesh.position.y = this.specs.height + capHeight / 2;
        capMesh.castShadow = true;
        this.bottleGroup.add(capMesh);
    }

    createPetaloidBase() {
        const baseRadius = this.specs.bodyDiameter / 2;
        const baseHeight = 0.015; // 1.5mm - flat base
        const petalCount = 5;

        // Create base geometry - flat base with petaloid pattern
        const geometry = new THREE.CylinderGeometry(
            baseRadius * 0.95,
            baseRadius,
            baseHeight,
            petalCount * 8
        );

        // Create concave petaloid shape with 5 feet (Bisleri-style base)
        const positions = geometry.attributes.position;
        for (let i = 0; i < positions.count; i++) {
            const vertex = new THREE.Vector3();
            vertex.fromBufferAttribute(positions, i);

            if (vertex.y < -baseHeight / 2 + 0.001) {
                // Bottom face - flat base
                const angle = Math.atan2(vertex.z, vertex.x);
                const distance = Math.sqrt(
                    vertex.x * vertex.x + vertex.z * vertex.z
                );

                // Create 5-petal pattern
                const petalAngle = (angle / Math.PI) * petalCount;
                const petalPos = (petalAngle % 2) - 1;
                const petalDepth = Math.abs(petalPos) * 0.008; // Concave depth

                // Create feet at petal centers
                const footAngle =
                    Math.round((angle / (2 * Math.PI)) * petalCount) *
                    ((2 * Math.PI) / petalCount);
                const footDistance = Math.sqrt(
                    Math.pow(
                        vertex.x - Math.cos(footAngle) * baseRadius * 0.7,
                        2
                    ) +
                        Math.pow(
                            vertex.z - Math.sin(footAngle) * baseRadius * 0.7,
                            2
                        )
                );
                const footHeight = footDistance < 0.01 ? 0.003 : 0; // 0.3mm feet

                vertex.y = -baseHeight / 2 - petalDepth + footHeight;
            }
        }

        geometry.attributes.position.needsUpdate = true;
        geometry.computeVertexNormals();

        const baseMaterial = new THREE.MeshPhysicalMaterial({
            color: 0x90d0a0, // Darker greenish tint matching body
            transparent: true,
            opacity: 0.5,
            roughness: 0.02, // Glossy
            transmission: 0.75,
            ior: 1.49,
            clearcoat: 1.0,
            clearcoatRoughness: 0.02,
        });

        const baseMesh = new THREE.Mesh(geometry, baseMaterial);
        // Base sits at bottom, so center at baseHeight/2
        baseMesh.position.y = baseHeight / 2;
        baseMesh.castShadow = true;
        this.bottleGroup.add(baseMesh);
    }

    createWaterFill() {
        const waterRadius = this.specs.bodyDiameter / 2 - 0.005; // Slightly smaller than bottle
        const waterHeight = this.specs.shoulderHeight - 0.01; // Water fills to shoulder

        const waterGeometry = new THREE.CylinderGeometry(
            waterRadius,
            waterRadius,
            waterHeight,
            64
        );
        const waterMaterial = new THREE.MeshPhysicalMaterial({
            color: 0xc0e0d0, // Slight greenish tint for water (matching bottle)
            transparent: true,
            opacity: 0.5, // More visible
            roughness: 0.0,
            transmission: 0.9,
            thickness: waterHeight,
            ior: 1.33, // Water IOR
        });

        const waterMesh = new THREE.Mesh(waterGeometry, waterMaterial);
        // Water fills from base, center at waterHeight/2
        waterMesh.position.y = waterHeight / 2;
        waterMesh.castShadow = false;
        waterMesh.receiveShadow = true;
        this.bottleGroup.add(waterMesh);

        // Ensure bottle group is positioned so base is at y=0
        this.bottleGroup.position.y = 0;
    }

    applyLabel(imageUrl) {
        const textureLoader = new THREE.TextureLoader();
        textureLoader.load(
            imageUrl,
            (texture) => {
                // Remove existing label
                if (this.labelMesh) {
                    this.bottleGroup.remove(this.labelMesh);
                }

                // Calculate label dimensions
                const labelHeight = this.specs.labelHeight;
                const labelCircumference = this.specs.labelCircumference;
                const labelRadius = this.specs.bodyDiameter / 2 + 0.001; // Slightly larger than bottle

                // High-quality texture settings
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.ClampToEdgeWrapping;
                texture.flipY = false; // No flip needed - already flipped in canvas

                // Enable high-quality texture filtering
                texture.minFilter = THREE.LinearMipmapLinearFilter;
                texture.magFilter = THREE.LinearFilter;
                texture.generateMipmaps = true;
                texture.anisotropy =
                    this.renderer.capabilities.getMaxAnisotropy();

                // Calculate aspect ratio for proper fit
                const aspectRatio = labelCircumference / labelHeight; // 205/60 ≈ 3.42
                texture.repeat.set(1, 1);

                // Create label geometry - exact dimensions with high detail
                const labelGeometry = new THREE.CylinderGeometry(
                    labelRadius,
                    labelRadius,
                    labelHeight,
                    128 // Very high detail (doubled) for smooth wrapping and better quality
                );

                // Position label exactly from 75mm to 135mm from base (y=0)
                const labelCenterY =
                    (this.specs.labelStartY + this.specs.labelEndY) / 2;

                // Create laminated, glossy label material
                const labelMaterial = new THREE.MeshPhysicalMaterial({
                    map: texture,
                    transparent: true,
                    opacity: 1.0,
                    roughness: 0.1, // Glossy
                    metalness: 0.0,
                    clearcoat: 1.0, // Laminated look
                    clearcoatRoughness: 0.1,
                    side: THREE.DoubleSide,
                });

                this.labelMesh = new THREE.Mesh(labelGeometry, labelMaterial);
                this.labelMesh.position.y = labelCenterY;
                this.labelMesh.castShadow = false;
                this.labelMesh.receiveShadow = true;
                this.labelMesh.renderOrder = 10; // Render on top

                // No rotation needed - texture orientation is correct
                this.labelMesh.rotation.y = 0;

                this.bottleGroup.add(this.labelMesh);
            },
            undefined,
            (error) => {
                console.error("Error loading label texture:", error);
            }
        );
    }

    autoFitImageToLabel(imageFile, callback) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                // High-resolution target dimensions for better quality
                // Using 2x resolution: 4100px × 1200px (4K quality)
                const scaleFactor = 2; // 2x for high quality
                const targetWidth = 4100; // 205mm at 20px/mm (2x quality)
                const targetHeight = 1200; // 60mm at 20px/mm (2x quality)

                // Create high-resolution canvas for image processing
                const canvas = document.createElement("canvas");
                canvas.width = targetWidth;
                canvas.height = targetHeight;
                const ctx = canvas.getContext("2d");

                // Enable high-quality image rendering
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = "high";

                // Calculate crop/fit to maintain aspect ratio
                const imgAspect = img.width / img.height;
                const targetAspect = targetWidth / targetHeight;

                let sx = 0,
                    sy = 0,
                    sw = img.width,
                    sh = img.height;

                if (imgAspect > targetAspect) {
                    // Image is wider - crop width
                    sw = img.height * targetAspect;
                    sx = (img.width - sw) / 2;
                } else {
                    // Image is taller - crop height
                    sh = img.width / targetAspect;
                    sy = (img.height - sh) / 2;
                }

                // Flip image vertically to fix orientation (Three.js coordinate system)
                ctx.save();
                ctx.translate(0, targetHeight);
                ctx.scale(1, -1);
                ctx.drawImage(
                    img,
                    sx,
                    sy,
                    sw,
                    sh,
                    0,
                    0,
                    targetWidth,
                    targetHeight
                );
                ctx.restore();

                // Get final high-quality image data (PNG for best quality)
                const finalImageUrl = canvas.toDataURL("image/png", 1.0); // Maximum quality

                // Apply to label
                this.applyLabel(finalImageUrl);

                if (callback) callback(finalImageUrl);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(imageFile);
    }

    setupControls() {
        // Simple rotation controls
        const canvas = this.renderer.domElement;
        let isDragging = false;
        let previousMousePosition = { x: 0, y: 0 };

        canvas.addEventListener("mousedown", (e) => {
            isDragging = true;
            previousMousePosition = { x: e.clientX, y: e.clientY };
            canvas.style.cursor = "grabbing";
        });

        canvas.addEventListener("mousemove", (e) => {
            if (!isDragging) return;
            const deltaX = e.clientX - previousMousePosition.x;
            this.bottleGroup.rotation.y += deltaX * 0.01;
            previousMousePosition = { x: e.clientX, y: e.clientY };
        });

        canvas.addEventListener("mouseup", () => {
            isDragging = false;
            canvas.style.cursor = "grab";
        });

        canvas.style.cursor = "grab";
    }

    setCameraView(view) {
        const bottleCenterY = this.specs.height / 2; // 1.05 (center of 2.1 unit bottle)

        if (view === "front") {
            this.camera.position.set(0, bottleCenterY, 5);
            this.camera.lookAt(0, bottleCenterY, 0);
        } else if (view === "threequarter") {
            this.camera.position.set(3, bottleCenterY, 4);
            this.camera.lookAt(0, bottleCenterY, 0);
        }

        this.camera.updateProjectionMatrix();
    }

    onWindowResize() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight || 600;
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    toggleOutline(show) {
        // Toggle outline visibility
        if (this.outlineMeshes && this.outlineMeshes.length > 0) {
            this.outlineMeshes.forEach((mesh) => {
                if (mesh) mesh.visible = show;
            });
        }
    }

    animate() {
        requestAnimationFrame(() => this.animate());
        this.renderer.render(this.scene, this.camera);
    }

    downloadHighQuality() {
        // Export high-quality image
        const canvas = this.renderer.domElement;
        const currentWidth = this.container.clientWidth;
        const currentHeight = this.container.clientHeight || 600;

        // Save current camera and renderer settings
        const originalWidth = currentWidth;
        const originalHeight = currentHeight;

        // Render at 2x resolution for high quality
        const renderWidth = originalWidth * 2;
        const renderHeight = originalHeight * 2;

        this.renderer.setSize(renderWidth, renderHeight);
        this.camera.aspect = renderWidth / renderHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.render(this.scene, this.camera);

        // Capture
        const dataURL = canvas.toDataURL("image/png", 1.0);

        // Reset to original size
        this.renderer.setSize(originalWidth, originalHeight);
        this.camera.aspect = originalWidth / originalHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.render(this.scene, this.camera);

        // Download
        const link = document.createElement("a");
        link.download = `bottle-render-${Date.now()}.png`;
        link.href = dataURL;
        link.click();
    }
}

// Export
if (typeof module !== "undefined" && module.exports) {
    module.exports = PhotorealisticBottle;
}
