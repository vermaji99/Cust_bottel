/**
 * Wrapper/Label Library
 * Pre-defined wrapper templates for bottle customization
 */

const WRAPPER_LIBRARY = [
    {
        id: 'classic-white',
        name: 'Classic White',
        description: 'Clean white label perfect for any brand',
        url: 'assets/images/wrappers/white-label.png',
        preview: 'assets/images/wrappers/previews/white-preview.png',
        category: 'basic'
    },
    {
        id: 'premium-teal',
        name: 'Premium Teal',
        description: 'Elegant teal wrapper with modern look',
        url: 'assets/images/wrappers/teal-label.png',
        preview: 'assets/images/wrappers/previews/teal-preview.png',
        category: 'premium'
    },
    {
        id: 'royal-blue',
        name: 'Royal Blue',
        description: 'Bold blue label for premium brands',
        url: 'assets/images/wrappers/blue-label.png',
        preview: 'assets/images/wrappers/previews/blue-preview.png',
        category: 'premium'
    },
    {
        id: 'green-natural',
        name: 'Green Natural',
        description: 'Eco-friendly green wrapper',
        url: 'assets/images/wrappers/green-label.png',
        preview: 'assets/images/wrappers/previews/green-preview.png',
        category: 'eco'
    },
    {
        id: 'red-bold',
        name: 'Red Bold',
        description: 'Eye-catching red label',
        url: 'assets/images/wrappers/red-label.png',
        preview: 'assets/images/wrappers/previews/red-preview.png',
        category: 'bold'
    },
    {
        id: 'minimal-clear',
        name: 'Minimal Clear',
        description: 'Transparent label with subtle design',
        url: 'assets/images/wrappers/clear-label.png',
        preview: 'assets/images/wrappers/previews/clear-preview.png',
        category: 'minimal'
    }
];

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WRAPPER_LIBRARY;
}

