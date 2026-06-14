import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Alert } from 'react-native';
import api from '../services/api';

interface WishlistContextType {
  wishlist: any[];
  addToWishlist: (product: any) => Promise<void>;
  removeFromWishlist: (wishlistId: string) => Promise<void>;
  toggleWishlist: (product: any) => Promise<void>;
  isInWishlist: (productId: string) => boolean;
  refreshWishlist: () => Promise<void>;
}

const WishlistContext = createContext<WishlistContextType | undefined>(undefined);

export const WishlistProvider = ({ children }: { children: ReactNode }) => {
  const [wishlist, setWishlist] = useState<any[]>([]);

  const refreshWishlist = async () => {
    try {
      const wishlistJson = await AsyncStorage.getItem('mb_wishlist');
      if (wishlistJson) {
          setWishlist(JSON.parse(wishlistJson));
      } else {
          setWishlist([]);
      }
    } catch (error) {
      console.error("Failed to fetch wishlist", error);
    }
  };

  useEffect(() => {
    refreshWishlist();
  }, []);

  const addToWishlist = async (product: any) => {
    try {
      const wishlistJson = await AsyncStorage.getItem('mb_wishlist') || '[]';
      let list = JSON.parse(wishlistJson);
      if (!list.find((item: any) => item.id === product.id)) {
          list.push(product);
          await AsyncStorage.setItem('mb_wishlist', JSON.stringify(list));
          setWishlist(list);
      }
    } catch (error) {
      console.error("Failed to add to wishlist", error);
      throw error;
    }
  };

  const removeFromWishlist = async (productId: string) => {
    try {
        const wishlistJson = await AsyncStorage.getItem('mb_wishlist') || '[]';
        let list = JSON.parse(wishlistJson);
        list = list.filter((item: any) => item.id !== productId);
        await AsyncStorage.setItem('mb_wishlist', JSON.stringify(list));
        setWishlist(list);
    } catch (error) {
        console.error("Failed to remove from wishlist", error);
        throw error;
    }
  };

  const isInWishlist = (productId: string) => {
      return wishlist.some(item => item.id === productId); 
  };

  const toggleWishlist = async (product: any) => {
      if (isInWishlist(product.id)) {
          await removeFromWishlist(product.id);
      } else {
          await addToWishlist(product);
      }
  };

  return (
    <WishlistContext.Provider value={{ wishlist, addToWishlist, removeFromWishlist, toggleWishlist, isInWishlist, refreshWishlist }}>
      {children}
    </WishlistContext.Provider>
  );
};

export const useWishlist = () => {
  const context = useContext(WishlistContext);
  if (context === undefined) {
    throw new Error('useWishlist must be used within a WishlistProvider');
  }
  return context;
};
