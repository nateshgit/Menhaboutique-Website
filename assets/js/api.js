// PHP/MySQL backend — Supabase credentials removed.

// Tiny Supabase REST Wrapper
const Supabase = {
  from(table) {
    let method = "GET";
    let body = null;
    let query = "";
    let filters = [];
    let orders = [];

    const builder = {
      select(columns = "*") {
        method = "GET";
        query = `select=${columns}`;
        return this;
      },
      insert(data) {
        method = "POST";
        body = data;
        return this;
      },
      update(data) {
        method = "PATCH";
        body = data;
        return this;
      },
      delete() {
        method = "DELETE";
        return this;
      },
      eq(column, value) {
        if (value === null || value === undefined || value === "") {
          filters.push(`${column}=is.null`);
        } else {
          filters.push(`${column}=eq.${value}`);
        }
        return this;
      },
      order(column, ascending = true) {
        orders.push(`${column}.${ascending ? "asc" : "desc"}`);
        return this;
      },
      or(filter) {
        filters.push(`or=(${filter})`);
        return this;
      },
      async get() {
        return await this;
      },
      async execute() {
        return await this;
      },
      then(onFulfilled, onRejected) {
        let url = `api/supabase_shim.php?table=${table}`;
        if (method === "GET" || method === "PATCH" || method === "DELETE") {
          const params = [];
          if (query) params.push(query);
          if (filters.length) params.push(...filters);
          if (orders.length) params.push(`order=${orders.join(",")}`);
          if (params.length) url += `&${params.join("&")}`;
        }

        if (
          (method === "PATCH" || method === "DELETE") &&
          filters.length === 0
        ) {
          return Promise.reject(
            new Error(`${method} requires a WHERE clause (use .eq())`),
          ).catch(onRejected);
        }

        const options = {
          method: method,
          headers: {
            "Content-Type": "application/json",
          },
        };
        if (body) options.body = JSON.stringify(body);

        return fetch(url, options)
          .then(async (response) => {
            if (!response.ok) {
              const errorText = await response.text();
              console.error(`Supabase ${method} Error:`, errorText);
              throw new Error(errorText || `Supabase ${method} failed`);
            }
            if (response.status === 204) return [];
            try {
              return await response.json();
            } catch (e) {
              return [];
            }
          })
          .then(onFulfilled, onRejected);
      },
    };
    return builder;
  },
  async rpc(fn, params = {}) {
    const response = await fetch(`api/supabase_shim.php?rpc=${encodeURIComponent(fn)}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(params),
    });
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || "RPC failed");
    }
    return await response.json();
  },
};

window.Supabase = Supabase;

// ── Supabase Cart Sync (cross-device cart for logged-in users) ──
const SupabaseCartSync = {
  async getOrCreateCart(userId) {
    try {
      // 1. Fetch ALL active carts for this user
      const carts = await Supabase.from("carts")
        .select("*")
        .eq("user_id", userId)
        .eq("status", "active")
        .order("created_at", true) // Oldest first = the master
        .get();

      if (carts && carts.length > 0) {
        const masterCart = carts[0];

        // 2. If multiple carts exist, merge and cleanup duplicates
        if (carts.length > 1) {
          console.warn(
            `Deduplicating ${carts.length} carts for user ${userId}`,
          );
          for (let i = 1; i < carts.length; i++) {
            const extraCart = carts[i];
            // Fetch items from extra cart
            const items = await this.getCartItems(extraCart.id);
            for (const item of items) {
              // Move items to master
              await this.upsertItem(masterCart.id, item.product, item.quantity);
            }
            // Deactivate the extra cart record
            await Supabase.from("carts")
              .eq("id", extraCart.id)
              .update({ status: "merged" });
          }
          // Final touch to master
          await this._touchCart(masterCart.id);
        }
        return masterCart;
      }

      // 3. If none found, create exactly one
      const result = await Supabase.from("carts").insert({
        user_id: userId,
        status: "active",
        updated_at: new Date().toISOString(),
      });
      const newCart = Array.isArray(result) ? result[0] : result;
      if (newCart) return newCart;

      // Fallback retry
      const retry = await Supabase.from("carts")
        .select("*")
        .eq("user_id", userId)
        .eq("status", "active")
        .get();
      return retry && retry.length ? retry[0] : null;
    } catch (e) {
      console.error("SupabaseCartSync.getOrCreateCart:", e);
      return null;
    }
  },

  async getCartItems(cartId) {
    try {
      return await Supabase.from("cart_items")
        .select("*, product:products(*)")
        .eq("cart_id", cartId)
        .get();
    } catch (e) {
      console.error("SupabaseCartSync.getCartItems:", e);
      return [];
    }
  },

  async upsertItem(cartId, product, quantity) {
    try {
      const variantId = product.variant_id || null;
      // Fetch existing items for this cart+product
      const existing = await Supabase.from("cart_items")
        .select("id,quantity,variant_id")
        .eq("cart_id", cartId)
        .eq("product_id", product.id)
        .get();

      const match = existing.find(
        (i) => String(i.variant_id || "") === String(variantId || ""),
      );

      if (match) {
        await Supabase.from("cart_items")
          .eq("id", match.id)
          .update({
            quantity: match.quantity + quantity,
            updated_at: new Date().toISOString(),
          });
      } else {
        await Supabase.from("cart_items").insert({
          cart_id: cartId,
          product_id: product.id,
          variant_id: variantId || null,
          quantity: quantity,
          unit_price: MainAPI.getProductPrice(product),
          product_snapshot: JSON.stringify(product),
          updated_at: new Date().toISOString(),
        });
      }
      await this._touchCart(cartId);
    } catch (e) {
      console.error("SupabaseCartSync.upsertItem:", e);
    }
  },

  async setItemQuantity(cartId, productId, variantId, quantity) {
    try {
      const vId = variantId || null;
      if (quantity <= 0) {
        await this.removeItem(cartId, productId, vId);
        return;
      }

      // Direct update using multiple filters
      await Supabase.from("cart_items")
        .update({ quantity, updated_at: new Date().toISOString() })
        .eq("cart_id", cartId)
        .eq("product_id", productId)
        .eq("variant_id", vId);

      await this._touchCart(cartId);
    } catch (e) {
      console.error("SupabaseCartSync.setItemQuantity:", e);
    }
  },

  async removeItem(cartId, productId, variantId) {
    try {
      const vId = variantId || null;
      // Direct delete using multiple filters — much more robust
      await Supabase.from("cart_items")
        .delete()
        .eq("cart_id", cartId)
        .eq("product_id", productId)
        .eq("variant_id", vId);

      await this._touchCart(cartId);
    } catch (e) {
      console.error("SupabaseCartSync.removeItem:", e);
    }
  },

  async clearCart(cartId) {
    try {
      await Supabase.from("cart_items").delete().eq("cart_id", cartId);
      await this._touchCart(cartId);
    } catch (e) {
      console.error("SupabaseCartSync.clearCart:", e);
    }
  },

  async _touchCart(cartId) {
    try {
      await Supabase.from("carts").eq("id", cartId).update({
        updated_at: new Date().toISOString(),
      });
    } catch (e) {
      /* non-critical */
    }
  },
};

window.SupabaseCartSync = SupabaseCartSync;

const MainAPI = {
  async fetchBanners() {
    try {
      const results = await Supabase.from("banners")
        .select("*")
        .eq("is_active", true)
        .order("sequence", true)
        .get();
      return Array.isArray(results) ? results : results?.data || [];
    } catch (error) {
      console.error("Error fetching banners:", error);
      return [];
    }
  },

  async fetchCategories() {
    try {
      return await Supabase.from("categories")
        .select("*")
        .order("sequence", true)
        .get();
    } catch (error) {
      console.error("Error fetching categories:", error);
      return [];
    }
  },

  async fetchProducts() {
    try {
      return await Supabase.from("products")
        .select("*,product_attributes(*)")
        .order("sequence", true)
        .get();
    } catch (error) {
      console.error("Error fetching products:", error);
      return [];
    }
  },

  async fetchProductsByCategory(categoryId) {
    try {
      return await Supabase.from("products")
        .select("*,product_attributes(*)")
        .eq("category_id", categoryId)
        .order("sequence", true)
        .get();
    } catch (error) {
      console.error("Error fetching products by category:", error);
      return [];
    }
  },

  async fetchProductById(id) {
    try {
      const results = await Supabase.from("products")
        .select("*,product_attributes(*)")
        .eq("id", id)
        .get();
      const product = results.length ? results[0] : null;
      if (product) {
        const [images, reviews] = await Promise.all([
          this.fetchProductImages(id),
          this.fetchReviews(id),
        ]);
        product.additional_images = images;
        product.reviews = reviews;
      }
      return product;
    } catch (error) {
      console.error("Error fetching product by ID:", error);
      return null;
    }
  },

  async fetchProductImages(productId) {
    try {
      return await Supabase.from("product_images")
        .select("*")
        .eq("product_id", productId)
        .order("display_order", true)
        .get();
    } catch (error) {
      console.error("Error fetching product images:", error);
      return [];
    }
  },

  async fetchReviews(productId) {
    try {
      return await Supabase.from("product_reviews")
        .select("*, users(first_name, last_name)")
        .eq("product_id", productId)
        .order("created_at", false)
        .get();
    } catch (error) {
      console.error("Error fetching reviews:", error);
      return [];
    }
  },

  async submitReview(reviewData) {
    try {
      const user = this.getUser();
      if (!user) throw new Error("You must be logged in to provide a review");

      const payload = {
        product_id: reviewData.product_id,
        user_id: user.id,
        rating: parseInt(reviewData.rating),
        comment: reviewData.comment,
        updated_at: new Date().toISOString(),
      };

      return await Supabase.from("product_reviews").insert(payload);
    } catch (error) {
      console.error("Error submitting review:", error);
      throw error;
    }
  },

  getStockStatus(product) {
    const explicit = (product && product.status) || "";
    if (explicit === "Coming Soon") return "Coming Soon";
    const qty = parseInt((product && product.stock_quantity) || 0);
    if (qty > 0) return "In Stock";
    return "Out of Stock";
  },

  getProductImage(product) {
    if (product.primary_image) return product.primary_image;
    if (product.image) return product.image;
    if (product.imageUrl) return product.imageUrl;
    return "https://via.placeholder.com/300x300?text=No+Image";
  },

  getProductPrice(product) {
    if (product.price && !product.new_price) return parseFloat(product.price);
    return parseFloat(product.new_price || product.price || 0);
  },

  async upsertUserAddress(addr) {
    try {
      const res = await fetch("api/addresses.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(addr),
      });
      const data = await res.json();
      if (!res.ok || data.error)
        throw new Error(data.error || "Failed to save address");
      return data;
    } catch (error) {
      console.error("Address upsert error:", error);
      throw error;
    }
  },

  async saveAddress(addr) {
    return this.upsertUserAddress(addr);
  },

  async getNextOrderNumber() {
    try {
      const results = await Supabase.from("order_prefix").select("*").get();
      if (!results || !results.length) {
        return "ORD-" + Math.floor(Math.random() * 10000);
      }

      const config = results[0];
      const prefix = config.prefix || "ORD";
      const sequence = parseInt(config.next_sequence || 1000);
      const orderNum = `${prefix}-${sequence}`;

      await Supabase.from("order_prefix")
        .update({ next_sequence: sequence + 1 })
        .eq("id", config.id);
      return orderNum;
    } catch (e) {
      console.error("Order sequence error:", e);
      return "ORD-" + Math.floor(Math.random() * 10000);
    }
  },

  async decrementStock(productId, attributeId, qty) {
    if (!productId || !qty) return;

    if (attributeId) {
      try {
        const variants = await Supabase.from("product_attributes")
          .select("stock_quantity")
          .eq("id", attributeId)
          .get();
        if (variants && variants.length) {
          const newStock = Math.max(
            0,
            parseInt(variants[0].stock_quantity || 0) - qty,
          );
          await Supabase.from("product_attributes")
            .eq("id", attributeId)
            .update({ stock_quantity: newStock });
        }
      } catch (e) {
        console.warn("Variant stock update failed", e);
      }
    }

    const products = await Supabase.from("products")
      .select("stock_quantity,status")
      .eq("id", productId)
      .get();
    if (!products || !products.length) return;

    const product = products[0];
    const newStock = Math.max(0, parseInt(product.stock_quantity || 0) - qty);
    const update = {
      stock_quantity: newStock,
      updated_at: new Date().toISOString(),
    };
    if (product.status !== "Coming Soon") {
      update.status = newStock > 0 ? "In Stock" : "Out of Stock";
    }
    await Supabase.from("products").eq("id", productId).update(update);
  },

  async createOrder(orderData) {
    try {
      const user = this.getUser();
      const payload = {
        user_id: user ? user.id : null,
        email: orderData.email,
        total_price: orderData.total_price,
        payment_status: orderData.payment_status || "unpaid",
        payment_method:
          orderData.payment_method || orderData.paymentMethod || "cod",
        delivery_charge:
          orderData.delivery_charge || orderData.deliveryCharge || 0,
        address_id: orderData.shippingAddressId || orderData.address_id,
        comments: orderData.comments || "",
        gateway_transaction_id: orderData.gateway_transaction_id || null,
        courier_id: orderData.courier_id || null,
        newAddress: orderData.newAddress || null,
        items: (orderData.items || []).map((item) => ({
          product_id: item.productId || item.product_id,
          quantity: item.quantity,
          price: item.price || item.unit_price,
          variant_id: item.variantId || item.attribute_id || null,
        })),
      };

      const res = await fetch("api/order.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!res.ok || data.error) {
        throw new Error(data.error || "Failed to place order");
      }
      return data;
    } catch (error) {
      console.error("Order error:", error);
      throw error;
    }
  },

  async getAvailableCouriers() {
    try {
      const res = await fetch("api/couriers.php");
      return await res.json();
    } catch (e) {
      console.error(e);
      return [];
    }
  },

  async getAvailableGateways() {
    try {
      const res = await fetch("api/gateways.php");
      return await res.json();
    } catch (e) {
      console.error(e);
      return [];
    }
  },

  async updateOrderPayment(orderNumber, status, transactionId = null) {
    try {
      const data = {
        payment_status: status,
        updated_at: new Date().toISOString(),
      };
      if (transactionId) data.payment_link = transactionId;

      await Supabase.from("orders")
        .eq("order_number", orderNumber)
        .update(data);
      return true;
    } catch (e) {
      console.error("Error updating order payment:", e);
      return false;
    }
  },

  async initiateGatewayPayment(order, gateway) {
    const type = gateway.type.toLowerCase();

    switch (type) {
      case "razorpay":
        return this.handleRazorpay(order, gateway);
      case "phonepe":
        return {
          type: "redirect",
          url: await this.generatePhonePePaymentLink(order, gateway),
        };
      case "stripe":
        return this.handleStripe(order, gateway);
      case "cashfree":
        return this.handleCashfree(order, gateway);
      case "payu":
        return this.handlePayU(order, gateway);
      case "ccavenue":
        return this.handleCCAvenue(order, gateway);
      default:
        throw new Error("Selected payment gateway not supported yet");
    }
  },

  async handleRazorpay(order, gateway) {
    const creds = gateway.credentials;
    const options = {
      key: creds.key_id,
      amount: Math.round(order.total_price * 100),
      currency: "INR",
      name: "Menha Boutique",
      description: "Order Payment",
      image: "assets/images/logo.jpg",
      handler: function (response) {
        window.location.href =
          window.location.origin +
          window.location.pathname +
          `?status=success&payment_id=${response.razorpay_payment_id}`;
      },
      prefill: {
        name: order.newAddress
          ? `${order.newAddress.first_name} ${order.newAddress.last_name}`
          : "",
        email: order.email || "",
      },
      theme: { color: "#7c3aed" },
      modal: {
        ondismiss: function () {
          window.location.reload();
        },
      },
    };

    return {
      type: "function",
      fn: () => {
        const rzp = new Razorpay(options);
        rzp.open();
      },
    };
  },

  async generatePhonePePaymentLink(order, gateway) {
    const creds = gateway.credentials;
    const merchantId = creds.merchantId;
    const saltKey = creds.saltKey;
    const saltIndex = creds.saltIndex;
    const isTest = gateway.is_test_mode;

    const transactionId = "TXN" + Date.now();
    const amount = Math.round(order.total_price * 100);

    const payload = {
      merchantId,
      merchantTransactionId: transactionId,
      merchantUserId: order.user_id || "GUEST",
      amount,
      redirectUrl:
        window.location.origin +
        window.location.pathname +
        `?status=success&transactionId=${transactionId}`,
      redirectMode: "REDIRECT",
      callbackUrl: window.location.origin + window.location.pathname,
      paymentInstrument: { type: "PAY_PAGE" },
    };

    const base64Payload = btoa(JSON.stringify(payload));
    const stringToHash = base64Payload + "/pg/v1/pay" + saltKey;
    const sha256 = CryptoJS.SHA256(stringToHash).toString();
    const xVerify = sha256 + "###" + saltIndex;

    if (isTest) {
      return (
        window.location.origin +
        window.location.pathname +
        `?status=success&transactionId=${transactionId}`
      );
    }

    return `https://merchants.phonepe.com/pg/v1/pay?payload=${base64Payload}&x-verify=${xVerify}`;
  },

  async handleStripe(order, gateway) {
    alert(
      "Stripe Integration requires a backend endpoint to create Checkout Session.",
    );
    throw new Error("Stripe logic pending backend implementation.");
  },

  async handleCashfree(order, gateway) {
    alert("Cashfree Integration requires server-side token generation.");
    throw new Error("Cashfree logic pending backend implementation.");
  },

  async handlePayU(order, gateway) {
    alert("PayU Integration requires server-side hash generation.");
    throw new Error("PayU logic pending backend implementation.");
  },

  async handleCCAvenue(order, gateway) {
    alert("CCAvenue Integration requires server-side encryption.");
    throw new Error("CCAvenue logic pending backend implementation.");
  },

  async getActiveGateway() {
    try {
      const gateways = await this.getAvailableGateways();
      return gateways.length ? gateways[0] : null;
    } catch (e) {
      console.error(e);
      return null;
    }
  },

  async calculateDeliveryCharge(stateCode, items) {
    try {
      const payload = {
        state: stateCode,
        items: items.map((item) => ({
          product_id: item.product.id,
          quantity: item.quantity,
        })),
      };
      const res = await fetch("api/delivery.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      return data.delivery_charge || 0;
    } catch (e) {
      console.error("Calculation error:", e);
      return 0;
    }
  },

  async login(emailOrPhone, password) {
    try {
      const res = await fetch("api/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ identifier: emailOrPhone, password }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Invalid credentials");
      this.setAuthToken(data.token, data.user);
      return data;
    } catch (error) {
      console.error("Login Error:", error);
      throw error;
    }
  },

  async register(userData) {
    try {
      const res = await fetch("api/register.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(userData),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Registration failed");
      this.setAuthToken(data.token, data.user);
      return data;
    } catch (error) {
      console.error("Registration Error:", error);
      throw error;
    }
  },

  async getUserAddresses() {
    try {
      const res = await fetch("api/addresses.php");
      return await res.json();
    } catch (error) {
      console.error("Error fetching addresses:", error);
      return [];
    }
  },

  async deleteAddress(addressId) {
    try {
      const res = await fetch("api/addresses.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id: addressId }),
      });
      const data = await res.json();
      if (!res.ok || data.error)
        throw new Error(data.error || "Failed to delete address");
      return data;
    } catch (error) {
      console.error("Error deleting address:", error);
      throw error;
    }
  },

  async getOrders() {
    try {
      const res = await fetch("api/orders.php");
      return await res.json();
    } catch (error) {
      console.error("Error fetching orders:", error);
      return [];
    }
  },

  async getCountries() {
    try {
      const res = await fetch("api/location.php?action=countries");
      return await res.json();
    } catch (error) {
      console.error(error);
      return [];
    }
  },

  async getStates(countryId) {
    try {
      const res = await fetch(
        `api/location.php?action=states&country_id=${encodeURIComponent(countryId)}`,
      );
      return await res.json();
    } catch (error) {
      console.error(error);
      return [];
    }
  },

  async getCities(stateId) {
    try {
      const res = await fetch(
        `api/location.php?action=cities&state_id=${encodeURIComponent(stateId)}`,
      );
      return await res.json();
    } catch (error) {
      console.error(error);
      return [];
    }
  },

  setAuthToken(token, user) {
    // No-op: auth is handled by PHP session
  },
  getAuthToken() {
    return (window.MB_AUTH && window.MB_AUTH.isLoggedIn) ? 'php-session' : null;
  },
  getUser() {
    return (window.MB_AUTH && window.MB_AUTH.user) ? window.MB_AUTH.user : null;
  },
  logout() {
    window.location.href = 'logout.php';
  },
  isAuthenticated() {
    return !!(window.MB_AUTH && window.MB_AUTH.isLoggedIn);
  },

  async requestPasswordReset(email) {
    try {
      const res = await fetch("api/reset-password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "request_reset", email }),
      });
      const data = await res.json();
      if (!res.ok)
        throw new Error(data.error || "Failed to request password reset");
      return data;
    } catch (e) {
      console.error("Reset error:", e);
      return { success: true }; // Don't expose exist/not-exist email
    }
  },

  async verifyPasswordOTP(email, otp) {
    try {
      const res = await fetch("api/reset-password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "verify_otp", email, otp }),
      });
      const data = await res.json();
      return res.ok && data.success;
    } catch (e) {
      return false;
    }
  },

  async updatePassword(email, otp, newPassword) {
    try {
      const res = await fetch("api/reset-password.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "update_password",
          email,
          otp,
          new_password: newPassword,
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "Failed to update password");
      return data;
    } catch (e) {
      throw e;
    }
  },

  async sendEmail(to, type, data = {}) {
    try {
      const res = await fetch("api/send-email.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ to, type, data }),
      });
      return await res.json();
    } catch (e) {
      console.error("sendEmail error:", e);
    }
  },
};

