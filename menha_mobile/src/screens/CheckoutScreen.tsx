import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert, TextInput, SafeAreaView, StatusBar, ActivityIndicator, Image, Modal, KeyboardAvoidingView, Platform, Linking } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { MainAPI } from '../services/api';
import PaymentGatewayService from '../services/PaymentGatewayService';
import { useCart } from '../context/CartContext';
import { resolveImageUrl } from '../utils/imageUtils';
import { COLORS, THEME } from '../constants/theme';

const CheckoutScreen = () => {
  const navigation = useNavigation<any>();
  const { updateCartCount } = useCart();
  const route = useRoute<any>();
  const { cartItems: initialCartItems } = route.params || { cartItems: [] };
  const [cartItems, setCartItems] = useState<any[]>(initialCartItems || []);

  const totalAmount = cartItems.reduce((sum, item) => {
      const p = item.product || item;
      const price = p.new_price || p.newPrice || p.price || 0;
      return sum + (price * item.quantity);
  }, 0);

  const [savedAddresses, setSavedAddresses] = useState<any[]>([]);
  const [selectedAddressId, setSelectedAddressId] = useState<string | null>(null);
  const [addNewAddress, setAddNewAddress] = useState(false);
  const [isAddressExpanded, setIsAddressExpanded] = useState(false);
  
  // New address form states
  const [newFirstName, setNewFirstName] = useState('');
  const [newLastName, setNewLastName] = useState('');
  const [newAddressLine, setNewAddressLine] = useState('');
  const [newAddressLine2, setNewAddressLine2] = useState('');
  const [newMobile, setNewMobile] = useState('');
  const [newCity, setNewCity] = useState('');
  const [newPostCode, setNewPostCode] = useState('');
  const [newState, setNewState] = useState('');
  const [newCountry, setNewCountry] = useState('India');
  
  // Location Dynamic States
  const [selectedCountry, setSelectedCountry] = useState<any>(null);
  const [selectedState, setSelectedState] = useState<any>(null);
  const [selectedCity, setSelectedCity] = useState<any>(null);
  const [countries, setCountries] = useState<any[]>([]);
  const [states, setStates] = useState<any[]>([]);
  const [cities, setCities] = useState<any[]>([]);
  const [modalVisible, setModalVisible] = useState(false);
  const [selectionType, setSelectionType] = useState<'country' | 'state' | 'city' | 'courier' | null>(null);

  // Helper Functions for Location
  const fetchCountries = async () => {
    try {
        const countries = await MainAPI.getCountries();
        setCountries(countries);
        const india = countries.find((c: any) => c.name === 'India');
        if (india) {
           setSelectedCountry(india);
           setNewCountry(india.name);
           fetchStates(india.id);
        }
    } catch (error) {
        console.log('Error fetching countries', error);
    }
  };

  const fetchStates = async (countryId: any) => {
      try {
          const states = await MainAPI.getStates(countryId);
          setStates(states);
      } catch (error) {
          console.log('Error fetching states', error);
      }
  };

  const fetchCities = async (stateId: any) => {
      try {
          const cities = await MainAPI.getCities(stateId);
          setCities(cities);
      } catch (error) {
          console.log('Error fetching cities', error);
      }
  };

  const openModal = (type: 'country' | 'state' | 'city' | 'courier') => {
      if (type === 'state' && !selectedCountry) {
          Alert.alert('Notice', 'Please select a country first');
          return;
      }
      if (type === 'city' && !selectedState) {
          Alert.alert('Notice', 'Please select a state first');
          return;
      }
      setSelectionType(type);
      setModalVisible(true);
  };

  const handleSelectLocation = (item: any, type: string) => {
      if (type === 'courier') {
          setSelectedCourier(item);
      } else if (type === 'country') {
          setSelectedCountry(item);
          setNewCountry(item.name);
          setSelectedState(null);
          setNewState('');
          setSelectedCity(null);
          setNewCity('');
          setStates([]);
          setCities([]);
          fetchStates(item.id);
      } else if (type === 'state') {
          setSelectedState(item);
          setNewState(item.name);
          setSelectedCity(null);
          setNewCity('');
          setCities([]);
          fetchCities(item.id);
      } else if (type === 'city') {
          setSelectedCity(item);
          setNewCity(item.name);
      }
      setModalVisible(false);
      setSelectionType(null);
  };

  const getLocationListData = () => {
      if (selectionType === 'country') return countries;
      if (selectionType === 'state') return states;
      if (selectionType === 'city') return cities;
      if (selectionType === 'courier') return couriers;
      return [];
  };

  // Payment Gateway & Courier State
  const [activeGateway, setActiveGateway] = useState<any>(null);
  const [couriers, setCouriers] = useState<any[]>([]);
  const [selectedCourier, setSelectedCourier] = useState<any>(null);

  const fetchData = async () => {
    try {
        const [gatewayList, courierList] = await Promise.all([
            MainAPI.getAvailableGateways(),
            MainAPI.getAvailableCouriers()
        ]);
        if (gatewayList.length > 0) {
            setActiveGateway(gatewayList[0]);
            setPaymentMethod('online');
        }
        setCouriers(courierList);
        if (courierList.length > 0) {
            setSelectedCourier(courierList[0]);
        }
    } catch (error) {
        console.log('Error fetching checkout data', error);
    }
  };

  // Checkout State
  const [address, setAddress] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState('online');
  const [deliveryFee, setDeliveryFee] = useState(0);

  const updateDeliveryCharge = async (stateName: string, items = cartItems) => {
    if (!stateName) {
        setDeliveryFee(0);
        return;
    }
    try {
        // Find state code if possible, or just pass state name if the API handles it.
        // The API expects stateCode. Let's find it.
        let stateCode = stateName;
        if (states.length > 0) {
            const found = states.find(s => s.name === stateName);
            if (found) stateCode = found.code;
        }
        const fee = await MainAPI.calculateDeliveryCharge(stateCode, items);
        setDeliveryFee(fee);
    } catch (error) {
        console.log('Error calculating delivery fee', error);
        setDeliveryFee(0);
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
          
          if (selectedAddressId && savedAddresses.length > 0) {
              const selectedAddr = savedAddresses.find(a => a.id === selectedAddressId);
              if (selectedAddr) {
                  await updateDeliveryCharge(selectedAddr.state, updatedCart);
              }
          } else if (addNewAddress && newState) {
              await updateDeliveryCharge(newState, updatedCart);
          }
      } catch (error) {
          console.error(error);
          Alert.alert('Error', 'Could not update quantity');
      }
  };

  const handlePayment = async () => {
    let finalAddressId = selectedAddressId;

    if (addNewAddress) {
         if (!newFirstName || !newAddressLine || !newCity || !newPostCode || !newState || !newCountry || !newMobile) {
             Alert.alert('Required', 'Please fill all mandatory address fields including name and mobile');
             return;
         }

         const phoneRegex = /^[0-9]{10}$/;
         if (!phoneRegex.test(newMobile)) {
             Alert.alert('Error', 'Mobile number must be exactly 10 digits containing only numbers');
             return;
         }
         setIsProcessing(true);
         try {
             // Create Address
             const addressData = await MainAPI.saveAddress({
                 firstName: newFirstName,
                 lastName: newLastName,
                 addressLine: newAddressLine,
                 addressLine2: newAddressLine2,
                 city: newCity,
                 postalCode: newPostCode,
                 state: newState,
                 country: newCountry,
                 phoneNumber: newMobile,
                 isDefault: false
             });
             finalAddressId = addressData.id;
         } catch (err) {
             console.log(err);
             Alert.alert('Error', 'Could not save new address');
             setIsProcessing(false);
             return;
         }
    } else {
        if (!finalAddressId) {
             Alert.alert('Required', 'Please select a delivery address');
             setIsProcessing(false);
             return;
        }
    }

    // Native Payment Gateway Processing
    let transactionId = null;
    const finalTotal = totalAmount + deliveryFee;
    if (paymentMethod === 'online') {
        setIsProcessing(true);
        try {
            // We pass a mock order object with necessary details for SDKs
            const mockOrder = {
                total_price: finalTotal,
                order_number: 'ORD-' + Date.now(),
                email: '', // Should be fetched if available
                phone_number: '', 
                customer_name: `${newFirstName} ${newLastName}`
            };
            
            const result: any = await PaymentGatewayService.processPayment(mockOrder, activeGateway);
            if (result && result.success) {
                transactionId = result.transactionId;
                Alert.alert('Payment Successful', 'Transaction ID: ' + transactionId);
            } else {
                throw new Error('Payment failed or cancelled');
            }
        } catch (error: any) {
            console.error('Payment Error:', error);
            Alert.alert('Payment Failed', error.message || 'Transaction could not be completed');
            setIsProcessing(false);
            return;
        }
    }

    setIsProcessing(true);
    
    try {
        const orderData = await MainAPI.createOrder({
            shippingAddressId: finalAddressId,
            items: cartItems.map((item: any) => {
                const p = item.product || item;
                const unitPrice = p.new_price || p.newPrice || p.price || 0; 
                return { 
                    productId: p.id, 
                    quantity: item.quantity,
                    price: unitPrice,
                    total: unitPrice * item.quantity
                };
            }),
            paymentMethod: paymentMethod === 'online' ? 'online' : 'cod',
            payment_status: paymentMethod === 'online' ? 'paid' : 'unpaid',
            total: totalAmount + deliveryFee,
            delivery_charge: deliveryFee,
            courier_id: selectedCourier?.id || null,
            courier_name: selectedCourier?.name || null,
            gateway_transaction_id: transactionId
        });
        
        setIsProcessing(false);
        // Clear Cart
        await AsyncStorage.removeItem('mb_cart');
        await updateCartCount();
        
        Alert.alert('Order Successful', 'Your order has been placed successfully!', [
           { text: 'View Orders', onPress: () => navigation.navigate('Orders') }
        ]);
    } catch (error: any) {
        console.error(error);
        setIsProcessing(false);
        const msg = error.message || 'Payment failed';
        Alert.alert('Error', msg);
    }
  };

  const fetchProfile = async () => {
      try {
          const res = await api.get('/auth/me');
          const user = res.data.user || res.data;
          if (user) {
              setNewFirstName(user.first_name || user.firstName || '');
              setNewLastName(user.last_name || user.lastName || '');
          }
      } catch (err) {
         // silent fail
      }
  };

  React.useEffect(() => {
    fetchAddresses();
    fetchCountries();
    fetchProfile();
    fetchData();
  }, []);

  const fetchAddresses = async () => {
    try {
      const list = await MainAPI.getAddresses();
      setSavedAddresses(list);
      
      // Auto-select default or first
      if (list.length > 0) {
          selectAddress(list[0]);
      } else {
          setAddNewAddress(true);
      }
    } catch (error) {
      console.log('Error fetching addresses', error);
      setAddNewAddress(true); // Fallback to form
    }
  };

  const selectAddress = (item: any) => {
      setSelectedAddressId(item.id);
      setAddNewAddress(false);
      // Format address for the order payload (which currently expects a string)
      // Assuming item has: address_line1 (or address), city, state, postal_code (or zipcode)
      const formatted = `${item.address || item.address_line1}, ${item.city}, ${item.state}, ${item.zip_code || item.postal_code || item.postCode}, ${item.country}`;
      setAddress(formatted);
      updateDeliveryCharge(item.state);
  };
  
  const handleNewAddressChange = () => {
      // Re-construct the full address string whenever a field changes
      // This is a bit inefficient to run on every render but simple for now. 
      // Better: construct it only on submit.
      // BUT `address` state is used by handlePayment check.
      // So we should update `address` state when these change OR update handlePayment to check these fields if addNewAddress is true.
      // Let's update `address` state:
      const formatted = `${newAddressLine}, ${newAddressLine2}, ${newCity}, ${newState}, ${newPostCode}, ${newCountry}`;
      setAddress(formatted);
  };

  // Effect to update address string when new address fields change
  React.useEffect(() => {
      if (addNewAddress) {
          const formatted = [newAddressLine, newAddressLine2, newCity, newPostCode, newState, newCountry].filter(Boolean).join(', ');
          setAddress(formatted);
          if (newState) updateDeliveryCharge(newState);
      }
  }, [newAddressLine, newAddressLine2, newCity, newPostCode, newState, newCountry, addNewAddress]);

  // Render
  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#f5f5f5" />
        <KeyboardAvoidingView 
            behavior={Platform.OS === 'ios' ? 'padding' : 'height'} 
            style={{flex: 1}}
            keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
        >
          {/* Custom Header */}
          <View style={styles.topHeader}>
              <TouchableOpacity onPress={() => navigation.goBack()} style={{padding: 10}}>
                  <Ionicons name="arrow-back" size={24} color="#333" />
              </TouchableOpacity>
              <Text style={styles.headerTitle}>Checkout</Text>
              <View style={{width: 44}} /> 
          </View>

          <ScrollView contentContainerStyle={styles.scrollContent}>
            
            {/* Address Section */}
            <View style={styles.section}>
                <View style={styles.sectionHeader}>
                    <Ionicons name="location-outline" size={20} color="#333" />
                    <Text style={styles.sectionTitle}>Delivery Address</Text>
                </View>
                
                {savedAddresses.length > 0 && (
                    <View style={{ marginBottom: 10 }}>
                        <TouchableOpacity 
                            style={styles.dropdownTrigger}
                            onPress={() => setIsAddressExpanded(!isAddressExpanded)}
                        >
                            <View style={{ flex: 1, paddingRight: 10 }}>
                                {addNewAddress ? (
                                    <Text style={[styles.dropdownText, { fontWeight: '600', color: COLORS.primary }]}>+ Add New Address</Text>
                                ) : (
                                    (() => {
                                        const selectedAddress = savedAddresses.find(a => a.id === selectedAddressId) || savedAddresses[0];
                                        return (
                                            <Text style={styles.dropdownText} numberOfLines={1}>
                                                {selectedAddress.type || 'Home'}: {selectedAddress.address || selectedAddress.address_line1}, {selectedAddress.city}
                                            </Text>
                                        );
                                    })()
                                )}
                            </View>
                            <Ionicons name={isAddressExpanded ? "chevron-up" : "chevron-down"} size={20} color="#666" />
                        </TouchableOpacity>

                        {isAddressExpanded && (
                            <View style={{ backgroundColor: '#fafafa', padding: 10, borderRadius: 12, borderWidth: 1, borderColor: '#eee', marginTop: 5 }}>
                                <ScrollView style={{ maxHeight: 250 }} nestedScrollEnabled={true}>
                                    {savedAddresses.map((item) => {
                                        const isSelected = selectedAddressId === item.id && !addNewAddress;
                                        return (
                                            <TouchableOpacity 
                                                key={item.id} 
                                                style={[
                                                    styles.addressCard, 
                                                    isSelected && styles.addressCardSelected,
                                                    { padding: 10, marginBottom: 8 }
                                                ]}
                                                onPress={() => {
                                                    selectAddress(item);
                                                    setIsAddressExpanded(false);
                                                }}
                                            >
                                                <View style={styles.radioContainer}>
                                                    <View style={[styles.radio, isSelected && styles.radioSelected, { width: 18, height: 18, borderRadius: 9 }]} />
                                                </View>
                                                <View style={styles.addressDetails}>
                                                    <Text style={styles.addressName}>{item.type || 'Home'}</Text>
                                                    <Text style={styles.addressText} numberOfLines={1}>
                                                        {item.address || item.address_line1}{item.address_line2 ? `, ${item.address_line2}` : ''}, {item.city}
                                                    </Text>
                                                    <Text style={styles.addressText} numberOfLines={1}>
                                                        {item.state} - {item.zip_code || item.postal_code || item.postCode}
                                                    </Text>
                                                </View>
                                            </TouchableOpacity>
                                        );
                                    })}
                                    
                                    <TouchableOpacity 
                                        style={[
                                            styles.addNewToggle, 
                                            addNewAddress && styles.addressCardSelected,
                                            { padding: 10, marginTop: 0 }
                                        ]}
                                        onPress={() => {
                                            setAddNewAddress(true);
                                            setSelectedAddressId(null);
                                            setAddress('');
                                            setIsAddressExpanded(false);
                                        }}
                                    >
                                        <View style={styles.radioContainer}>
                                            <View style={[styles.radio, addNewAddress && styles.radioSelected, { width: 18, height: 18, borderRadius: 9 }]} />
                                        </View>
                                        <Text style={styles.addNewText}>+ Add New Address</Text>
                                    </TouchableOpacity>
                                </ScrollView>
                            </View>
                        )}
                    </View>
                )}

                {/* New Address Form */}
                {addNewAddress && (
                    <View style={styles.newAddressForm}>
                        <View style={styles.row}>
                             <View style={[styles.inputWrapper, { flex: 1, marginRight: 10 }]}>
                                 <Ionicons name="person-outline" size={20} color="#666" style={styles.inputIcon} />
                                 <TextInput 
                                     style={styles.input}
                                     placeholder="First Name"
                                     placeholderTextColor="#999"
                                     value={newFirstName}
                                     onChangeText={setNewFirstName}
                                 />
                             </View>
                             <View style={[styles.inputWrapper, { flex: 1 }]}>
                                 <TextInput 
                                     style={styles.input}
                                     placeholder="Last Name"
                                     placeholderTextColor="#999"
                                     value={newLastName}
                                     onChangeText={setNewLastName}
                                 />
                             </View>
                        </View>
                        <View style={styles.inputWrapper}>
                            <Ionicons name="location-outline" size={20} color="#666" style={styles.inputIcon} />
                            <TextInput 
                                style={styles.input}
                                placeholder="Address Line 1 (House No, Street)"
                                placeholderTextColor="#999"
                                value={newAddressLine}
                                onChangeText={setNewAddressLine}
                            />
                        </View>
                        <View style={styles.inputWrapper}>
                            <Ionicons name="location-outline" size={20} color="#666" style={styles.inputIcon} />
                            <TextInput 
                                style={styles.input}
                                placeholder="Address Line 2 (Optional)"
                                placeholderTextColor="#999"
                                value={newAddressLine2}
                                onChangeText={setNewAddressLine2}
                            />
                        </View>
                        <View style={styles.inputWrapper}>
                            <Ionicons name="call-outline" size={20} color="#666" style={styles.inputIcon} />
                            <TextInput 
                                style={styles.input}
                                placeholder="Mobile Number"
                                placeholderTextColor="#999"
                                keyboardType="phone-pad"
                                value={newMobile}
                                onChangeText={setNewMobile}
                            />
                        </View>

                        <View style={styles.row}>
                            <TouchableOpacity 
                                style={[styles.inputWrapper, { flex: 1, marginRight: 10 }]} 
                                onPress={() => openModal('country')}
                            >
                                 <Text style={[styles.inputText, !newCountry && styles.placeholderText]}>{newCountry || "Country"}</Text>
                                 <Ionicons name="chevron-down" size={16} color="#999" />
                            </TouchableOpacity>
                            
                            <TouchableOpacity 
                                style={[styles.inputWrapper, { flex: 1 }]}
                                onPress={() => openModal('state')}
                            >
                                 <Text style={[styles.inputText, !newState && styles.placeholderText]}>{newState || "State"}</Text>
                                 <Ionicons name="chevron-down" size={16} color="#999" />
                            </TouchableOpacity>
                        </View>

                        <View style={styles.row}>
                            <TouchableOpacity 
                                style={[styles.inputWrapper, { flex: 1, marginRight: 10 }]}
                                onPress={() => openModal('city')}
                            >
                                 <Text style={[styles.inputText, !newCity && styles.placeholderText]}>{newCity || "City"}</Text>
                                 <Ionicons name="chevron-down" size={16} color="#999" />
                            </TouchableOpacity>

                            <View style={[styles.inputWrapper, { flex: 1 }]}>
                                 <TextInput 
                                    style={styles.input}
                                    placeholder="Pincode"
                                    placeholderTextColor="#999"
                                    value={newPostCode}
                                    onChangeText={setNewPostCode}
                                    keyboardType="number-pad"
                                 />
                            </View>
                        </View>
                    </View>
                )}
            </View>

            {/* Order Summary */}
            <View style={styles.section}>
                <View style={styles.sectionHeader}>
                    <Ionicons name="receipt-outline" size={20} color="#333" />
                    <Text style={styles.sectionTitle}>Order Summary</Text>
                </View>

                {/* Selected Products List */}
                <View style={styles.checkoutItemList}>
                    {cartItems.map((item) => {
                        const p = item.product || item;
                        const rawImageUrl = 
                          p.primary_image || 
                          p.primaryImage || 
                          (p.images && p.images.length > 0 ? (p.images[0].image_url || p.images[0].imageUrl || p.images[0].url) : null) || 
                          p.image;

                        const imageUrl = resolveImageUrl(rawImageUrl, 150);
                        const displayPrice = p.new_price || p.newPrice || p.price;

                        return (
                          <View key={item.id || p.id} style={styles.checkoutItemCard}>
                            <Image source={{ uri: imageUrl }} style={styles.checkoutItemImage} resizeMode="cover" />
                            <View style={styles.checkoutItemDetails}>
                              <Text style={styles.checkoutItemName} numberOfLines={1}>{p.name || p.title}</Text>
                              <Text style={styles.checkoutItemPrice}>₹{displayPrice}</Text>
                            </View>
                            <View style={styles.checkoutQtyContainer}>
                              <TouchableOpacity 
                                  onPress={() => updateQuantity(item.id || p.id, item.quantity - 1)} 
                                  style={styles.checkoutQtyBtn}
                              >
                                <Ionicons name="remove" size={14} color="#333" />
                              </TouchableOpacity>
                              <Text style={styles.checkoutQtyText}>{item.quantity}</Text>
                              <TouchableOpacity 
                                  onPress={() => updateQuantity(item.id || p.id, item.quantity + 1)} 
                                  style={styles.checkoutQtyBtn}
                              >
                                <Ionicons name="add" size={14} color="#333" />
                              </TouchableOpacity>
                            </View>
                          </View>
                        );
                    })}
                </View>
                <View style={styles.divider} />

                <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Items Total</Text>
                    <Text style={styles.summaryValue}>₹{totalAmount}</Text>
                </View>
            
            {/* Courier Selection */}
            {couriers.length > 0 && (
                <View style={styles.section}>
                    <View style={styles.sectionHeader}>
                        <Ionicons name="bus-outline" size={20} color="#333" />
                        <Text style={styles.sectionTitle}>Select Courier</Text>
                    </View>
                    <TouchableOpacity 
                        style={styles.dropdownTrigger}
                        onPress={() => openModal('courier')}
                    >
                         <Text style={styles.dropdownText}>
                             {selectedCourier ? selectedCourier.name : 'Select Courier'}
                         </Text>
                         <Ionicons name="chevron-down" size={20} color="#666" />
                    </TouchableOpacity>
                </View>
            )}
                <View style={styles.summaryRow}>
                    <Text style={styles.summaryLabel}>Delivery Fee</Text>
                    <Text style={deliveryFee === 0 ? styles.freeDelivery : styles.summaryValue}>
                        {deliveryFee === 0 ? 'Free' : `₹${deliveryFee}`}
                    </Text>
                </View>
                <View style={styles.divider} />
                <View style={styles.totalRow}>
                    <Text style={styles.totalLabel}>Total to Pay</Text>
                    <Text style={styles.totalValue}>₹{totalAmount + deliveryFee}</Text>
                </View>
            </View>

            {/* Payment Method */}
            <View style={styles.section}>
                <View style={styles.sectionHeader}>
                    <Ionicons name="card-outline" size={20} color="#333" />
                    <Text style={styles.sectionTitle}>Payment Method</Text>
                </View>
                
                <TouchableOpacity 
                    style={[styles.paymentOption, paymentMethod === 'online' && styles.paymentOptionSelected]}
                    onPress={() => setPaymentMethod('online')}
                >
                    <View style={[styles.radio, paymentMethod === 'online' && styles.radioSelected]} />
                    <Text style={styles.paymentText}>
                        {activeGateway ? `${activeGateway.name.charAt(0).toUpperCase() + activeGateway.name.slice(1)}` : 'Online Payment'}
                    </Text>
                    {/* Display icons based on gateway or generic */}
                    <Image 
                        source={{uri: 'https://cdn-icons-png.flaticon.com/512/196/196578.png'}} 
                        style={{width: 30, height: 30, marginLeft: 'auto', opacity: 0.7}} 
                        resizeMode="contain"
                    />
                </TouchableOpacity>


            </View>

          </ScrollView>
        </KeyboardAvoidingView>

      {/* Pay Button */}
      <View style={styles.footer}>
        <TouchableOpacity 
          onPress={handlePayment}
          disabled={isProcessing}
        >
            <LinearGradient
                colors={isProcessing ? ['#ccc', '#bbb'] : THEME.gradients.primary as any}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.payButton}
            >
                {isProcessing ? (
                     <ActivityIndicator color="#fff" /> 
                ) : (
                    <>
                        <Text style={styles.payButtonText}>PAY ₹{totalAmount + deliveryFee}</Text>
                        <Ionicons name="lock-closed" size={18} color="#fff" style={{marginLeft: 8}} />
                    </>
                )}
            </LinearGradient>
        </TouchableOpacity>
      </View>
      <Modal
            visible={modalVisible}
            animationType="slide"
            transparent={true}
            onRequestClose={() => setModalVisible(false)}
        >
            <View style={styles.modalOverlay}>
                <View style={styles.modalContainer}>
                    <View style={styles.modalHeader}>
                        <Text style={styles.modalTitle}>Select {selectionType ? selectionType.charAt(0).toUpperCase() + selectionType.slice(1) : ''}</Text>
                        <TouchableOpacity onPress={() => setModalVisible(false)} style={styles.closeBtn}>
                            <Ionicons name="close" size={24} color="#333" />
                        </TouchableOpacity>
                    </View>
                    
                    {getLocationListData().length === 0 ? (
                        <View style={styles.emptyState}>
                            <Text style={styles.emptyText}>No options available</Text>
                        </View>
                    ) : (
                        <ScrollView style={{maxHeight: 400}}>
                            {getLocationListData().map((item: any) => (
                                <TouchableOpacity 
                                    key={item.id} 
                                    style={styles.modalItem} 
                                    onPress={() => handleSelectLocation(item, selectionType!)}
                                >
                                    <Text style={styles.modalItemText}>{item.name}</Text>
                                     {((selectionType === 'country' && selectedCountry?.id === item.id) ||
                                       (selectionType === 'state' && selectedState?.id === item.id) ||
                                       (selectionType === 'city' && selectedCity?.id === item.id) ||
                                       (selectionType === 'courier' && selectedCourier?.id === item.id)) && (
                                         <Ionicons name="checkmark" size={20} color={COLORS.primary} />
                                     )}
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    )}
                </View>
            </View>
        </Modal>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  topHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#fff',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
    paddingHorizontal: 5,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#333',
  },
  scrollContent: {
      padding: 15,
      paddingBottom: 100,
  },
  section: {
      backgroundColor: '#fff',
      padding: 15,
      borderRadius: 12,
      marginBottom: 20,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
      elevation: 2,
  },
  sectionHeader: {
      flexDirection: 'row',
      alignItems: 'center',
      marginBottom: 15,
      borderBottomWidth: 1,
      borderBottomColor: '#f0f0f0',
      paddingBottom: 10,
  },
  sectionTitle: {
      fontSize: 16,
      fontWeight: '600',
      marginLeft: 10,
      color: '#333',
  },

  summaryRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      marginBottom: 12,
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
  freeDelivery: {
      color: COLORS.success,
      fontWeight: 'bold',
  },
  divider: {
      height: 1,
      backgroundColor: '#f0f0f0',
      marginVertical: 10,
  },
  totalRow: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      marginTop: 5,
  },
  totalLabel: {
      fontWeight: '700',
      fontSize: 16,
      color: '#333',
  },
  totalValue: {
      fontWeight: '700',
      fontSize: 18,
      color: COLORS.primary,
  },
  paymentOption: {
      flexDirection: 'row',
      alignItems: 'center',
      padding: 15,
      borderWidth: 1,
      borderColor: '#eee',
      borderRadius: 8,
      marginBottom: 10,
      backgroundColor: '#fff',
  },
  paymentOptionSelected: {
      borderColor: COLORS.primary,
      backgroundColor: '#f0fdf4', // Very light green
  },
  radio: {
      width: 20,
      height: 20,
      borderRadius: 10,
      borderWidth: 2,
      borderColor: '#ccc',
      marginRight: 10,
  },
  radioSelected: {
      borderColor: COLORS.primary,
      borderWidth: 5,
  },
  paymentText: {
      fontWeight: '500',
      color: '#333',
      fontSize: 14,
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
  },
  payButton: {
      paddingVertical: 15,
      borderRadius: 30,
      flexDirection: 'row',
      justifyContent: 'center',
      alignItems: 'center',
  },
  payButtonText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '700',
      letterSpacing: 0.5,
  },
  addressList: {
      marginTop: 5,
  },
  addressCard: {
      flexDirection: 'row',
      alignItems: 'center',
      padding: 12,
      borderWidth: 1,
      borderColor: '#eee',
      borderRadius: 10,
      marginBottom: 10,
      backgroundColor: '#fff',
  },
  addressCardSelected: {
      borderColor: COLORS.primary,
      backgroundColor: '#f0fdf4',
  },
  radioContainer: {
      marginRight: 10,
  },
  addressDetails: {
      flex: 1,
  },
  addressName: {
      fontWeight: '600',
      color: '#333',
      fontSize: 14,
      marginBottom: 2,
  },
  addressText: {
      fontSize: 13,
      color: '#666',
      lineHeight: 18,
  },
  addNewToggle: {
      flexDirection: 'row',
      alignItems: 'center',
      padding: 12,
      borderWidth: 1,
      borderColor: '#eee',
      borderRadius: 10,
      borderStyle: 'dashed',
      marginTop: 5,
  },
  addNewText: {
      color: COLORS.primary,
      fontWeight: '600',
      fontSize: 14,
  },
  newAddressForm: {
      marginTop: 15,
      padding: 10,
      backgroundColor: '#fafafa',
      borderRadius: 8,
      borderWidth: 1,
      borderColor: '#eee',
  },
  row: {
      flexDirection: 'row',
      justifyContent: 'space-between',
  },
  inputWrapper: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: '#f9f9f9',
      borderRadius: 12,
      marginBottom: 15,
      paddingHorizontal: 15,
      height: 55,
      borderWidth: 1,
      borderColor: '#eee',
  },
  inputIcon: {
      marginRight: 10,
  },
  input: {
    flex: 1,
    height: '100%',
    color: '#333',
    fontSize: 16,
  },
  inputText: {
      fontSize: 16,
      color: '#333',
  },
  placeholderText: {
      color: '#999',
  },
  modalOverlay: {
      flex: 1,
      backgroundColor: 'rgba(0,0,0,0.5)',
      justifyContent: 'center',
      alignItems: 'center',
  },
  courierList: {
      marginTop: 10,
      marginBottom: 5,
  },
  courierCard: {
      flexDirection: 'row',
      alignItems: 'center',
      paddingHorizontal: 15,
      paddingVertical: 12,
      borderRadius: 12,
      borderWidth: 1,
      borderColor: '#eee',
      marginRight: 10,
      backgroundColor: '#f9f9f9',
      minWidth: 120,
  },
  courierCardSelected: {
      borderColor: COLORS.primary,
      backgroundColor: COLORS.primary + '10',
  },
  courierText: {
      fontSize: 14,
      color: '#333',
      fontWeight: '600',
  },
  courierRadio: {
      width: 16,
      height: 16,
      borderRadius: 8,
      borderWidth: 2,
      borderColor: '#bbb',
      marginRight: 10,
  },
  courierRadioSelected: {
      borderColor: COLORS.primary,
      backgroundColor: COLORS.primary,
  },
  modalContainer: {
      width: '85%',
      backgroundColor: '#fff',
      borderRadius: 12,
      padding: 20,
      maxHeight: '70%',
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.25,
      shadowRadius: 3.84,
      elevation: 5,
  },
  modalHeader: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: 15,
      borderBottomWidth: 1,
      borderBottomColor: '#eee',
      paddingBottom: 10,
  },
  modalTitle: {
      fontSize: 18,
      fontWeight: 'bold',
      color: '#333',
  },
  closeBtn: {
      padding: 5,
  },
  modalItem: {
      paddingVertical: 15,
      borderBottomWidth: 1,
      borderBottomColor: '#f0f0f0',
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
  },
  modalItemText: {
      fontSize: 16,
      color: '#333',
  },
  emptyState: {
      padding: 20,
      alignItems: 'center',
  },
  emptyText: {
      color: '#999',
      fontSize: 16,
  },
  checkoutItemList: {
      marginBottom: 15,
  },
  checkoutItemCard: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: '#f9f9f9',
      padding: 8,
      borderRadius: 8,
      marginBottom: 8,
  },
  checkoutItemImage: {
      width: 50,
      height: 50,
      borderRadius: 6,
      backgroundColor: '#eee',
  },
  checkoutItemDetails: {
      flex: 1,
      marginLeft: 10,
      justifyContent: 'center',
  },
  checkoutItemName: {
      fontSize: 14,
      fontWeight: '500',
      color: '#333',
  },
  checkoutItemPrice: {
      fontSize: 13,
      fontWeight: '600',
      color: COLORS.primary,
      marginTop: 2,
  },
  checkoutQtyContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: '#fff',
      borderRadius: 15,
      borderWidth: 1,
      borderColor: '#eee',
  },
  checkoutQtyBtn: {
      paddingHorizontal: 8,
      paddingVertical: 4,
  },
  checkoutQtyText: {
      fontSize: 12,
      fontWeight: 'bold',
      color: '#333',
      paddingHorizontal: 6,
  },
  dropdownTrigger: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      backgroundColor: '#f9f9f9',
      borderRadius: 12,
      marginTop: 10,
      marginBottom: 5,
      paddingHorizontal: 15,
      height: 55,
      borderWidth: 1,
      borderColor: '#eee',
  },
  dropdownText: {
      fontSize: 16,
      color: '#333',
  },
});

export default CheckoutScreen;
