import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, Image, SafeAreaView, StatusBar } from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import api from '../services/api';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';
import { resolveImageUrl } from '../utils/imageUtils';
import Loader from '../components/Loader';

const OrderDetailScreen = () => {
  const route = useRoute<any>();
  const navigation = useNavigation();
  const { orderId, orderData } = route.params || {};
  
  const [order, setOrder] = useState<any>(orderData || null);
  const [loading, setLoading] = useState(!orderData);

  useEffect(() => {
    if (!order && orderId) {
      fetchOrderDetails();
    }
  }, [orderId]);

  const fetchOrderDetails = async () => {
    try {
      const response = await api.get(`/orders?select=*,order_items(*,products(*))&id=eq.${orderId}`);
      if (response.data.length) {
          setOrder(response.data[0]);
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch(status?.toLowerCase()) {
        case 'delivered': return COLORS.success;
        case 'cancelled': return COLORS.danger;
        case 'processing': return COLORS.warning;
        default: return COLORS.info;
    }
  };

  if (loading) {
    return <Loader fullScreen />;
  }

  if (!order) {
    return (
      <View style={styles.center}>
        <Text>Order not found</Text>
      </View>
    );
  }

  const items = order.items || order.order_items || [];

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      <ScrollView contentContainerStyle={styles.content}>
        
        {/* Order Header */}
        <View style={styles.section}>
            <View style={styles.headerRow}>
                <View>
                    <Text style={styles.orderIdLabel}>Order No</Text>
                    <Text style={styles.orderId}>#{order.order_number || order.id}</Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: getStatusColor(order.status) + '20' }]}>
                    <Text style={[styles.statusText, { color: getStatusColor(order.status) }]}>{order.status}</Text>
                </View>
            </View>
            <Text style={styles.date}>{new Date(order.created_at).toLocaleString()}</Text>
        </View>

        {/* Shipping Address */}
        {(order.addresses || order.address) && (
            <View style={styles.section}>
                <Text style={styles.sectionTitle}>Shipping Address</Text>
                <View style={styles.addressBox}>
                    <Ionicons name="location-outline" size={20} color={COLORS.primary} style={{marginRight: 10}} />
                    <View style={{flex:1}}>
                        {order.addresses ? (
                            <>
                                <Text style={styles.addressText}>{order.addresses.first_name} {order.addresses.last_name}</Text>
                                <Text style={styles.addressText}>{order.addresses.address_line1 || order.addresses.address_line}</Text>
                                <Text style={styles.addressText}>{order.addresses.city}, {order.addresses.state} {order.addresses.zip_code}</Text>
                            </>
                        ) : (
                            <Text style={styles.addressText}>{order.address.line1 || order.address}</Text>
                        )}
                    </View>
                </View>
            </View>
        )}

        {/* Order Items */}
        <View style={styles.section}>
            <Text style={styles.sectionTitle}>Items ({items.length})</Text>
            {items.map((item: any, index: number) => {
                const p = item.products || item.product || {};
                const rawImageUrl = p.primary_image || p.image || item.image;
                const imageUrl = resolveImageUrl(rawImageUrl, 150);
                
                return (
                    <View key={index} style={styles.itemRow}>
                        <Image source={{ uri: imageUrl }} style={styles.itemImage} />
                        <View style={styles.itemDetails}>
                            <Text style={styles.itemName} numberOfLines={2}>{item.product?.name || item.name || item.product_name}</Text>
                            <Text style={styles.itemVariant}>{item.variant || 'Standard'}</Text>
                            <View style={styles.widthRow}>
                                <Text style={styles.itemQty}>Qty: {item.quantity}</Text>
                                <Text style={styles.itemPrice}>
                                    ₹{item.total_price || (item.unit_price * item.quantity)}
                                </Text>
                            </View>
                        </View>
                    </View>
                );
            })}
        </View>

        {/* Payment Summary */}
        <View style={styles.section}>
            <Text style={styles.sectionTitle}>Order Summary</Text>
            <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Subtotal</Text>
                <Text style={styles.summaryValue}>₹{order.total_price}</Text>
            </View>
            <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Shipping</Text>
                <Text style={styles.summaryValue}>₹0</Text>
            </View>
            <View style={styles.divider} />
            <View style={styles.summaryRow}>
                <Text style={styles.totalLabel}>Total Amount</Text>
                <Text style={styles.totalValue}>₹{order.total_price}</Text>
            </View>
        </View>

      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    padding: 15,
  },
  section: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 15,
    marginBottom: 15,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
  },
  headerRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      marginBottom: 5,
  },
  orderIdLabel: {
      fontSize: 12,
      color: '#888',
  },
  orderId: {
      fontSize: 16,
      fontWeight: 'bold',
      color: '#333',
  },
  date: {
      fontSize: 12,
      color: '#999',
  },
  statusBadge: {
      paddingHorizontal: 8,
      paddingVertical: 3,
      borderRadius: 8,
  },
  statusText: {
      fontSize: 12,
      fontWeight: '600',
      textTransform: 'capitalize',
  },
  sectionTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: '#333',
      marginBottom: 15,
  },
  addressBox: {
      flexDirection: 'row',
      alignItems: 'flex-start',
  },
  addressText: {
      fontSize: 14,
      color: '#555',
      marginBottom: 2,
  },
  itemRow: {
      flexDirection: 'row',
      marginBottom: 15,
      borderBottomWidth: 1,
      borderBottomColor: '#f9f9f9',
      paddingBottom: 15,
  },
  itemImage: {
      width: 60,
      height: 60,
      borderRadius: 8,
      backgroundColor: '#f5f5f5',
  },
  itemDetails: {
      flex: 1,
      marginLeft: 15,
  },
  itemName: {
      fontSize: 14,
      fontWeight: '600',
      color: '#333',
      marginBottom: 4,
  },
  itemVariant: {
      fontSize: 12,
      color: '#999',
      marginBottom: 4,
  },
  widthRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
  },
  itemQty: {
      fontSize: 13,
      color: '#666',
  },
  itemPrice: {
      fontSize: 14,
      fontWeight: '700',
      color: COLORS.primary,
  },
  summaryRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      marginBottom: 10,
  },
  summaryLabel: {
      color: '#666',
      fontSize: 14,
  },
  summaryValue: {
      color: '#333',
      fontSize: 14,
      fontWeight: '500',
  },
  divider: {
      height: 1,
      backgroundColor: '#eee',
      marginVertical: 10,
  },
  totalLabel: {
      fontSize: 16,
      fontWeight: '700',
      color: '#333',
  },
  totalValue: {
      fontSize: 18,
      fontWeight: '700',
      color: COLORS.primary,
  },
});

export default OrderDetailScreen;
