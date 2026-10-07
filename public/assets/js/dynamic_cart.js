// Dynamic AJAX interactions for Noir Store
document.addEventListener('DOMContentLoaded', () => {
    // Intercept Add to Cart forms
    const cartForms = document.querySelectorAll('.add-to-cart-form');
    cartForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                const data = await res.json();
                if(data.success) {
                    alert('Added to cart successfully!');
                    // Update cart counter in UI...
                }
            } catch (err) {
                console.error('Cart error:', err);
            }
        });
    });
});
