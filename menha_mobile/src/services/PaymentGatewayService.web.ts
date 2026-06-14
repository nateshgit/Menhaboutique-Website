export default class PaymentGatewayService {
    static async processPayment(order: any, gateway: any): Promise<{ success: boolean, transactionId: any }> {
        console.log(`[Web Mock] Simulated payment for ${gateway.type}`);
        await new Promise(resolve => setTimeout(resolve, 2000));
        return { success: true, transactionId: 'simulated_txn_' + Date.now() };
    }
}
