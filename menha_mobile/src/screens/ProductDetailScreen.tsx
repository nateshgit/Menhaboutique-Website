import React, { useEffect, useState } from 'react';
import { View, Text, Image, ScrollView, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, Dimensions, StatusBar, SafeAreaView, Platform, TextInput, Modal } from 'react-native';
import CachedImage from '../components/CachedImage';
import api, { MainAPI } from '../services/api';
import { useRoute, useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { COLORS, THEME } from '../constants/theme';
import Loader from '../components/Loader';

const { width } = Dimensions.get('window');
const IMAGE_SIZE = Math.min(width, 500);

import { resolveImageUrl } from '../utils/imageUtils';

import { useCart } from '../context/CartContext';
import { useWishlist } from '../context/WishlistContext';

const ProductDetailScreen = () => {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { productId } = route.params || {}; // Safety check
  const [product, setProduct] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [adding, setAdding] = useState(false);
  const [userToken, setUserToken] = useState<string | null>(null);
  const [ratingInput, setRatingInput] = useState(5);
  const [commentInput, setCommentInput] = useState('');
  const [submittingReview, setSubmittingReview] = useState(false);
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [isZoomModalVisible, setIsZoomModalVisible] = useState(false);
  const [zoomScale, setZoomScale] = useState(1);
  const [isDescExpanded, setIsDescExpanded] = useState(false);

  const getZoomedImageUrl = () => {
      if (!product) return '';
      const displayImageUrl = resolveImageUrl(
          product.primary_image || 
          product.primaryImage || 
          (product.images && product.images.length > 0 ? (product.images[0].image_url || product.images[0].imageUrl || product.images[0].url) : null) || 
          product.image,
          1000
      );
      if (activeImageIndex === 0) {
          return displayImageUrl;
      }
      if (product.additional_images && product.additional_images.length > activeImageIndex - 1) {
          const img = product.additional_images[activeImageIndex - 1];
          return resolveImageUrl(img.image_url || img.imageUrl, 1000);
      }
      return displayImageUrl;
  };

  const handleScroll = (event: any) => {
    const slideSize = event.nativeEvent.layoutMeasurement.width;
    const index = Math.round(event.nativeEvent.contentOffset.x / slideSize);
    setActiveImageIndex(index);
  };

  const { updateCartCount } = useCart();
  const { toggleWishlist, isInWishlist } = useWishlist();

  useEffect(() => {
    checkUser();
    if (productId) {
        fetchProductDetails();
    }
  }, [productId]);
  
  const checkUser = async () => {
      const token = await AsyncStorage.getItem('auth_token');
      setUserToken(token);
  };
  
  const fetchProductDetails = async () => {
    try {
      const productData = await MainAPI.fetchProductById(productId);
      setProduct(productData);
      
      // Optimize image loading by prefetching in the background
      if (productData) {
          const rawPrimary = productData.primary_image || productData.primaryImage || productData.image;
          if (rawPrimary) {
              Image.prefetch(resolveImageUrl(rawPrimary, 600)).catch(() => {});
          }
          if (Array.isArray(productData.additional_images)) {
              productData.additional_images.forEach((img: any) => {
                  const url = resolveImageUrl(img.image_url || img.imageUrl, 600);
                  if (url && url.startsWith('http')) {
                      Image.prefetch(url).catch(() => {});
                  }
              });
          }
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const addToCart = async (goToCart = false) => {
    setAdding(true);
    try {
        const cartJson = await AsyncStorage.getItem('mb_cart') || '[]';
        let cart = JSON.parse(cartJson);
        const existing = cart.find((item: any) => item.product.id === product.id);
        if (existing) {
            existing.quantity += quantity;
        } else {
            cart.push({ product, quantity });
        }
        await AsyncStorage.setItem('mb_cart', JSON.stringify(cart));
        await updateCartCount();

        if (goToCart) {
            navigation.navigate('Cart');
        } else {
            Alert.alert('Success', 'Added to cart!');
        }

    } catch (error: any) {
        console.error('Add to cart error:', error);
        Alert.alert('Error', 'Failed to add to cart');
    } finally {
        setAdding(false);
    }
  };

  const handleBuyNow = async () => {
      const displayPrice = product.new_price || product.newPrice || product.price || 0;
      const totalAmount = displayPrice * quantity;
      const token = await AsyncStorage.getItem('auth_token');
      const cartItems = [{ product, quantity }];
      if (!token) {
          if (Platform.OS === 'web') {
              alert('Please login to place your order.');
              navigation.navigate('Login', { redirect: 'Checkout', cartItems, totalAmount });
          } else {
              Alert.alert('Login Required', 'Please login to place your order.', [
                  { text: 'Cancel', style: 'cancel' },
                  { text: 'Login', onPress: () => navigation.navigate('Login', { redirect: 'Checkout', cartItems, totalAmount }) }
              ]);
          }
      } else {
          navigation.navigate('Checkout', { cartItems, totalAmount });
      }
  };



  const increment = () => setQuantity(prev => prev + 1);
  const decrement = () => setQuantity(prev => (prev > 1 ? prev - 1 : 1));

  const handleReviewSubmit = async () => {
      if (!commentInput.trim()) {
          Alert.alert('Error', 'Please enter a review comment');
          return;
      }
      setSubmittingReview(true);
      try {
          await MainAPI.submitReview({
              product_id: product.id,
              rating: ratingInput,
              comment: commentInput
          });
          Alert.alert('Success', 'Review submitted successfully!');
          setCommentInput('');
          setRatingInput(5);
          fetchProductDetails(); // Refresh product to show new review
      } catch (error) {
          Alert.alert('Error', 'Could not submit review. Please try again.');
      } finally {
          setSubmittingReview(false);
      }
  };

  if (loading) {
      return <Loader fullScreen />;
  }

  if (!product) {
    return <View style={styles.center}><Text>Product not found</Text></View>;
  }

  // Handle Image Resolution
  const rawImageUrl = 
    product.primary_image || 
    product.primaryImage || 
    (product.images && product.images.length > 0 ? (product.images[0].image_url || product.images[0].imageUrl || product.images[0].url) : null) || 
    product.image;

  const imageUrl = resolveImageUrl(rawImageUrl, 600);

  const displayPrice = product.new_price || product.newPrice || product.price;
  const displayOldPrice = product.old_price || product.oldPrice;

  const discount = displayOldPrice ? Math.round(((displayOldPrice - displayPrice) / displayOldPrice) * 100) : 0;
  const rating = product.rating || 4.8;
  const reviewCount = product.reviews ? product.reviews.length : (product.reviewCount || 120);

  let stockStatus = product.status;
  if (!stockStatus || stockStatus === 'In Stock' || stockStatus === 'Out of Stock') {
      stockStatus = parseInt(product.stock_quantity || 0) > 0 ? 'In Stock' : 'Out of Stock';
  }
  const isOutOfStock = stockStatus === 'Out of Stock';
  const canAdd = !isOutOfStock;

  const descriptionText = product.description || 'Experience the authentic taste of tradition with our premium quality products made from natural ingredients.';
  const shouldShowToggle = descriptionText.length > 150;
  const displayDescription = shouldShowToggle && !isDescExpanded 
    ? descriptionText.substring(0, 150) + '...' 
    : descriptionText;

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.headerOverlay}>
            <View />
            <View style={{flexDirection: 'row', alignItems: 'center'}}>
                <TouchableOpacity 
                    style={[styles.iconBtn, {marginRight: 10}]} 
                    onPress={() => toggleWishlist(product)}
                    activeOpacity={0.8}
                >
                    <Ionicons 
                        name={isInWishlist(product.id) ? "heart" : "heart-outline"} 
                        size={24} 
                        color={isInWishlist(product.id) ? COLORS.danger || '#ff4d4f' : '#333'} 
                    />
                </TouchableOpacity>
                <TouchableOpacity style={styles.iconBtn} onPress={() => navigation.navigate('Cart')}>
                    <Ionicons name="cart-outline" size={24} color="#333" />
                </TouchableOpacity>
            </View>
        </View>

        <View style={styles.imageWrapper}>
             <ScrollView 
                 horizontal 
                 pagingEnabled 
                 showsHorizontalScrollIndicator={false}
                 onScroll={handleScroll}
                 scrollEventThrottle={16}
             >
                <TouchableOpacity activeOpacity={0.9} onPress={() => setIsZoomModalVisible(true)}>
                    <CachedImage source={{ uri: imageUrl }} style={[styles.image, { width: IMAGE_SIZE, height: IMAGE_SIZE }] as any} containerStyle={{ width: IMAGE_SIZE, height: IMAGE_SIZE }} resizeMode="contain" />
                </TouchableOpacity>
                {Array.isArray(product.additional_images) ? product.additional_images.map((img: any, idx: number) => (
                    <TouchableOpacity key={`img-btn-${idx}`} activeOpacity={0.9} onPress={() => setIsZoomModalVisible(true)}>
                        <CachedImage source={{ uri: resolveImageUrl(img.image_url || img.imageUrl, 600) }} style={[styles.image, { width: IMAGE_SIZE, height: IMAGE_SIZE }] as any} containerStyle={{ width: IMAGE_SIZE, height: IMAGE_SIZE }} resizeMode="contain" />
                    </TouchableOpacity>
                )) : null}
             </ScrollView>
             {Array.isArray(product.additional_images) && product.additional_images.length > 0 ? (
                 <View style={styles.pagination}>
                     <View style={[styles.dot, activeImageIndex === 0 && styles.activeDot]} />
                     {product.additional_images.map((_: any, idx: number) => (
                         <View key={`dot-${idx}`} style={[styles.dot, activeImageIndex === idx + 1 && styles.activeDot]} />
                     ))}
                 </View>
             ) : null}

             {discount > 0 ? (
                 <View style={styles.discountBadge}>
                     <Text style={styles.discountText}>{discount}% OFF</Text>
                 </View>
             ) : null}
        </View>
        
        <View style={styles.content}>
          <Text style={styles.title}>{product.name || product.title}</Text>
          
          <View style={styles.metaRow}><View style={styles.ratingBox}><Text style={styles.ratingText}>{rating} ★</Text><Text style={styles.reviewText}> | {product.reviews?.length || 0} reviews</Text></View><View style={[styles.stockStatus, isOutOfStock && { backgroundColor: 'rgba(229,57,53,0.1)' }]}><Text style={[styles.inStock, isOutOfStock && { color: COLORS.danger }]}>{stockStatus}</Text></View></View>
          <View style={styles.priceContainer}><Text style={styles.price}>₹{displayPrice}</Text>{displayOldPrice ? <Text style={styles.oldPrice}>₹{displayOldPrice}</Text> : null}<Text style={styles.taxText}>(Incl. of all taxes)</Text></View>

          <View style={styles.divider} />
          <View style={styles.quantitySection}>
              <Text style={styles.sectionTitle}>Quantity</Text>
              <View style={styles.counter}>
                  <TouchableOpacity onPress={decrement} style={styles.counterBtn}>
                      <Ionicons name="remove" size={18} color="#333" />
                  </TouchableOpacity>
                  <Text style={styles.quantityValue}>{quantity}</Text>
                  <TouchableOpacity onPress={increment} style={styles.counterBtn}>
                      <Ionicons name="add" size={18} color="#333" />
                  </TouchableOpacity>
              </View>
          </View>
          <View style={styles.divider} />

          <Text style={styles.descriptionHead}>Product Description</Text>
          <Text style={styles.description}>{displayDescription}</Text>
          {shouldShowToggle && (
            <TouchableOpacity onPress={() => setIsDescExpanded(!isDescExpanded)} style={styles.showMoreBtn}>
              <Text style={styles.showMoreText}>
                {isDescExpanded ? 'Show Less' : 'Show More'}
              </Text>
              <Ionicons 
                name={isDescExpanded ? 'chevron-up' : 'chevron-down'} 
                size={14} 
                color={COLORS.primary} 
                style={{ marginLeft: 4 }} 
              />
            </TouchableOpacity>
          )}
          
           {/* Additional Info / Delivery Placeholder */}
           <View style={styles.deliveryInfo}>
               <Ionicons name="cube-outline" size={20} color="#666" style={{marginRight:8}} />
               <Text style={styles.deliveryText}>Standard Delivery: 2-3 Days</Text>
           </View>

           <View style={styles.divider} />
           <Text style={styles.descriptionHead}>Customer Reviews</Text>
           
           {Boolean(userToken) && userToken !== '' ? (
               <View style={styles.reviewForm}>
                   <Text style={styles.reviewFormTitle}>Write a Review</Text>
                   <View style={styles.starSelector}>
                       {[1, 2, 3, 4, 5].map((star) => (
                           <TouchableOpacity key={star} onPress={() => setRatingInput(star)} style={{padding: 5}}>
                               <Ionicons 
                                   name={star <= ratingInput ? "star" : "star-outline"} 
                                   size={28} 
                                   color={COLORS.warning} 
                               />
                           </TouchableOpacity>
                       ))}
                   </View>
                   <TextInput 
                       style={styles.reviewInput}
                       placeholder="What did you think about this product?"
                       multiline
                       numberOfLines={3}
                       value={commentInput}
                       onChangeText={setCommentInput}
                   />
                   <TouchableOpacity 
                       style={[styles.submitReviewBtn, submittingReview && {opacity: 0.7}]} 
                       onPress={handleReviewSubmit}
                       disabled={submittingReview}
                   >
                       {submittingReview ? (
                           <ActivityIndicator color="#fff" size="small" />
                       ) : (
                           <Text style={styles.submitReviewText}>Submit Review</Text>
                       )}
                   </TouchableOpacity>
               </View>
           ) : null}

           {product.reviews && product.reviews.length > 0 ? (
               <>
                {product.reviews.map((rev: any, idx: number) => (
                    <View key={idx} style={styles.reviewCard}>
                        <View style={styles.reviewHeader}>
                            <Text style={styles.reviewerName}>{rev.users?.first_name} {rev.users?.last_name}</Text>
                            <Text style={styles.reviewRating}>{rev.rating} ★</Text>
                        </View>
                        <Text style={styles.reviewComment}>{rev.comment}</Text>
                        <Text style={styles.reviewDate}>{new Date(rev.created_at).toLocaleDateString()}</Text>
                    </View>
                ))}
               </>
           ) : (
               <Text style={styles.description}>No reviews yet.</Text>
           )}
        </View>
      </ScrollView>
      <View style={styles.footer}>
        <View style={styles.footerBtnContainer}>
            <TouchableOpacity 
              style={[styles.addToCartBtn, !canAdd && { opacity: 0.5 }]} 
              onPress={() => canAdd && addToCart(false)} 
              disabled={adding || !canAdd}
            >
            <Text style={styles.addToCartText}>Add to Cart</Text>
            </TouchableOpacity>
        </View>
        
        <View style={styles.footerBtnContainer}>
            <TouchableOpacity 
              onPress={() => canAdd && handleBuyNow()} 
              disabled={adding || !canAdd} 
              style={[{flex:1}, !canAdd && { opacity: 0.5 }]}
            >
                <LinearGradient
                    colors={THEME.gradients.primary as any}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 0 }}
                    style={styles.buyBtn}
                >
                    <Text style={styles.buyBtnText}>Buy Now</Text>
                </LinearGradient>
            </TouchableOpacity>
        </View>
      </View>
      <Modal visible={isZoomModalVisible} transparent={true} animationType="fade" onRequestClose={()=>{setIsZoomModalVisible(false);setZoomScale(1);}}>
        <View style={styles.modalBackground}>
            <TouchableOpacity style={styles.modalCloseOverlay} activeOpacity={1} onPress={()=>{setIsZoomModalVisible(false);setZoomScale(1);}} />
            <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>{product?.name || 'Zoom View'}</Text>
                <TouchableOpacity style={styles.modalCloseBtn} onPress={()=>{setIsZoomModalVisible(false);setZoomScale(1);}}>
                    <Ionicons name="close" size={24} color="#fff" />
                </TouchableOpacity>
            </View>
            <ScrollView maximumZoomScale={4} minimumZoomScale={1} showsHorizontalScrollIndicator={false} showsVerticalScrollIndicator={false} contentContainerStyle={styles.zoomScrollContent}>
                <Image source={{ uri: getZoomedImageUrl() }} style={[styles.zoomedImage, { transform: [{ scale: zoomScale }] }]} resizeMode="contain" />
            </ScrollView>
            <View style={styles.zoomControls}>
                <TouchableOpacity style={styles.zoomControlBtn} onPress={()=>setZoomScale(prev=>Math.max(1, prev-0.5))}>
                    <Ionicons name="remove-circle-outline" size={28} color="#fff" />
                </TouchableOpacity>
                <Text style={styles.zoomScaleText}>{Math.round(zoomScale*100)}%</Text>
                <TouchableOpacity style={styles.zoomControlBtn} onPress={()=>setZoomScale(prev=>Math.min(4, prev+0.5))}>
                    <Ionicons name="add-circle-outline" size={28} color="#fff" />
                </TouchableOpacity>
            </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  modalBackground: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.92)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalCloseOverlay: {
    ...StyleSheet.absoluteFillObject,
  },
  modalHeader: {
    position: 'absolute',
    top: Platform.OS === 'ios' ? 50 : 20,
    left: 20,
    right: 20,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    zIndex: 10,
  },
  modalTitle: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
    flex: 1,
    marginRight: 20,
  },
  modalCloseBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  zoomScrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    width: Dimensions.get('window').width,
    height: Dimensions.get('window').height,
  },
  zoomedImage: {
    width: '100%',
    height: '80%',
    maxHeight: 600,
  },
  zoomControls: {
    position: 'absolute',
    bottom: 40,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.6)',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 30,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    zIndex: 20,
  },
  zoomControlBtn: {
    padding: 5,
  },
  zoomScaleText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
    marginHorizontal: 15,
    minWidth: 45,
    textAlign: 'center',
  },
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scrollContent: {
      paddingBottom: 100,
  },
  headerOverlay: {
      position: 'absolute',
      top: 10,
      left: 0,
      right: 0,
      flexDirection: 'row',
      justifyContent: 'space-between',
      paddingHorizontal: 15,
      zIndex: 10,
  },
  iconBtn: {
      width: 40,
      height: 40,
      borderRadius: 20,
      backgroundColor: 'rgba(255,255,255,0.9)',
      justifyContent: 'center',
      alignItems: 'center',
      shadowColor: "#000",
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.1,
      elevation: 2,
  },
  imageWrapper: {
      width: width,
      height: width, // Square aspect ratio
      backgroundColor: '#f9f9f9',
      position: 'relative',
  },
  image: {
    width: '100%',
    height: '100%',
  },
  discountBadge: {
      position: 'absolute',
      bottom: 20,
      left: 20,
      backgroundColor: COLORS.accent,
      paddingHorizontal: 10,
      paddingVertical: 4,
      borderRadius: 4,
  },
  discountText: {
      color: '#fff',
      fontWeight: 'bold',
      fontSize: 12,
  },
  pagination: {
      position: 'absolute',
      bottom: 20,
      alignSelf: 'center',
      flexDirection: 'row',
  },
  dot: {
      width: 8,
      height: 8,
      borderRadius: 4,
      backgroundColor: 'rgba(0,0,0,0.2)',
      marginHorizontal: 4,
  },
  activeDot: {
      backgroundColor: COLORS.primary,
      width: 12,
  },
  content: {
    padding: 20,
  },
  category: {
      fontSize: 12,
      color: '#888',
      textTransform: 'uppercase',
      marginBottom: 5,
      letterSpacing: 1,
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    marginBottom: 10,
    color: '#1a1a1a',
    lineHeight: 32,
  },
  metaRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      marginBottom: 15,
  },
  ratingBox: {
      flexDirection: 'row',
      alignItems: 'center',
  },
  ratingText: {
      fontSize: 14,
      fontWeight: 'bold',
      color: COLORS.warning,
  },
  reviewText: {
      color: '#888',
      fontSize: 13,
      marginLeft: 5,
  },
  stockStatus: {
      backgroundColor: '#e6f4ea',
      paddingHorizontal: 8,
      paddingVertical: 3,
      borderRadius: 4,
  },
  inStock: {
      color: COLORS.success,
      fontSize: 12,
      fontWeight: '600',
  },
  priceContainer: {
      flexDirection: 'row',
      alignItems: 'baseline',
      marginBottom: 5,
  },
  price: {
    fontSize: 28,
    color: COLORS.primary, // Brand green
    fontWeight: '700',
    marginRight: 10,
  },
  oldPrice: {
      fontSize: 16,
      color: '#999',
      textDecorationLine: 'line-through',
      marginRight: 10,
  },
  taxText: {
      fontSize: 12,
      color: '#999',
  },
  divider: {
      height: 1,
      backgroundColor: '#f0f0f0',
      marginVertical: 20,
  },
  quantitySection: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
  },
  sectionTitle: {
      fontSize: 16,
      fontWeight: '600',
      color: '#333',
  },
  counter: {
      flexDirection: 'row',
      alignItems: 'center',
      borderWidth: 1,
      borderColor: '#d0d0d0',
      borderRadius: 20,
      backgroundColor: '#f9f9f9',
      overflow: 'hidden',
  },
  counterBtn: {
      width: 36,
      height: 36,
      backgroundColor: '#f0f0f0',
      justifyContent: 'center',
      alignItems: 'center',
  },
  quantityValue: {
      fontSize: 15,
      fontWeight: '700',
      marginHorizontal: 12,
      minWidth: 20,
      textAlign: 'center',
      color: '#333',
  },
  descriptionHead: {
    fontSize: 18,
    fontWeight: '600',
    marginBottom: 10,
    color: '#333',
  },
  description: {
    fontSize: 14,
    color: '#666',
    lineHeight: 24,
  },
  deliveryInfo: {
      marginTop: 20,
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: '#f9f9f9',
      padding: 10,
      borderRadius: 8,
  },
  deliveryText: {
      color: '#666',
      fontSize: 13,
  },
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    flexDirection: 'row',
    padding: 15,
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#f0f0f0',
    elevation: 20,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
  },
  footerBtnContainer: {
      flex: 1,
      paddingHorizontal: 5,
  },
  addToCartBtn: {
    flex: 1,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: COLORS.primary,
    paddingVertical: 12,
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addToCartText: {
    fontWeight: '700',
    fontSize: 14,
    color: COLORS.primary,
  },
  buyBtn: {
    flex: 1,
    paddingVertical: 13, // border slightly accounts for difference
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
  },
  buyBtnText: {
    fontWeight: '700',
    fontSize: 14,
    color: '#fff',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  reviewCard: {
      backgroundColor: '#f9f9f9',
      padding: 15,
      borderRadius: 12,
      marginBottom: 15,
  },
  reviewHeader: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: 8,
  },
  reviewerName: {
      fontSize: 14,
      fontWeight: 'bold',
      color: '#333',
  },
  reviewRating: {
      fontSize: 12,
      fontWeight: 'bold',
      color: COLORS.warning,
  },
  reviewComment: {
      fontSize: 13,
      color: '#555',
      lineHeight: 18,
      marginBottom: 10,
  },
  reviewDate: {
      fontSize: 11,
      color: '#999',
  },
  reviewForm: {
      backgroundColor: '#f9f9f9',
      padding: 15,
      borderRadius: 12,
      marginBottom: 20,
      borderWidth: 1,
      borderColor: '#eee',
  },
  reviewFormTitle: {
      fontSize: 16,
      fontWeight: 'bold',
      color: '#333',
      marginBottom: 10,
  },
  starSelector: {
      flexDirection: 'row',
      marginBottom: 15,
      justifyContent: 'center',
  },
  reviewInput: {
      backgroundColor: '#fff',
      borderWidth: 1,
      borderColor: '#ddd',
      borderRadius: 8,
      padding: 10,
      height: 80,
      textAlignVertical: 'top',
      marginBottom: 15,
  },
  submitReviewBtn: {
      backgroundColor: COLORS.primary,
      padding: 12,
      borderRadius: 8,
      alignItems: 'center',
  },
  submitReviewText: {
      color: '#fff',
      fontWeight: 'bold',
      fontSize: 14,
  },
  showMoreBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 6,
  },
  showMoreText: {
    color: COLORS.primary,
    fontWeight: '600',
    fontSize: 14,
  }
});

export default ProductDetailScreen;
