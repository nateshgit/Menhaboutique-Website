import React from 'react';
import {
  View, Text, TouchableOpacity, StyleSheet,
  Dimensions, Platform, Alert
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { COLORS, THEME } from '../constants/theme';
import CachedImage from './CachedImage';
import { resolveImageUrl } from '../utils/imageUtils';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useWishlist } from '../context/WishlistContext';
import { useCart } from '../context/CartContext';

const { width } = Dimensions.get('window');
const CARD_MARGIN = 10;
const CONTAINER_PADDING = 10;
const COLUMN_WIDTH = (width - CONTAINER_PADDING * 2 - CARD_MARGIN) / 2;
const IMAGE_SIZE = COLUMN_WIDTH; // Square card image

interface ProductCardProps {
  item: any;
  onPress: (item: any) => void;
}

const ProductCard: React.FC<ProductCardProps> = ({ item, onPress }) => {
  const navigation = useNavigation<any>();
  const { updateCartCount } = useCart();
  const { toggleWishlist, isInWishlist } = useWishlist();

  const handleAddButtonPress = async (productItem: any) => {
    try {
      const cartJson = (await AsyncStorage.getItem('mb_cart')) || '[]';
      let cart = JSON.parse(cartJson);
      const existing = cart.find((i: any) => i.product.id === productItem.id);
      if (existing) {
        existing.quantity += 1;
      } else {
        cart.push({ product: productItem, quantity: 1 });
      }
      await AsyncStorage.setItem('mb_cart', JSON.stringify(cart));
      await updateCartCount();
      Alert.alert('Success', 'Added to cart!');
    } catch (error) {
      Alert.alert('Error', 'Failed to add to cart');
    }
  };

  const handleBuyNowPress = async (productItem: any) => {
    const displayPrice = productItem.new_price || productItem.newPrice || productItem.price || 0;
    const token = await AsyncStorage.getItem('auth_token');
    const cartItems = [{ product: productItem, quantity: 1 }];
    if (!token) {
      if (Platform.OS === 'web') {
        alert('Please login to place your order.');
        navigation.navigate('Login', { redirect: 'Checkout', cartItems, totalAmount: displayPrice });
      } else {
        Alert.alert('Login Required', 'Please login to place your order.', [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Login',
            onPress: () =>
              navigation.navigate('Login', { redirect: 'Checkout', cartItems, totalAmount: displayPrice }),
          },
        ]);
      }
    } else {
      navigation.navigate('Checkout', { cartItems, totalAmount: displayPrice });
    }
  };

  const raw =
    item.primary_image ||
    item.primaryImage ||
    (item.images?.length > 0
      ? item.images[0].image_url || item.images[0].imageUrl || item.images[0].url
      : null) ||
    item.image;
  const imageUrl = resolveImageUrl(raw, 300);

  const rating = item.rating || 4.5;
  const reviews = item.reviews ? item.reviews.length : item.reviewCount || 0;
  const displayPrice = item.new_price || item.newPrice || item.price;
  const displayOldPrice = item.old_price || item.oldPrice;

  const attr = item.product_attributes?.length > 0 ? item.product_attributes[0] : null;
  let weightDisplay = item.weight || '';
  if (attr) {
    weightDisplay = String(attr.attribute_value || '');
    if (
      attr.uom &&
      String(attr.uom).trim() !== '' &&
      String(attr.uom) !== 'undefined' &&
      String(attr.uom) !== 'null'
    ) {
      if (!weightDisplay.toLowerCase().includes(String(attr.uom).toLowerCase())) {
        weightDisplay = `${weightDisplay} ${attr.uom}`;
      }
    }
    weightDisplay = weightDisplay.replace(/mlml/gi, 'ml').replace(/gg/gi, 'g');
  }

  let stockStatus = item.status;
  if (!stockStatus || stockStatus === 'In Stock' || stockStatus === 'Out of Stock') {
    stockStatus = parseInt(item.stock_quantity || 0) > 0 ? 'In Stock' : 'Out of Stock';
  }
  const isOutOfStock = stockStatus === 'Out of Stock';
  const canAdd = !isOutOfStock;

  return (
    <TouchableOpacity
      style={[styles.card, isOutOfStock && { opacity: 0.5 }]}
      onPress={() => onPress(item)}
      activeOpacity={0.9}
      disabled={isOutOfStock}
    >
      <View style={styles.imageContainer}>
        {(item.sale || (displayOldPrice && displayPrice < displayOldPrice)) && (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>SALE</Text>
          </View>
        )}
        <TouchableOpacity
          style={styles.wishlistIcon}
          onPress={() => toggleWishlist(item)}
          activeOpacity={0.8}
        >
          <Ionicons
            name={isInWishlist(item.id) ? 'heart' : 'heart-outline'}
            size={16}
            color={isInWishlist(item.id) ? COLORS.danger || '#ff4d4f' : '#888'}
          />
        </TouchableOpacity>

        <CachedImage
          source={{ uri: imageUrl }}
          style={styles.image}
          containerStyle={styles.imageInner}
          resizeMode="cover"
        />
      </View>

      <View style={styles.details}>
        <Text style={styles.name} numberOfLines={2}>
          {item.name || item.title}
        </Text>

        <View style={styles.ratingRow}>
          <View style={styles.stars}>
            {[1, 2, 3, 4, 5].map((star) => (
              <Ionicons
                key={star}
                name={star <= Math.round(rating) ? 'star' : 'star-outline'}
                size={10}
                color={COLORS.warning}
              />
            ))}
          </View>
          <Text style={styles.ratingText}>({reviews} reviews)</Text>
        </View>

        <View style={styles.priceRow}>
          <Text style={styles.price}>₹{displayPrice}</Text>
          {displayOldPrice && displayOldPrice > displayPrice && (
            <Text style={styles.oldPrice}>₹{displayOldPrice}</Text>
          )}
        </View>

        <View style={styles.weightContainer}>
          <Text style={styles.weightText}>{weightDisplay}</Text>
        </View>

        <View style={styles.buttonsRow}>
          <TouchableOpacity
            style={[styles.addButtonContainer, !canAdd && { opacity: 0.5 }]}
            onPress={() => canAdd && handleAddButtonPress(item)}
            disabled={!canAdd}
          >
            <LinearGradient
              colors={THEME.gradients.primary as any}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.actionButton}
            >
              <Ionicons name="cart-outline" size={14} color="#fff" style={{ marginRight: 2 }} />
              <Text style={styles.actionButtonText}>Cart</Text>
            </LinearGradient>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.buyButtonContainer, !canAdd && { opacity: 0.5 }]}
            onPress={() => canAdd && handleBuyNowPress(item)}
            disabled={!canAdd}
          >
            <LinearGradient
              colors={THEME.gradients.accent as any}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.actionButton}
            >
              <Ionicons name="flash-outline" size={14} color="#fff" style={{ marginRight: 2 }} />
              <Text style={styles.actionButtonText}>Buy</Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>

        {isOutOfStock && (
          <Text style={{ color: COLORS.danger, fontSize: 10, textAlign: 'center', marginTop: 5, fontWeight: 'bold' }}>
            Out of Stock
          </Text>
        )}
      </View>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  card: {
    width: COLUMN_WIDTH,
    backgroundColor: '#fff',
    marginBottom: 15,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#f0f0f0',
    overflow: 'hidden',
  },
  imageContainer: {
    height: IMAGE_SIZE,
    width: '100%',
    position: 'relative',
    backgroundColor: '#ebebeb',
  },
  imageInner: {
    width: '100%',
    height: IMAGE_SIZE,
  },
  image: {
    width: '100%',
    height: IMAGE_SIZE,
  },
  badge: {
    position: 'absolute',
    top: 10,
    left: 10,
    backgroundColor: COLORS.accent,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
    zIndex: 1,
  },
  badgeText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: 'bold',
  },
  wishlistIcon: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    elevation: 2,
  },
  details: {
    padding: 10,
  },
  name: {
    fontSize: 13,
    color: '#333',
    fontWeight: '600',
    marginBottom: 4,
    height: 36,
    lineHeight: 18,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  stars: {
    flexDirection: 'row',
  },
  ratingText: {
    fontSize: 10,
    color: '#888',
    marginLeft: 4,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  price: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.primary,
    marginRight: 6,
  },
  oldPrice: {
    fontSize: 11,
    color: '#999',
    textDecorationLine: 'line-through',
  },
  weightContainer: {
    marginBottom: 8,
  },
  weightText: {
    fontSize: 11,
    color: '#666',
  },
  buttonsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 4,
    width: '100%',
  },
  addButtonContainer: {
    flex: 1,
    marginRight: 4,
  },
  buyButtonContainer: {
    flex: 1,
    marginLeft: 4,
  },
  actionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 8,
    borderRadius: 20,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 11,
    fontWeight: '600',
  },
});

export default ProductCard;
