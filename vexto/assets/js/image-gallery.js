/**
 * Image Gallery and Zoom Manager
 * Handles image zoom, gallery navigation, and lightbox functionality
 */

class ImageGallery {
    constructor() {
        this.currentImageIndex = 0;
        this.images = [];
        this.init();
    }

    /**
     * Initialize gallery
     */
    init() {
        this.setupImageZoom();
        this.setupLightbox();
        this.setupKeyboardNavigation();
    }

    /**
     * Setup image zoom functionality
     */
    setupImageZoom() {
        const zoomableImages = document.querySelectorAll('[data-zoomable]');
        
        zoomableImages.forEach(img => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', (e) => this.openLightbox(e.target));
        });
    }

    /**
     * Setup lightbox
     */
    setupLightbox() {
        // Create lightbox HTML if it doesn't exist
        if (!document.getElementById('image-lightbox')) {
            const lightbox = document.createElement('div');
            lightbox.id = 'image-lightbox';
            lightbox.className = 'image-lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-container">
                    <button class="lightbox-close" aria-label="Cerrar">&times;</button>
                    <button class="lightbox-prev" aria-label="Anterior">&#10094;</button>
                    <div class="lightbox-content">
                        <img id="lightbox-image" src="" alt="Imagen ampliada" class="lightbox-image">
                        <div class="lightbox-info">
                            <span class="lightbox-counter"><span id="current-image">1</span> / <span id="total-images">1</span></span>
                        </div>
                    </div>
                    <button class="lightbox-next" aria-label="Siguiente">&#10095;</button>
                </div>
            `;
            document.body.appendChild(lightbox);
        }

        const lightbox = document.getElementById('image-lightbox');
        const closeBtn = lightbox.querySelector('.lightbox-close');
        const prevBtn = lightbox.querySelector('.lightbox-prev');
        const nextBtn = lightbox.querySelector('.lightbox-next');

        closeBtn.addEventListener('click', () => this.closeLightbox());
        prevBtn.addEventListener('click', () => this.previousImage());
        nextBtn.addEventListener('click', () => this.nextImage());
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) this.closeLightbox();
        });
    }

    /**
     * Setup keyboard navigation
     */
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            const lightbox = document.getElementById('image-lightbox');
            if (!lightbox || !lightbox.classList.contains('active')) return;

            switch(e.key) {
                case 'Escape':
                    this.closeLightbox();
                    break;
                case 'ArrowLeft':
                    this.previousImage();
                    break;
                case 'ArrowRight':
                    this.nextImage();
                    break;
            }
        });
    }

    /**
     * Open lightbox with image
     */
    openLightbox(img) {
        const gallery = img.closest('[data-gallery]');
        
        if (gallery) {
            // Get all images in the gallery
            this.images = Array.from(gallery.querySelectorAll('[data-zoomable]'));
            this.currentImageIndex = this.images.indexOf(img);
        } else {
            // Single image
            this.images = [img];
            this.currentImageIndex = 0;
        }

        this.displayImage();
        
        const lightbox = document.getElementById('image-lightbox');
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close lightbox
     */
    closeLightbox() {
        const lightbox = document.getElementById('image-lightbox');
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    /**
     * Display current image
     */
    displayImage() {
        if (this.images.length === 0) return;

        const img = this.images[this.currentImageIndex];
        const lightboxImg = document.getElementById('lightbox-image');
        const currentCounter = document.getElementById('current-image');
        const totalCounter = document.getElementById('total-images');
        const prevBtn = document.querySelector('.lightbox-prev');
        const nextBtn = document.querySelector('.lightbox-next');

        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt || 'Imagen';
        currentCounter.textContent = this.currentImageIndex + 1;
        totalCounter.textContent = this.images.length;

        // Hide navigation buttons if only one image
        if (this.images.length === 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
        }
    }

    /**
     * Show next image
     */
    nextImage() {
        if (this.images.length <= 1) return;
        this.currentImageIndex = (this.currentImageIndex + 1) % this.images.length;
        this.displayImage();
    }

    /**
     * Show previous image
     */
    previousImage() {
        if (this.images.length <= 1) return;
        this.currentImageIndex = (this.currentImageIndex - 1 + this.images.length) % this.images.length;
        this.displayImage();
    }
}

// Initialize gallery when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new ImageGallery();
    });
} else {
    new ImageGallery();
}
