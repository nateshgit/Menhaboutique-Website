import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, StyleSheet } from 'react-native';
import api from '../services/api';
import ProductList from '../components/ProductList';
import { useNavigation } from '@react-navigation/native';

import { useWishlist } from '../context/WishlistContext';

const WishlistScreen = () => {
  const { wishlist, refreshWishlist } = useWishlist();
  const navigation = useNavigation<any>();

  useEffect(() => {
    refreshWishlist();
  }, []);

  const handleProductPress = (item: any) => {
    // The mapped item has id = product_id, so this works
    navigation.navigate('ProductDetail', { productId: item.id });
  };

  // Map wishlist items to be compatible with ProductList
  const mappedWishlist = wishlist.map(item => ({
      ...item,
      id: item.id || item.product_id, // Use product ID as expected by ProductList
      // Ensure other fields match if necessary
      name: item.name || item.title,
      price: item.price || item.new_price,
      // primary_image is already there
  }));

  if (wishlist.length === 0) {
      return (
          <View style={styles.center}>
              <Text style={{fontSize: 50}}>💔</Text>
              <Text style={styles.emptyText}>Your wishlist is empty</Text>
          </View>
      )
  }

  return (
    <View style={styles.container}>
       <ProductList 
            data={mappedWishlist} 
            scrollEnabled={true}
            onPress={handleProductPress} 
       />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  emptyText: {
      marginTop: 20,
      fontSize: 16,
      color: '#888',
  }
});

export default WishlistScreen;
