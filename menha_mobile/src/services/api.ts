import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Base URL for the PHP backend server. For Android emulator local server, use http://10.0.2.2/api
// For Web/Production, change to your domain (e.g. 'https://menhaboutique.com/api')
export const BACKEND_URL = 'https://menhaboutique.com/api';

// api instance queries the MySQL database via supabase_shim.php using standard PostgREST syntax
const api = axios.create({
  baseURL: `${BACKEND_URL}/supabase_shim.php`,
  headers: {
    'Content-Type': 'application/json',
  },
});

// customApi instance communicates directly with dedicated PHP custom endpoints (like login.php, register.php)
const customApi = axios.create({
  baseURL: BACKEND_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Helper to set auth token globally for both axios instances
export const setAuthToken = (token: string | null) => {
  const authVal = token ? `Bearer ${token}` : '';
  api.defaults.headers.common['Authorization'] = authVal;
  customApi.defaults.headers.common['Authorization'] = authVal;
  api.defaults.headers.common['X-Authorization'] = authVal;
  customApi.defaults.headers.common['X-Authorization'] = authVal;
};

// Replicating web app's MainAPI logic for Mobile
export const MainAPI = {
    getProductPrice(product: any) {
        if (product.price && !product.new_price) return parseFloat(product.price);
        return parseFloat(product.new_price || product.newPrice || product.price || 0);
    },

    async fetchBanners() {
        try {
            const res = await api.get('/banners?select=*&is_active=eq.true&order=sequence.asc');
            return res.data;
        } catch (error) {
            console.error("Error fetching banners:", error);
            return [];
        }
    },

    async fetchCategories() {
        try {
            const res = await api.get('/categories?select=*&order=sequence.asc');
            return res.data;
        } catch (error) {
            console.error("Error fetching categories:", error);
            return [];
        }
    },

    async fetchProducts() {
        try {
            const res = await api.get('/products?select=*,product_attributes(*),categories(name)&order=sequence.asc');
            return res.data;
        } catch (error) {
            console.error("Error fetching products:", error);
            return [];
        }
    },

    async fetchProductById(id: string) {
        try {
            const res = await api.get(`/products?select=*,product_attributes(*),categories(name)&id=eq.${id}`);
            const product = res.data.length ? res.data[0] : null;
            if (product) {
                const [images, reviews] = await Promise.all([
                    this.fetchProductImages(id),
                    this.fetchReviews(id)
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

    async fetchProductImages(productId: string) {
        try {
            const res = await api.get(`/product_images?select=*&product_id=eq.${productId}&order=display_order.asc`);
            return res.data;
        } catch (error) {
            console.error("Error fetching product images:", error);
            return [];
        }
    },

    async fetchReviews(productId: string) {
        try {
            const res = await api.get(`/product_reviews?select=*,users(first_name,last_name)&product_id=eq.${productId}&order=created_at.desc`);
            return res.data;
        } catch (error) {
            console.error("Error fetching reviews:", error);
            return [];
        }
    },

    async fetchHomeReviews() {
        try {
            const res = await api.get('/home_reviews?select=*&is_active=eq.true&order=sequence.asc');
            return res.data;
        } catch (error) {
            console.error("Error fetching home reviews:", error);
            return [];
        }
    },

    async login(identifier: string, password: any) {
        try {
            // Point login to secure, dedicated login.php endpoint
            const res = await customApi.post('/login.php', {
                identifier,
                password
            });
            if (res.data.error) throw new Error(res.data.error);
            const { user, token } = res.data;
            await AsyncStorage.setItem('auth_token', token);
            await AsyncStorage.setItem('user_info', JSON.stringify(user));
            setAuthToken(token);
            return { user, token };
        } catch (error: any) {
            const msg = error.response?.data?.error || error.message || "Invalid credentials";
            console.error("Login Error:", msg);
            throw new Error(msg);
        }
    },

    async register(userData: any) {
        try {
            // Point registration to dedicated register.php endpoint (with Bcrypt hashing and optional address support)
            const payload: any = {
                email: userData.email,
                password: userData.password,
                firstName: userData.firstName,
                lastName: userData.lastName,
                phoneNumber: userData.phoneNumber
            };
            if (userData.address) {
                payload.address = {
                    line1: userData.address,
                    line2: '',
                    city: userData.city,
                    state: userData.state,
                    postalCode: userData.postCode,
                    country: userData.country || 'India'
                };
            }
            const res = await customApi.post('/register.php', payload);
            if (res.data.error) throw new Error(res.data.error);
            const { user, token } = res.data;
            await AsyncStorage.setItem('auth_token', token);
            await AsyncStorage.setItem('user_info', JSON.stringify(user));
            setAuthToken(token);
            return { user, token };
        } catch (error: any) {
            const msg = error.response?.data?.error || error.message || "Registration failed";
            console.error("Registration Error:", msg);
            throw new Error(msg);
        }
    },

    async updateProfile(profileData: any) {
        try {
            const res = await customApi.post('/update_profile.php', profileData);
            if (res.data.error) throw new Error(res.data.error);
            return res.data;
        } catch (error: any) {
            const msg = error.response?.data?.error || error.message || "Failed to update profile";
            console.error("Update Profile Error:", msg);
            throw new Error(msg);
        }
    },

    async requestPasswordReset(email: string) {
        try {
            const res = await customApi.post('/reset-password.php', {
                action: 'request_reset',
                email
            });
            if (res.data.error) throw new Error(res.data.error);
            return res.data;
        } catch (error: any) {
            const msg = error.response?.data?.error || error.message || "Failed to request password reset";
            console.error("Password reset request error:", msg);
            throw new Error(msg);
        }
    },

    async verifyPasswordOTP(email: string, otp: string) {
        try {
            const res = await customApi.post('/reset-password.php', {
                action: 'verify_otp',
                email,
                otp
            });
            if (res.data.error) throw new Error(res.data.error);
            return res.data.success;
        } catch (error: any) {
            console.error("OTP verification error:", error.response?.data?.error || error.message);
            return false;
        }
    },

    async updatePassword(email: string, otp: string, newPassword: any) {
        try {
            const res = await customApi.post('/reset-password.php', {
                action: 'update_password',
                email,
                otp,
                new_password: newPassword
            });
            if (res.data.error) throw new Error(res.data.error);
            return res.data;
        } catch (error: any) {
            const msg = error.response?.data?.error || error.message || "Failed to update password";
            console.error("Password update error:", msg);
            throw new Error(msg);
        }
    },


    async getCountries() {
        try {
            const res = await api.get('/countries?select=*&order=name.asc');
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async getStates(countryId: string) {
        try {
            const res = await api.get(`/states?select=*&country_id=eq.${countryId}&order=name.asc`);
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async getCities(stateId: string) {
        try {
            const res = await api.get(`/cities?select=*&state_id=eq.${stateId}&order=name.asc`);
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async getAddresses() {
        try {
            const userJson = await AsyncStorage.getItem('user_info');
            if (!userJson) return [];
            const user = JSON.parse(userJson);
            const res = await api.get(`/addresses?select=*&user_id=eq.${user.id}`);
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async saveAddress(addr: any) {
        try {
            const userJson = await AsyncStorage.getItem('user_info');
            if (!userJson) throw new Error('Not logged in');
            const user = JSON.parse(userJson);
            
            const data = {
                user_id: user.id,
                first_name: addr.firstName || addr.first_name,
                last_name: addr.lastName || addr.last_name,
                address_line1: addr.addressLine || addr.address_line1,
                address_line2: addr.addressLine2 || addr.address_line2 || '',
                city: addr.city,
                state: addr.state,
                zip_code: addr.postalCode || addr.zip_code,
                country: addr.country || 'India',
                phone_number: addr.phoneNumber || user.phone_number,
                is_default: addr.isDefault || false,
                updated_at: new Date().toISOString()
            };

            const res = await api.post('/addresses', data, { headers: { 'Prefer': 'return=representation' } });
            return Array.isArray(res.data) ? res.data[0] : res.data;
        } catch (error) {
            console.error(error);
            throw error;
        }
    },

    async createOrder(orderData: any) {
        try {
            const userJson = await AsyncStorage.getItem('user_info');
            const user = userJson ? JSON.parse(userJson) : null;
            
            // Post single, atomic transactional request to the custom order.php endpoint
            const res = await customApi.post('/order.php', {
                user_id: user ? user.id : null,
                email: orderData.email || user?.email,
                total_price: orderData.total,
                payment_status: orderData.payment_status || 'unpaid',
                payment_method: orderData.paymentMethod,
                delivery_charge: orderData.delivery_charge || 0,
                address_id: orderData.shippingAddressId,
                gateway_transaction_id: orderData.gateway_transaction_id || null,
                courier_id: orderData.courier_id || null,
                items: orderData.items.map((item: any) => ({
                    product_id: item.productId,
                    quantity: item.quantity,
                    price: item.price,
                    variant_id: item.variantId || null
                }))
            });

            if (res.data.error) {
                throw new Error(res.data.error);
            }
            return res.data.order;
        } catch (error: any) {
            const msg = error.response?.data?.error || error.message || "Failed to place order";
            console.error("Order Creation Error:", msg);
            throw new Error(msg);
        }
    },

    async getOrders() {
        try {
            const userJson = await AsyncStorage.getItem('user_info');
            if (!userJson) return [];
            const user = JSON.parse(userJson);
            const res = await api.get(`/orders?select=*,order_items(*,products(*)),addresses(*)&user_id=eq.${user.id}&order=created_at.desc`);
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async submitReview(reviewData: any) {
        try {
            const userJson = await AsyncStorage.getItem('user_info');
            if (!userJson) throw new Error('Not logged in');
            const user = JSON.parse(userJson);

            const payload = {
                product_id: reviewData.product_id,
                user_id: user.id,
                rating: parseInt(reviewData.rating),
                comment: reviewData.comment,
                updated_at: new Date().toISOString()
            };

            const res = await api.post('/product_reviews', payload, { headers: { 'Prefer': 'return=representation' } });
            return res.data[0];
        } catch (error) {
            console.error("Error submitting review:", error);
            throw error;
        }
    },

    async getAvailableCouriers() {
        try {
            const res = await api.get('/couriers?select=*&is_active=eq.true&order=name.asc');
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async getAvailableGateways() {
        try {
            const res = await api.get('/payment_gateways?select=*&is_active=eq.true');
            return res.data;
        } catch (error) {
            console.error(error);
            return [];
        }
    },

    async deleteAddress(id: string) {
        try {
            const res = await api.delete(`/addresses?id=eq.${id}`);
            return res;
        } catch (error) {
            console.error("Delete API error:", error);
            throw error;
        }
    },

    async calculateDeliveryCharge(stateCode: string, items: any[]) {
        try {
            const [stateRes, configRes] = await Promise.allSettled([
                api.get(`/states?select=zone&code=eq.${stateCode}`),
                api.get('/delivery_config?select=calculation_mode')
            ]);

            const zone = (stateRes.status === 'fulfilled' && stateRes.value.data.length > 0) ? stateRes.value.data[0].zone : 'REST';
            const mode = (configRes.status === 'fulfilled' && configRes.value.data.length > 0) ? configRes.value.data[0].calculation_mode : 'WEIGHT';

            let thresholdValue = 0;
            if (mode === 'RATE') {
                thresholdValue = items.reduce((sum, item) => {
                    const p = item.product || item;
                    return sum + (this.getProductPrice(p) * item.quantity);
                }, 0);
            } else {
                items.forEach(item => {
                    const p = item.product || item;
                    const weightStr = p.weight || '0g';
                    const weightVal = parseInt(weightStr.replace(/[^0-9]/g, '')) || 0;
                    thresholdValue += weightVal * item.quantity;
                });
            }

            const allTariffsRes = await api.get('/delivery_tariffs?select=*&order=max_weight.asc');
            const allTariffs = allTariffsRes.data;
            
            let modeTariffs = allTariffs.filter((t: any) => (t.tariff_type || 'WEIGHT') === mode);
            // Fallback: if mode is WEIGHT and no specific weight tariffs found, use all
            if (mode === 'WEIGHT' && !modeTariffs.length && allTariffs.length) {
                modeTariffs = allTariffs;
            }

            if (!modeTariffs.length) return 0;

            const tier = modeTariffs.find((t: any) => t.max_weight >= thresholdValue) || modeTariffs[modeTariffs.length - 1];

            return tier ? (tier.prices[zone] || tier.prices['REST'] || 0) : 0;
        } catch (error) {
            console.error("Delivery charge calculation error:", error);
            return 0;
        }
    }
};

export default api;