// ── CartManager ──────────────────────────────────────────────
// Auth and cart state come entirely from PHP session + DB.
// No localStorage is used. _items is an in-memory array that
// is populated from the server on page load.
// ─────────────────────────────────────────────────────────────
const CartManager = {
  _items: [],

  getCart() {
    return this._items;
  },

  _notify() {
    window.dispatchEvent(new Event("cartUpdated"));
  },

  // Load cart from server (called once on DOMContentLoaded).
  // If PHP already rendered the cart (window._phpCartItems), use that directly
  // to avoid a duplicate fetch on the cart page.
  async init() {
    if (Array.isArray(window._phpCartItems)) {
      this._items = window._phpCartItems;
      // Don't null it out — cart.php inline script may reference it too
      this._notify();
      return;
    }
    try {
      const res = await fetch("api/cart.php?action=my_cart");
      const data = await res.json();
      this._items = data.items || [];
    } catch (e) {
      // On failure keep _items as-is; do NOT call _notify() so the PHP-rendered
      // badge count is not overwritten with 0
      console.warn("Cart load failed, retaining server-rendered count", e);
      return;
    }
    this._notify();
  },

  // Pull the latest cart from the DB without triggering a full page reload.
  // Used for cross-device sync (visibilitychange + interval polling).
  async syncFromDB() {
    if (!(window.MB_AUTH && window.MB_AUTH.isLoggedIn)) return;
    try {
      const res = await fetch("api/cart.php?action=my_cart");
      if (!res.ok) return;
      const data = await res.json();
      const fresh = data.items || [];
      const changed = JSON.stringify(this._items) !== JSON.stringify(fresh);
      this._items = fresh;
      if (changed) this._notify();
    } catch (e) {
      console.warn("Cart sync failed:", e);
    }
  },

  add(product, quantity = 1) {
    const variantId = product.variant_id || null;
    const existing = this._items.find(
      (i) =>
        i.product.id === product.id &&
        (i.product.variant_id || null) === variantId,
    );
    if (existing) existing.quantity += quantity;
    else this._items.push({ product, quantity });
    this._notify();

    fetch("api/cart.php?action=add", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ product, quantity }),
    }).catch((e) => console.warn("Cart add failed:", e));

    if (typeof window.showToast === "function") {
      window.showToast(
        `Added "${product.title || product.name || "Item"}" to cart`,
        "success",
      );
    }
  },

  update(productId, quantity, variantId = null) {
    const vId = variantId || null;
    if (quantity <= 0) {
      this.remove(productId, variantId);
      return;
    }
    const item = this._items.find(
      (i) =>
        String(i.product.id) === String(productId) &&
        (i.product.variant_id || null) === vId,
    );
    if (!item) return;
    item.quantity = quantity;
    this._notify();

    fetch("api/cart.php?action=update", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ productId, quantity, variantId: vId }),
    }).catch((e) => console.warn("Cart update failed:", e));
  },

  remove(productId, variantId = null) {
    const vId = variantId || null;
    this._items = this._items.filter(
      (i) =>
        !(String(i.product.id) === String(productId) && (i.product.variant_id || null) === vId),
    );
    this._notify();

    fetch("api/cart.php?action=remove_item", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ productId, variantId: vId }),
    }).catch((e) => console.warn("Cart remove failed:", e));
  },

  clear() {
    this._items = [];
    this._notify();

    fetch("api/cart.php?action=clear_cart", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
    }).catch((e) => console.warn("Cart clear failed:", e));
  },

  getTotalItems() {
    return this._items.reduce((sum, item) => sum + item.quantity, 0);
  },

  getTotalPrice() {
    return this._items.reduce((sum, item) => {
      const price = parseFloat(
        item.product.new_price || item.product.price || 0,
      );
      return sum + price * item.quantity;
    }, 0);
  },
};

