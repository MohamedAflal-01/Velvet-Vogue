/**
 * Velvet Vogue - Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // 1. Category and Product Slug Auto-Generation
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.userEdited) {
                slugInput.value = generateSlug(this.value);
            }
        });

        slugInput.addEventListener('change', function() {
            if (this.value.trim() !== '') {
                slugInput.dataset.userEdited = 'true';
            } else {
                delete slugInput.dataset.userEdited;
            }
        });
    }

    function generateSlug(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start
            .replace(/-+$/, '');            // Trim - from end
    }

    // 2. Real-time Image Upload Preview (FileReader)
    const imageInput = document.getElementById('product_image');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            // Remove existing preview if any
            const existingPreview = document.getElementById('product-image-preview');
            if (existingPreview) {
                existingPreview.remove();
            }

            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.createElement('div');
                    previewContainer.id = 'product-image-preview';
                    previewContainer.className = 'mt-3';
                    
                    const label = document.createElement('span');
                    label.className = 'd-block text-secondary small mb-1';
                    label.innerText = 'Upload Preview:';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    Object.assign(img.style, {
                        width: '120px',
                        height: '120px',
                        objectFit: 'cover'
                    });
                    
                    previewContainer.appendChild(label);
                    previewContainer.appendChild(img);
                    
                    // Insert preview right after the input field
                    imageInput.parentNode.appendChild(previewContainer);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 3. Admin Pricing Validations
    const priceForm = document.querySelector('form[action*="product_add.php"], form[action*="product_edit.php"]');
    if (priceForm) {
        priceForm.addEventListener('submit', function(event) {
            const price = parseFloat(document.getElementById('price').value);
            const salePriceInput = document.getElementById('sale_price');
            const salePrice = salePriceInput ? parseFloat(salePriceInput.value) : NaN;

            if (price <= 0) {
                event.preventDefault();
                alert('Regular price must be greater than LKR 0.');
                document.getElementById('price').focus();
                return;
            }

            if (!isNaN(salePrice) && salePrice >= price) {
                event.preventDefault();
                alert('Sale price must be lower than the regular price.');
                salePriceInput.focus();
                return;
            }
        });
    }

    // 4. Clientside Quick Table Filter (Search through rows in view)
    const clientSearchInput = document.querySelector('.input-group input[placeholder*="Search"]');
    const tableBody = document.querySelector('table tbody');
    
    if (clientSearchInput && tableBody) {
        clientSearchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');

            // If empty search, reset all rows visibility
            if (query === '') {
                rows.forEach(row => row.style.display = '');
                return;
            }

            rows.forEach(row => {
                let match = false;
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(query)) {
                        match = true;
                    }
                });
                
                if (match) {
                    row.style.display = '';
                } else {
                    // Check if it's the "No rows" placeholder row
                    if (row.cells.length > 1) {
                        row.style.display = 'none';
                    }
                }
            });
        });
    }
});
