import React, { useState, useEffect } from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import { View, Text } from 'react-native';
import Loader from '../components/Loader';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons'; // Ensure typical Expo icons
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { COLORS } from '../constants/theme';
import { useCart } from '../context/CartContext';
import { setAuthToken } from '../services/api';

// Screens
import LoginScreen from '../screens/LoginScreen';
import HomeScreen from '../screens/HomeScreen';
import ProductDetailScreen from '../screens/ProductDetailScreen';
import CartScreen from '../screens/CartScreen';
import ProfileScreen from '../screens/ProfileScreen';
import OrdersScreen from '../screens/OrdersScreen';
import CheckoutScreen from '../screens/CheckoutScreen';
import AddressScreen from '../screens/AddressScreen';
import OrderDetailScreen from '../screens/OrderDetailScreen';
import CategoryProductsScreen from '../screens/CategoryProductsScreen';
import AddAddressScreen from '../screens/AddAddressScreen';
import ForgotPasswordScreen from '../screens/ForgotPasswordScreen';
import CategoriesScreen from '../screens/CategoriesScreen';
import ProductsScreen from '../screens/ProductsScreen';
import ContactScreen from '../screens/ContactScreen';
import PrivacyPolicyScreen from '../screens/PrivacyPolicyScreen';
import TermsScreen from '../screens/TermsScreen';
import WishlistScreen from '../screens/WishlistScreen';

// Types
export type RootStackParamList = {
  MainTabs: undefined;
  Login: { setIsAuthenticated?: (val: boolean) => void };
  Signup: { setIsAuthenticated?: (val: boolean) => void };
  ProductDetail: { productId: string };
  Checkout: { cartItems: any[], totalAmount: number };
  Orders: undefined;
  OrderDetail: { orderId: string, orderData?: any };
  Address: undefined;
  AddAddress: { address?: any };
  CategoryProducts: { categoryId: string, categoryName: string };
  ForgotPassword: undefined;
  Products: undefined;
  Contact: undefined;
  PrivacyPolicy: undefined;
  Terms: undefined;
  Wishlist: undefined;
};

// Navigators
const Stack = createStackNavigator<RootStackParamList>();
const Tab = createBottomTabNavigator();

// 1. Define Bottom Tab Navigator

const MainTabs = () => {
  const { cartCount } = useCart();
  const insets = useSafeAreaInsets();

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: COLORS.primary, // Brand Green
        tabBarInactiveTintColor: '#686e7d', // Dark Gray
        tabBarStyle: {
            height: 60 + insets.bottom,
            paddingBottom: 8 + insets.bottom,
            paddingTop: 8,
            backgroundColor: '#ffffff',
            borderTopWidth: 1,
            borderTopColor: '#f0f0f0',
            elevation: 10,
            shadowColor: '#000',
            shadowOffset: { width: 0, height: -2 },
            shadowOpacity: 0.1,
            shadowRadius: 4,
        },
        tabBarIcon: ({ focused, color, size }) => {
          let iconName: any;

          if (route.name === 'Home') {
            iconName = focused ? 'home' : 'home-outline';
          } else if (route.name === 'Category') { // Placeholder if we add a Category Screen
            iconName = focused ? 'grid' : 'grid-outline';
          } else if (route.name === 'Cart') {
            iconName = focused ? 'cart' : 'cart-outline';
          } else if (route.name === 'Profile') {
            iconName = focused ? 'person' : 'person-outline';
          }

          return <Ionicons name={iconName} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="Home" component={HomeScreen} />
      <Tab.Screen name="Category" component={CategoriesScreen} />
      <Tab.Screen 
        name="Cart" 
        component={CartScreen} 
        options={{ 
          tabBarBadge: cartCount > 0 ? cartCount : undefined,
          tabBarBadgeStyle: { backgroundColor: COLORS.accent, color: '#fff', fontSize: 10, minWidth: 16, height: 16, lineHeight: 16 }
        }} 
      /> 
      <Tab.Screen name="Profile" component={ProfileScreen} />
    </Tab.Navigator>
  );
};

// 2. Define Main Stack Navigator (Wraps Tabs + Other Screens)
const AppNavigator = () => {
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const token = await AsyncStorage.getItem('auth_token');
      if (token) {
        setAuthToken(token);
        setIsAuthenticated(true);
      }
    } catch (e) {
      console.error(e);
    } finally {
      // Short delay to prevent flicker if needed, or just set loading false
      setLoading(false);
    }
  };

  if (loading) {
    return <Loader fullScreen />;
  }

  return (
    <Stack.Navigator initialRouteName="MainTabs">
        {/* Main Tabs (Home, Profile, etc.) - Always visible first */}
        <Stack.Screen 
            name="MainTabs" 
            component={MainTabs} 
            options={{ headerShown: false }} 
        />
        
        {/* Auth Screens */}
        <Stack.Screen 
            name="Login" 
            component={LoginScreen} 
            options={{ headerShown: false }} 
            initialParams={{ setIsAuthenticated }}
        />

        <Stack.Screen 
            name="Signup" 
            component={require('../screens/SignupScreen').default} 
            options={{ headerShown: false }} 
            initialParams={{ setIsAuthenticated }}
        />

        {/* Detail & Stack Screens */}
        <Stack.Screen 
            name="ProductDetail" 
            component={ProductDetailScreen} 
            options={{ title: 'Product Details' }} 
        />
        
        <Stack.Screen 
            name="Checkout"  
            component={CheckoutScreen} 
            options={{ headerShown: false }} 
        />
        
        <Stack.Screen 
            name="Orders" 
            component={OrdersScreen} 
            options={{ title: 'My Orders' }} 
        /> 

        <Stack.Screen 
            name="OrderDetail" 
            component={OrderDetailScreen} 
            options={{ title: 'Order Details' }} 
        />
        
        <Stack.Screen 
            name="Address" 
            component={AddressScreen} 
            options={{ headerShown: false }} 
        /> 
        
        <Stack.Screen 
            name="CategoryProducts" 
            component={CategoryProductsScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="AddAddress" 
            component={AddAddressScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="ForgotPassword" 
            component={ForgotPasswordScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="Products" 
            component={ProductsScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="Contact" 
            component={ContactScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="PrivacyPolicy" 
            component={PrivacyPolicyScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="Terms" 
            component={TermsScreen} 
            options={{ headerShown: false }} 
        />
        <Stack.Screen 
            name="Wishlist" 
            component={WishlistScreen} 
            options={{ title: 'My Wishlist' }} 
        />
    </Stack.Navigator>
  );
};

export default AppNavigator;