// ── WishlistManager ──────────────────────────────────────────
// Wishlist state is synchronized with localStorage for guest users,
// and synchronized with the database wishlists table for logged-in users.
// ─────────────────────────────────────────────────────────────
const WishlistManager = {
  _items: [],

  getWishlist() {
    return this._items;
  },

  _notify() {
    window.dispatchEvent(new Event("wishlistUpdated"));
  },

  async init() {
    if (window.MB_AUTH && window.MB_AUTH.isLoggedIn) {
      try {
        const res = await fetch("api/supabase_shim.php?table=wishlists&select=*,product(*)");
        if (res.ok) {
          const data = await res.json();
          if (Array.isArray(data)) {
            this._items = data.map(item => item.product).filter(Boolean);
          }
        }
      } catch (e) {
        console.warn("Wishlist load failed:", e);
      }
    } else {
      // Guest
      const local = localStorage.getItem("mb_wishlist");
      if (local) {
        try {
          this._items = JSON.parse(local);
        } catch (e) {
          this._items = [];
        }
      }
    }
    this._notify();
  },

  has(productId) {
    return this._items.some(p => String(p.id) === String(productId));
  },

  async toggle(product) {
    if (this.has(product.id)) {
      await this.remove(product.id);
    } else {
      await this.add(product);
    }
  },

  async add(product) {
    if (this.has(product.id)) return;
    this._items.push(product);
    this._notify();

    if (window.MB_AUTH && window.MB_AUTH.isLoggedIn) {
      try {
        await fetch("api/supabase_shim.php?table=wishlists", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ product_id: product.id })
        });
      } catch (e) {
        console.warn("Wishlist add failed:", e);
      }
    } else {
      localStorage.setItem("mb_wishlist", JSON.stringify(this._items));
    }

    if (typeof window.showToast === "function") {
      window.showToast(
        `Added "${product.title || product.name || "Item"}" to wishlist`,
        "success"
      );
    }
  },

  async remove(productId) {
    this._items = this._items.filter(p => String(p.id) !== String(productId));
    this._notify();

    if (window.MB_AUTH && window.MB_AUTH.isLoggedIn) {
      try {
        await fetch(`api/supabase_shim.php?table=wishlists&product_id=eq.${productId}`, {
          method: "DELETE"
        });
      } catch (e) {
        console.warn("Wishlist remove failed:", e);
      }
    } else {
      localStorage.setItem("mb_wishlist", JSON.stringify(this._items));
    }

    if (typeof window.showToast === "function") {
      window.showToast("Removed from wishlist", "info");
    }
  },

  async mergeGuestWishlistOnLogin() {
    const local = localStorage.getItem("mb_wishlist");
    if (!local) return;
    try {
      const list = JSON.parse(local);
      if (Array.isArray(list) && list.length > 0) {
        for (const item of list) {
          try {
            await fetch("api/supabase_shim.php?table=wishlists", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ product_id: item.id })
            });
          } catch (e) {
            // Ignore duplicate/fail
          }
        }
      }
      localStorage.removeItem("mb_wishlist");
      await this.init();
    } catch (e) {
      console.warn("Guest wishlist merge failed:", e);
    }
  }
};

window.MainAPI = MainAPI;
window.CartManager = CartManager;
window.WishlistManager = WishlistManager;
