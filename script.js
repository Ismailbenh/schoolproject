// ── script.js ──

// ════════════════════════════════════
// CART HELPERS
// ════════════════════════════════════

function getCart() {
    return JSON.parse(localStorage.getItem("cart")) || [];
}

function saveCart(cart) {
    localStorage.setItem("cart", JSON.stringify(cart));
}

// ════════════════════════════════════
// UPDATE UI (aside + modal + footer)
// ════════════════════════════════════

function updateCartUI() {
    const cart  = getCart();
    const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);

    // Aside summary
    const summary = document.querySelector(".cartSummary");
    if (summary) {
        summary.innerHTML = count === 0
            ? `<p class="cartSummaryEmpty">Your cart is empty.</p>`
            : `<p style="color:var(--text-color);font-size:13px;">${count} item(s)</p>
               <p style="color:var(--accent);font-weight:700;">$${total.toFixed(2)}</p>`;
    }

    // Modal + footer totals
    document.querySelectorAll(".cartTotal span, .checkoutTotal span")
        .forEach(el => el.textContent = `$${total.toFixed(2)}`);
}

// ════════════════════════════════════
// RENDER CART MODAL ITEMS
// ════════════════════════════════════

function renderCartModal() {
    const cart     = getCart();
    const cartBody = document.querySelector(".cartModalBody");
    if (!cartBody) return;

    if (cart.length === 0) {
        cartBody.innerHTML = `<p class="emptyCart">Your cart is empty</p>`;
        return;
    }

    cartBody.innerHTML = cart.map(item => `
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:10px 0;border-bottom:1px solid var(--border);
                    font-size:14px;color:var(--text-color);">
            <div>
                <p style="font-weight:700;margin-bottom:4px;">${item.name}</p>
                <p style="color:var(--text-muted);">Qty: ${item.quantity} × $${item.price.toFixed(2)}</p>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="color:var(--accent);font-weight:700;">$${(item.price * item.quantity).toFixed(2)}</span>
                <button onclick="removeFromCart('${item.product_id}')"
                        style="background:none;border:none;color:var(--text-muted);font-size:18px;cursor:pointer;">
                    &times;
                </button>
            </div>
        </div>
    `).join("");
}

// ════════════════════════════════════
// ADD / REMOVE FROM CART
// ════════════════════════════════════

function removeFromCart(productId) {
    saveCart(getCart().filter(item => item.product_id !== productId));
    updateCartUI();
    renderCartModal();
}

// ════════════════════════════════════
// INIT — runs when page loads
// ════════════════════════════════════

document.addEventListener("DOMContentLoaded", function () {

    // Restore cart state on every page load
    updateCartUI();

    // ── Add to Cart buttons ──
    document.querySelectorAll(".addToCart").forEach(btn => {
        btn.addEventListener("click", function () {
            const article = btn.closest(".categoryArticle");
            if (!article) return;

            const name  = article.querySelector(".productName")?.textContent.trim() || "Unknown";
            const price = parseFloat(article.querySelector(".productPrice")?.textContent.replace(/[^0-9.]/g, "") || 0);
            const qty   = parseInt(article.querySelector(".qtyInput")?.value || 1);
            const id    = name.toLowerCase().replace(/\s+/g, "_");

            const cart     = getCart();
            const existing = cart.find(i => i.product_id === id);
            existing ? existing.quantity += qty : cart.push({ product_id: id, name, price, quantity: qty });

            saveCart(cart);
            updateCartUI();

            // Button feedback
            btn.textContent = "Added ✓";
            btn.style.background = "#3a7a3a";
            setTimeout(() => { btn.textContent = "Add to Cart"; btn.style.background = ""; }, 1200);
        });
    });

    // ── Render modal when cart opens ──
    const cartToggle = document.getElementById("cartToggle");
    if (cartToggle) {
        cartToggle.addEventListener("change", () => {
            if (cartToggle.checked) renderCartModal();
        });
    }

    // ── Login form ──
    const loginForm = document.querySelector(".LoginForm[data-type='login']");
    if (loginForm) {
        loginForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const email    = document.getElementById("Email").value.trim();
            const password = loginForm.querySelector("input[type='password']").value.trim();
            const errorBox = document.getElementById("loginError");
            if (errorBox) errorBox.textContent = "";

            const res    = await fetch("login.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            });
            const result = await res.json();

            result.success
                ? window.location.href = result.redirect
                : errorBox && (errorBox.textContent = result.message);
        });
    }

    // ── Register form ──
    const registerForm = document.querySelector(".LoginForm[data-type='register']");
    if (registerForm) {
        registerForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const email    = document.getElementById("Email").value.trim();
            const inputs   = registerForm.querySelectorAll("input[type='password']");
            const password = inputs[0].value.trim();
            const confirm  = inputs[1].value.trim();
            const errorBox = document.getElementById("registerError");
            if (errorBox) errorBox.textContent = "";

            if (password !== confirm) {
                if (errorBox) errorBox.textContent = "Passwords do not match.";
                return;
            }

            const res    = await fetch("register.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&confirm=${encodeURIComponent(confirm)}`
            });
            const result = await res.json();

            result.success
                ? window.location.href = result.redirect
                : errorBox && (errorBox.textContent = result.message);
        });
    }

    // ── Checkout button ──
    document.querySelector(".checkoutBtn")?.addEventListener("click", async function () {
        const cart = getCart();
        if (cart.length === 0) { alert("Your cart is empty!"); return; }

        const res    = await fetch("save_order.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ items: cart })
        });
        const result = await res.json();

        if (result.success) {
            alert("Order placed successfully!");
            localStorage.removeItem("cart");
            updateCartUI();
            renderCartModal();
        } else {
            alert("Error: " + result.message);
        }
    });

});