// Always start fresh page loads scrolled to the top (no mid/lower restore)
if ("scrollRestoration" in history) {
  history.scrollRestoration = "manual";
}
window.addEventListener("pageshow", () => window.scrollTo(0, 0));

// ── Site-wide toast ─────────────────────────────────────────
function ensureToastStack() {
  let stack = document.getElementById("mb-toast-stack");
  if (!stack) {
    stack = document.createElement("div");
    stack.id = "mb-toast-stack";
    stack.className = "toast-stack";
    document.body.appendChild(stack);
  }
  return stack;
}
const TOAST_ICONS = { success: "check", error: "alert-triangle", info: "info" };
window.showToast = function (message, type = "info", duration = 3500) {
  const stack = ensureToastStack();
  const t = document.createElement("div");
  t.className = `toast ${type}`;
  t.innerHTML = `
        <span class="toast-icon"><i data-lucide="${TOAST_ICONS[type] || "info"}"></i></span>
        <span class="toast-msg">${message}</span>
        <button class="toast-close" aria-label="Dismiss">✕</button>
    `;
  stack.appendChild(t);
  if (typeof lucide !== "undefined") lucide.createIcons();
  requestAnimationFrame(() => t.classList.add("show"));
  const close = () => {
    t.classList.remove("show");
    setTimeout(() => t.remove(), 300);
  };
  t.querySelector(".toast-close").addEventListener("click", close);
  setTimeout(close, duration);
};

document.addEventListener("DOMContentLoaded", async () => {
  window.scrollTo(0, 0);

  if (typeof lucide !== "undefined") {
    lucide.createIcons();
  }

  window.addEventListener("cartUpdated", updateCartBadge);

  try {
    await CartManager.init();
    updateCartBadge();
  } catch (e) {
    console.warn("Cart init failed:", e);
  }

  // Cross-device cart sync for logged-in users:
  // Re-fetch on tab focus and every 60 s so changes on another device appear promptly.
  if (window.MB_AUTH && window.MB_AUTH.isLoggedIn) {
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "visible") CartManager.syncFromDB();
    });
    setInterval(() => CartManager.syncFromDB(), 60000);
  }
});


let badgeAnimFrame = null;
function updateCartBadge() {
  const badge = document.getElementById("cart-badge");
  if (!badge) return;
  const count = CartManager.getTotalItems();

  const oldCount = parseInt(badge.innerText) || 0;
  if (count === oldCount) {
    badge.style.display = count > 0 ? "flex" : "none";
    return;
  }

  // Cancel existing animation loop if any
  if (badgeAnimFrame) {
    cancelAnimationFrame(badgeAnimFrame);
    badgeAnimFrame = null;
  }

  // "Running count" effect: animate the number change
  if (count > oldCount) {
    let current = oldCount;
    const step = () => {
      if (current < count) {
        current++;
        badge.innerText = current;
        badgeAnimFrame = requestAnimationFrame(() => setTimeout(step, 40));
      } else {
        badge.innerText = count;
        badgeAnimFrame = null;
      }
    };
    step();
  } else {
    badge.innerText = count;
  }

  if (count > 0) {
    badge.style.display = "flex";
    // Pop animation
    badge.style.transform = "translate(30%, -30%) scale(1.4)";
    badge.style.transition =
      "transform 0.15s cubic-bezier(0.175, 0.885, 0.32, 1.275)";
    setTimeout(() => {
      badge.style.transform = "translate(30%, -30%) scale(1)";
    }, 150);
  } else {
    badge.style.display = "none";
  }
}

window._productRegistry = window._productRegistry || {};

window.addToCartById = function (key) {
  const prod = window._productRegistry[key];
  if (prod) CartManager.add(prod, 1);
};

window.buyNowById = function (key) {
  const prod = window._productRegistry[key];
  if (!prod) return;
  sessionStorage.setItem(
    "mb_buynow",
    JSON.stringify([{ product: prod, quantity: 1 }]),
  );
  window.location.href = "checkout.php?buynow=1";
};

window.addToCartDirect = function (prodJson) {
  try {
    const prod = JSON.parse(decodeURIComponent(prodJson));
    CartManager.add(prod, 1);
  } catch (e) {
    console.error("addToCartDirect error:", e);
  }
};

