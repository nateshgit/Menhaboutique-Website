import { Alert, Platform } from 'react-native';

let RazorpayCheckout: any = null;
let initPaymentSheet: any = null;
let presentPaymentSheet: any = null;
let Cashfree: any = null;
let PhonePe: any = null;

if (Platform.OS !== 'web') {
    try {
        RazorpayCheckout = require('react-native-razorpay').default;
        const stripe = require('@stripe/stripe-react-native');
        initPaymentSheet = stripe.initPaymentSheet;
        presentPaymentSheet = stripe.presentPaymentSheet;
        Cashfree = require('react-native-cashfree-pg-sdk');
        PhonePe = require('react-native-phonepe-pg').default;
    } catch (e) {
        console.log('Error loading payment SDKs:', e);
    }
}

class PaymentGatewayService {
    /**
     * Razorpay Integration
     */
    static async handleRazorpay(order: any, gateway: any): Promise<{ success: boolean, transactionId: any }> {
        const creds = gateway.credentials;
        const options = {
            key: creds.key_id,
            amount: Math.round(order.total_price * 100),
            currency: 'INR',
            name: 'Menha Boutique',
            description: `Order #${order.order_number || 'New'}`,
            image: 'https://wrjzdrhvrluamygexyvi.supabase.co/storage/v1/object/public/assets/logo.jpg',
            prefill: {
                email: order.email || '',
                contact: order.phone_number || '',
                name: order.customer_name || ''
            },
            theme: { color: '#7c3aed' }
        };

        try {
            const data = await RazorpayCheckout.open(options);
            return { success: true, transactionId: data.razorpay_payment_id };
        } catch (error: any) {
            console.error('Razorpay Error:', error);
            throw new Error(error.description || 'Razorpay payment failed');
        }
    }

    /**
     * Stripe Integration
     */
    static async handleStripe(order: any, gateway: any): Promise<{ success: boolean, transactionId: any }> {
        // Stripe usually requires a backend endpoint to create a PaymentIntent and get clientSecret
        // For now, we assume the backend provides the clientSecret in the gateway response or we'd need to call it.
        // Since we don't have a dedicated endpoint yet, this is a structural implementation.
        const clientSecret = gateway.temp_client_secret; // Mock property
        if (!clientSecret) throw new Error('Stripe client secret missing. Backend integration required.');

        const { error } = await initPaymentSheet({
            paymentIntentClientSecret: clientSecret,
            merchantDisplayName: 'Menha Boutique',
            allowsDelayedPaymentMethods: true,
        });

        if (error) throw new Error(error.message);

        const { error: presentError } = await presentPaymentSheet();
        if (presentError) throw new Error(presentError.message);

        return { success: true, transactionId: 'stripe_success' };
    }

    /**
     * Cashfree Integration
     */
    static async handleCashfree(order: any, gateway: any): Promise<{ success: boolean, transactionId: any }> {
        const sessionId = gateway.temp_session_id; 
        if (!sessionId) throw new Error('Cashfree session ID missing.');

        try {
            const session = new Cashfree.CFSession(sessionId, order.order_number, gateway.is_test_mode ? 'SANDBOX' : 'PRODUCTION');
            const theme = new Cashfree.CFThemeBuilder().setPrimaryColor('#7c3aed').setSecondaryColor('#ffffff').build();
            const paymentComponent = new Cashfree.CFPaymentComponentBuilder().add(Cashfree.CFPaymentComponentBuilder.CFPaymentModes.CARD).build();
            // Modern Cashfree SDKs often use a configuration object
            const result = await Cashfree.CFPaymentGatewayService.doPayment(session, paymentComponent, theme);
            return { success: true, transactionId: result };
        } catch (error: any) {
            throw new Error(error.message || 'Cashfree payment failed');
        }
    }

    /**
     * PhonePe Integration
     */
    static async handlePhonePe(order: any, gateway: any): Promise<{ success: boolean, transactionId: any }> {
        const creds = gateway.credentials;
        const merchantId = creds.merchant_id || creds.merchantId;
        const environment = gateway.is_test_mode ? 'SANDBOX' : 'PRODUCTION';
        
        // Checksum MUST be generated on your backend for security.
        // For the purpose of this implementation, we assume the backend provides the checksum
        // and the base64-encoded body in the gateway object or a preceding API call.
        const checksum = gateway.temp_checksum;
        const base64Body = gateway.temp_base64_body;
        const appId = Platform.OS === 'android' ? 'com.menhaboutique.mobile' : null;

        if (!checksum || !base64Body) {
            throw new Error('PhonePe checksum or payload missing. Backend integration required.');
        }

        try {
            // Initialize SDK
            await PhonePe.init(environment, merchantId, "FLOW_ID_MENHA", false);
            
            // Start Transaction
            const result = await PhonePe.startTransaction(base64Body, checksum, appId, null);
            
            if (result.status === 'SUCCESS') {
                return { success: true, transactionId: result.transactionId || 'phonepe_success' };
            } else {
                throw new Error(result.error || 'PhonePe transaction failed');
            }
        } catch (error: any) {
            console.error('PhonePe Error:', error);
            throw new Error(error.message || 'PhonePe payment failed');
        }
    }

    /**
     * Main Entry Point
     */
    static async processPayment(order: any, gateway: any): Promise<{ success: boolean, transactionId: any }> {
        const type = gateway.type?.toLowerCase();
        
        switch (type) {
            case 'razorpay':
                return await this.handleRazorpay(order, gateway);
            case 'stripe':
                return await this.handleStripe(order, gateway);
            case 'cashfree':
                return await this.handleCashfree(order, gateway);
            case 'phonepe':
                return await this.handlePhonePe(order, gateway);
            default:
                // Fallback to simulation if SDK not available or type not matched
                console.log(`No native handler for ${type}, using simulation.`);
                await new Promise(resolve => setTimeout(resolve, 2000));
                return { success: true, transactionId: 'simulated_txn_' + Date.now() };
        }
    }
}

export default PaymentGatewayService;
