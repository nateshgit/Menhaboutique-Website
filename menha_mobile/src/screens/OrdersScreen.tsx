import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, StyleSheet, SafeAreaView, StatusBar, TouchableOpacity } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import api, { MainAPI } from '../services/api';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';
import Loader from '../components/Loader';

const OrdersScreen = () => {
  const navigation = useNavigation<any>();
  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
        const orders = await MainAPI.getOrders();
        setOrders(orders);
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

  const renderItem = ({ item }: { item: any }) => (
    <TouchableOpacity 
        style={styles.card}
        onPress={() => navigation.navigate('OrderDetail', { orderId: item.id, orderData: item })}
        activeOpacity={0.9}
    >
      <View style={styles.header}>
          <View style={styles.orderIdContainer}>
              <Ionicons name="cube-outline" size={18} color="#333" />
              <Text style={styles.orderId}>Order #{item.order_number || item.id}</Text>
          </View>
          <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
            <Text style={[styles.status, { color: getStatusColor(item.status) }]}>{item.status}</Text>
          </View>
      </View>
      
      <View style={styles.divider} />
      
      {item.addresses && (
        <View style={{ marginBottom: 12 }}>
            <Text style={styles.label}>Shipping Address</Text>
            <Text style={styles.addressValue} numberOfLines={2}>
                {item.addresses.address_line1 || item.addresses.address_line}, {item.addresses.city}, {item.addresses.state} {item.addresses.zip_code}
            </Text>
        </View>
      )}
      
      <View style={styles.detailsRow}>
          <View>
              <Text style={styles.label}>Date</Text>
              <Text style={styles.value}>{new Date(item.created_at || Date.now()).toLocaleDateString()}</Text>
          </View>
          <View style={{alignItems: 'flex-end'}}>
              <Text style={styles.label}>Total Amount</Text>
              <Text style={styles.totalPrice}>₹{item.total_price}</Text>
          </View>
      </View>
      
      <View style={styles.footer}>
          <Text style={styles.viewDetails}>View Details</Text>
          <Ionicons name="chevron-forward" size={16} color={COLORS.primary} />
      </View>
    </TouchableOpacity>
  );

  if (loading) return <Loader fullScreen />;

  if (orders.length === 0) {
      return (
          <View style={styles.center}>
              <Ionicons name="receipt-outline" size={60} color="#ccc" style={{marginBottom: 10}} />
              <Text style={styles.emptyText}>No orders found</Text>
          </View>
      );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#f8f9fa" />
      <FlatList
        data={orders}
        renderItem={renderItem}
        contentContainerStyle={{padding: 15}}
        keyExtractor={(item) => (item.id || Math.random()).toString()}
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa',
  },
  center: {flex:1, justifyContent:'center', alignItems:'center'},
  emptyText: { fontSize:16, color:'#888', fontWeight: '500'},
  card: {
      backgroundColor: '#fff',
      borderRadius: 12,
      marginBottom: 15,
      padding: 15,
      elevation: 2,
      shadowColor: '#000',
      shadowOffset: { width:0, height: 1},
      shadowOpacity: 0.05,
      shadowRadius: 3,
      borderWidth: 1,
      borderColor: '#f0f0f0',
  },
  header: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
  },
  orderIdContainer: {
      flexDirection: 'row',
      alignItems: 'center',
  },
  orderId: {
      fontWeight: '700',
      fontSize: 16,
      marginLeft: 8,
      color: '#333',
  },
  statusBadge: {
      paddingHorizontal: 10,
      paddingVertical: 4,
      borderRadius: 12,
  },
  status: {
      fontWeight: '600',
      fontSize: 12,
      textTransform: 'capitalize',
  },
  divider: {
      height: 1,
      backgroundColor: '#f0f0f0',
      marginVertical: 12,
  },
  detailsRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
  },
  label: {
      fontSize: 12,
      color: '#888',
      marginBottom: 4,
  },
  value: {
      fontSize: 14,
      color: '#333',
      fontWeight: '500',
  },
  addressValue: {
      fontSize: 13,
      color: '#444',
      fontWeight: '400',
      lineHeight: 18,
  },
  totalPrice: {
      fontSize: 18,
      fontWeight: '700',
      color: COLORS.primary, // Brand deep green
  },
  footer: {
      marginTop: 15,
      paddingTop: 15,
      borderTopWidth: 1,
      borderTopColor: '#f0f0f0',
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
  },
  viewDetails: {
      fontSize: 12,
      fontWeight: '600',
      color: COLORS.primary,
  }
});

export default OrdersScreen;