window.buyNowDirect = function (prodJson) {
  try {
    const prod = JSON.parse(decodeURIComponent(prodJson));
    sessionStorage.setItem(
      "mb_buynow",
      JSON.stringify([{ product: prod, quantity: 1 }]),
    );
    window.location.href = "checkout.php?buynow=1";
  } catch (e) {
    console.error("buyNowDirect error:", e);
  }
};

window.addToCartFromRegistry = function (prod) {
  if (prod) {
    CartManager.add(prod, 1);
  }
};

window.buyNowFromRegistry = function (prod) {
  if (!prod) return;
  const item = {
    id: prod.id,
    title: prod.title,
    sku: prod.sku || prod.title,
    primary_image: prod.primary_image,
    status: prod.status || "In Stock",
    price: parseFloat(prod.new_price),
    selected_variant: prod.product_attributes && prod.product_attributes.length > 0 
                      ? prod.product_attributes[0].attribute_value 
                      : (prod.weight || '1 unit'),
    variant_id: prod.product_attributes && prod.product_attributes.length > 0 
                ? prod.product_attributes[0].id 
                : null
  };
  sessionStorage.setItem(
    "mb_buynow",
    JSON.stringify([{ product: item, quantity: 1 }]),
  );
  window.location.href = "checkout.php?buynow=1";
};

window.productCardHtml = function (prod) {
  window._productRegistry[prod.id] = prod;
  const img = MainAPI.getProductImage(prod);
  const price = MainAPI.getProductPrice(prod);
  const stockStatus = MainAPI.getStockStatus(prod);
  const stockClass =
    stockStatus === "In Stock"
      ? "in-stock"
      : stockStatus === "Coming Soon"
        ? "coming-soon"
        : "out-of-stock";
  const canAdd = stockStatus === "In Stock";
  const rating = prod.rating || "0.0";
  let unit = prod.weight || prod.unit || "";
  if (prod.product_attributes && prod.product_attributes.length > 0) {
    const v = prod.product_attributes[0].attribute_value;
    unit =
      unit && !v.toLowerCase().includes(unit.toLowerCase())
        ? `${v} ${unit}`
        : v;
  }
  const key = prod.id;
  const btns = `
        <div class="prod-card-btns" onclick="event.stopPropagation();">
            <button class="prod-add-btn${canAdd ? "" : " disabled"}" ${canAdd ? `onclick="event.stopPropagation();window.addToCartById('${key}');"` : "disabled"}><i data-lucide="shopping-cart"></i> Cart</button>
            <button class="prod-buy-btn${canAdd ? "" : " disabled"}" ${canAdd ? `onclick="event.stopPropagation();window.buyNowById('${key}');"` : "disabled"}>Buy Now</button>
        </div>`;
  return `
        <div class="product-card fade-in-stagger" onclick="window.location.href='product.php?id=${prod.id}';" style="cursor:pointer;">
            <div class="prod-img-box">
                <img src="${img}" alt="${prod.title}" loading="lazy">
            </div>
            <div class="prod-info">
                <h3 class="prod-title">${prod.title}</h3>
                <div class="prod-meta">
                    ${unit ? `<span class="prod-variant-chip">${unit}</span>` : ""}
                    <div class="rating-pill"><i data-lucide="star"></i> ${rating}</div>
                    <span class="stock-pill ${stockClass}">${stockStatus}</span>
                </div>
                <div class="prod-price">₹${price}</div>
                ${btns}
            </div>
        </div>`;
};

window.addEventListener("cartUpdated", updateCartBadge);

function toggleMobileDrawer(open) {
  const drawer = document.getElementById("mobile-drawer");
  const backdrop = document.getElementById("mobile-drawer-backdrop");
  if (!drawer || !backdrop) return;
  const shouldOpen =
    typeof open === "boolean" ? open : !drawer.classList.contains("open");
  drawer.classList.toggle("open", shouldOpen);
  backdrop.classList.toggle("open", shouldOpen);
  document.body.style.overflow = shouldOpen ? "hidden" : "";
  if (typeof lucide !== "undefined") lucide.createIcons();
}
window.toggleMobileDrawer = toggleMobileDrawer;

// ── Scroll-reveal (IntersectionObserver) ─────────────────────
(function initReveal() {
  if (!("IntersectionObserver" in window)) {
    document.querySelectorAll(".reveal").forEach(function (el) {
      el.classList.add("visible");
    });
    return;
  }
  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12 }
  );
  document.querySelectorAll(".reveal").forEach(function (el) {
    observer.observe(el);
  });
})();

