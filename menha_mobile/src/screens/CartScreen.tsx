import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, StyleSheet, Alert, SafeAreaView, StatusBar, Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import api, { MainAPI } from '../services/api';
import { resolveImageUrl } from '../utils/imageUtils';
import CachedImage from '../components/CachedImage';
import { COLORS, THEME } from '../constants/theme';
import Loader from '../components/Loader';
import SectionHeader from '../components/SectionHeader';

import { useCart } from '../context/CartContext';

const CartScreen = () => {
  const navigation = useNavigation<any>();
  const [cartItems, setCartItems] = useState<any[]>([]);
  const [relatedProducts, setRelatedProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const { updateCartCount } = useCart();

  useEffect(() => {
    // Refresh cart when focusing screen
    const unsubscribe = navigation.addListener('focus', () => {
      fetchCart();
    });
    return unsubscribe;
  }, [navigation]);

  const fetchCart = async () => {
      setLoading(true);
      try {
           const cartJson = await AsyncStorage.getItem('mb_cart');
           let parsedCart: any[] = [];
           if (cartJson) {
               parsedCart = JSON.parse(cartJson);
               setCartItems(parsedCart);
           } else {
               setCartItems([]);
           }
           await updateCartCount();
           await fetchRelatedProducts(parsedCart);
      } catch (error) {
          console.error(error);
      } finally {
          setLoading(false);
      }
  };

  const fetchRelatedProducts = async (currentCartItems: any[]) => {
      try {
          const products = await MainAPI.fetchProducts();
          const cartProductIds = currentCartItems.map(item => (item.product?.id || item.id));
          // Filter out products currently in cart
          const filtered = products.filter((p: any) => !cartProductIds.includes(p.id));
          setRelatedProducts(filtered.slice(0, 6)); // Recommend top 6 items
      } catch (error) {
          console.error("Error fetching related products:", error);
      }
  };

  const updateQuantity = async (id: string, newQuantity: number) => {
      if (newQuantity < 1) return;
      try {
          const updatedCart = cartItems.map(item => 
              (item.product?.id === id || item.id === id) ? { ...item, quantity: newQuantity } : item
          );
          setCartItems(updatedCart);
          await AsyncStorage.setItem('mb_cart', JSON.stringify(updatedCart));
          await updateCartCount();
      } catch (error) {
          console.error(error);
          Alert.alert('Error', 'Could not update quantity');
      }
  };

  const removeItem = async (id: string) => {
      try {
          const updatedCart = cartItems.filter(item => (item.product?.id !== id && item.id !== id));
          setCartItems(updatedCart);
          await AsyncStorage.setItem('mb_cart', JSON.stringify(updatedCart));
          await updateCartCount();
      } catch (error) {
           console.error(error);
           Alert.alert('Error', 'Could not remove item');
      }
  };

  const calculateTotal = () => {
      return cartItems.reduce((sum, item) => {
          const p = item.product || item;
          const price = p.new_price || p.newPrice || p.price || 0;
          return sum + (price * item.quantity);
      }, 0);
  };

  const handleCheckout = async () => {
      const token = await AsyncStorage.getItem('auth_token');
      if (!token) {
          if (Platform.OS === 'web') {
              alert('Please login to place your order.');
              navigation.navigate('Login', { redirect: 'Checkout', cartItems, totalAmount: calculateTotal() });
          } else {
              Alert.alert('Login Required', 'Please login to place your order.', [
                  { text: 'Cancel', style: 'cancel' },
                  { text: 'Login', onPress: () => navigation.navigate('Login', { redirect: 'Checkout', cartItems, totalAmount: calculateTotal() }) }
              ]);
          }
      } else {
          navigation.navigate('Checkout', { cartItems, totalAmount: calculateTotal() });
      }
  };

  const renderItem = ({ item }: { item: any }) => {
    const p = item.product || item;
    const rawImageUrl = 
      p.primary_image || 
      p.primaryImage || 
      (p.images && p.images.length > 0 ? (p.images[0].image_url || p.images[0].imageUrl || p.images[0].url) : null) || 
      p.image;

    const imageUrl = resolveImageUrl(rawImageUrl, 150);

    const displayPrice = p.new_price || p.newPrice || p.price;

    return (
      <View style={styles.card}>
        <View style={styles.imageContainer}>
          <CachedImage 
            source={{ uri: imageUrl }} 
            style={styles.image as any} 
            containerStyle={styles.imageContainer} 
            resizeMode="cover" 
          />
        </View>
        <View style={styles.details}>
          <View style={styles.infoRow}>
            <Text style={styles.name} numberOfLines={2}>{p.name || p.title}</Text>
            <TouchableOpacity onPress={() => removeItem(item.id || p.id)} style={styles.deleteBtn}>
                <Ionicons name="trash-outline" size={20} color={COLORS.danger} />
            </TouchableOpacity>
          </View>
          
          <Text style={styles.category}>{p.categories?.name || p.category?.name || p.category_name || 'Snacks'}</Text>
          <Text style={styles.price}>₹{displayPrice}</Text>

          <View style={styles.counterContainer}>
               <TouchableOpacity onPress={() => updateQuantity(item.id, item.quantity - 1)} style={[styles.counterBtn, styles.counterBtnLeft]}>
                   <Ionicons name="remove" size={16} color="#333" />
               </TouchableOpacity>
               <View style={styles.quantityBox}>
                   <Text style={styles.quantity}>{item.quantity}</Text>
               </View>
               <TouchableOpacity onPress={() => updateQuantity(item.id, item.quantity + 1)} style={[styles.counterBtn, styles.counterBtnRight]}>
                   <Ionicons name="add" size={16} color="#333" />
               </TouchableOpacity>
          </View>
        </View>
      </View>
    );
  };

  const handleAddRelatedToCart = async (item: any) => {
      try {
          const cartJson = await AsyncStorage.getItem('mb_cart') || '[]';
          let cart = JSON.parse(cartJson);
          const existing = cart.find((i: any) => (i.product?.id === item.id || i.id === item.id));
          if (existing) {
              existing.quantity += 1;
          } else {
              cart.push({ id: item.id, product: item, quantity: 1 });
          }
          await AsyncStorage.setItem('mb_cart', JSON.stringify(cart));
          await updateCartCount();
          await fetchCart(); // Refresh cart list
          Alert.alert('Success', 'Added to cart!');
      } catch (error) {
          console.error(error);
          Alert.alert('Error', 'Failed to add to cart');
      }
  };

  const renderRelatedItem = ({ item }: { item: any }) => {
      const rawImageUrl = 
        item.primary_image || 
        item.primaryImage || 
        (item.images && item.images.length > 0 ? (item.images[0].image_url || item.images[0].imageUrl || item.images[0].url) : null) || 
        item.image;

      const imageUrl = resolveImageUrl(rawImageUrl, 150);
      const displayPrice = item.new_price || item.newPrice || item.price;
      const displayOldPrice = item.old_price || item.oldPrice;

      return (
          <TouchableOpacity 
              style={styles.relatedCard} 
              onPress={() => navigation.navigate('ProductDetail', { productId: item.id })}
          >
              <CachedImage 
                source={{ uri: imageUrl }} 
                style={styles.relatedImage as any} 
                containerStyle={styles.relatedImageContainer} 
                resizeMode="cover" 
              />
              <View style={styles.relatedDetails}>
                  <Text style={styles.relatedName} numberOfLines={1}>{item.name || item.title}</Text>
                  <Text style={styles.relatedCategory}>{item.categories?.name || item.category_name || 'Snacks'}</Text>
                  <View style={styles.relatedPriceRow}>
                      <Text style={styles.relatedPrice}>₹{displayPrice}</Text>
                      {displayOldPrice && displayOldPrice > displayPrice ? (
                          <Text style={styles.relatedOldPrice}>₹{displayOldPrice}</Text>
                      ) : null}
                  </View>
                  <TouchableOpacity 
                      style={styles.relatedAddBtn} 
                      onPress={() => handleAddRelatedToCart(item)}
                  >
                      <Text style={styles.relatedAddText}>Add to Cart</Text>
                  </TouchableOpacity>
              </View>
          </TouchableOpacity>
      );
  };

  const renderRelatedSection = () => {
      if (relatedProducts.length === 0) return null;

      return (
          <View style={styles.relatedSection}>
              <SectionHeader title="You May Also Like" />
              <FlatList
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  data={relatedProducts}
                  keyExtractor={(item) => item.id.toString()}
                  renderItem={renderRelatedItem}
                  contentContainerStyle={styles.relatedList}
              />
          </View>
      );
  };

  const renderEmptyCart = () => (
      <View style={styles.emptyContainer}>
          <View style={styles.emptyIconContainer}>
            <Ionicons name="cart-outline" size={80} color="#ddd" />
          </View>
          <Text style={styles.emptyText}>Your cart is empty!</Text>
          <Text style={styles.emptySubText}>Looks like you haven't added anything yet.</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Home')} style={{marginTop: 20}}>
               <LinearGradient
                colors={THEME.gradients.primary as any}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.shopNowBtn}
               >
                  <Text style={styles.shopNowText}>Shop Now</Text>
               </LinearGradient>
          </TouchableOpacity>
      </View>
  );

  if (loading) {
    return <Loader fullScreen />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#f5f5f5" />
      <View style={styles.header}>
          <Text style={styles.headerTitle}>My Cart ({cartItems.length})</Text>
      </View>
      
      <FlatList
        data={cartItems}
        renderItem={renderItem}
        keyExtractor={(item, index) => (item.id || item.product?.id || index).toString()}
        contentContainerStyle={[styles.list, cartItems.length === 0 && { flexGrow: 1, justifyContent: 'center' }]}
        showsVerticalScrollIndicator={false}
        ListEmptyComponent={renderEmptyCart}
        ListFooterComponent={renderRelatedSection}
      />
      
      {cartItems.length > 0 ? (
        <View style={styles.footer}>
            <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Total Amount</Text>
                <Text style={styles.totalValue}>₹{calculateTotal()}</Text>
            </View>
            
            <TouchableOpacity onPress={handleCheckout}>
                <LinearGradient
                  colors={THEME.gradients.primary as any}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                  style={styles.checkoutBtn}
                >
                    <Text style={styles.checkoutText}>Proceed to Checkout</Text>
                    <Ionicons name="arrow-forward" size={18} color="#fff" />
                </LinearGradient>
            </TouchableOpacity>
        </View>
      ) : null}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
    padding: 20,
  },
  header: {
      padding: 15,
      backgroundColor: '#fff',
      borderBottomWidth: 1,
      borderBottomColor: '#eee',
  },
  headerTitle: {
      fontSize: 18,
      fontWeight: '700',
      color: '#333',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
    padding: 20,
    paddingVertical: 50,
  },
  emptyIconContainer: {
      marginBottom: 20,
      width: 150,
      height: 150,
      borderRadius: 75,
      backgroundColor: '#f9f9f9',
      justifyContent: 'center',
      alignItems: 'center',
  },
  emptyText: {
      fontSize: 22,
      fontWeight: 'bold',
      color: '#333',
      marginBottom: 5,
  },
  emptySubText: {
      fontSize: 14,
      color: '#888',
      textAlign: 'center',
  },
  shopNowBtn: {
      paddingHorizontal: 30,
      paddingVertical: 12,
      borderRadius: 25,
      elevation: 2,
  },
  shopNowText: {
      color: '#fff',
      fontWeight: 'bold',
      fontSize: 16,
  },
  list: {
    padding: 15,
    paddingBottom: 120,
  },
  card: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    marginBottom: 15,
    borderRadius: 12,
    padding: 10,
    elevation: 2,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
  },
  imageContainer: {
      width: 90,
      height: 90,
      borderRadius: 8,
      backgroundColor: '#f9f9f9',
      overflow: 'hidden',
  },
  image: {
    width: '100%',
    height: '100%',
  },
  details: {
    flex: 1,
    marginLeft: 15,
    justifyContent: 'space-between',
    paddingVertical: 2,
  },
  infoRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
  },
  name: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    flex: 1,
    marginRight: 10,
    lineHeight: 20,
  },
  deleteBtn: {
      padding: 5,
  },
  category: {
      fontSize: 12,
      color: '#888',
      marginTop: 2,
  },
  price: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.primary, // Deep green
    marginTop: 5,
  },
  counterContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      marginTop: 8,
      alignSelf: 'flex-start',
      backgroundColor: '#f5f5f5',
      borderRadius: 20,
  },
  counterBtn: {
      paddingHorizontal: 10,
      paddingVertical: 6,
      justifyContent: 'center',
      alignItems: 'center',
  },
  counterBtnLeft: {
      borderTopLeftRadius: 20,
      borderBottomLeftRadius: 20,
      paddingLeft: 12,
  },
  counterBtnRight: {
      borderTopRightRadius: 20,
      borderBottomRightRadius: 20,
      paddingRight: 12,
  },
  quantityBox: {
      paddingHorizontal: 10,
      borderLeftWidth: 1,
      borderRightWidth: 1,
      borderColor: '#e0e0e0',
  },
  quantity: {
      fontSize: 14,
      fontWeight: 'bold',
      color: '#333',
  },
  footer: {
      position: 'absolute',
      bottom: 0,
      left: 0,
      right: 0,
      backgroundColor: '#fff',
      padding: 20,
      borderTopWidth: 1,
      borderTopColor: '#f0f0f0',
      elevation: 10,
      shadowColor: "#000",
      shadowOffset: { width: 0, height: -3 },
      shadowOpacity: 0.1,
      shadowRadius: 5,
      borderTopLeftRadius: 20,
      borderTopRightRadius: 20,
  },
  totalRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: 20,
  },
  totalLabel: {
      fontSize: 16,
      color: '#666',
      fontWeight: '500',
  },
  totalValue: {
      fontSize: 22,
      fontWeight: '700',
      color: COLORS.primary,
  },
  checkoutBtn: {
      paddingVertical: 15,
      borderRadius: 30,
      flexDirection: 'row',
      justifyContent: 'center',
      alignItems: 'center',
  },
  checkoutText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '700',
      marginRight: 8,
      letterSpacing: 0.5,
  },
  relatedSection: {
    marginTop: 20,
    marginBottom: 40,
    paddingBottom: 20,
  },
  relatedList: {
    paddingHorizontal: 5,
    paddingVertical: 10,
  },
  relatedCard: {
    width: 160,
    backgroundColor: '#fff',
    marginRight: 15,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#f0f0f0',
    overflow: 'hidden',
  },
  relatedImageContainer: {
    width: 158,
    height: 120,
    overflow: 'hidden',
  },
  relatedImage: {
    width: '100%',
    height: 120,
  },
  relatedDetails: {
    padding: 10,
  },
  relatedName: {
    fontSize: 13,
    fontWeight: '600',
    color: '#333',
    marginBottom: 2,
  },
  relatedCategory: {
    fontSize: 10,
    color: '#888',
    marginBottom: 6,
    textTransform: 'uppercase',
  },
  relatedPriceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  relatedPrice: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.primary,
    marginRight: 6,
  },
  relatedOldPrice: {
    fontSize: 11,
    color: '#999',
    textDecorationLine: 'line-through',
  },
  relatedAddBtn: {
    backgroundColor: COLORS.primary,
    paddingVertical: 6,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  relatedAddText: {
    color: '#fff',
    fontSize: 11,
    fontWeight: '600',
  },
});

export default CartScreen;
