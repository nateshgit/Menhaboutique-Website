import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, ImageBackground, StatusBar, ScrollView, KeyboardAvoidingView, Platform, SafeAreaView } from 'react-native';
import api, { setAuthToken, MainAPI } from '../services/api';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';

const SignupScreen = ({ route }: any) => {
  const { setIsAuthenticated } = route.params || {};
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [password, setPassword] = useState('');
  
  // Address State
  const [address, setAddress] = useState('');
  const [postCode, setPostCode] = useState('');
  
  // Location Selections (Object with id and name)
  const [selectedCountry, setSelectedCountry] = useState<any>(null);
  const [selectedState, setSelectedState] = useState<any>(null);
  const [selectedCity, setSelectedCity] = useState<any>(null);

  // Data Lists
  const [countries, setCountries] = useState<any[]>([]);
  const [states, setStates] = useState<any[]>([]);
  const [cities, setCities] = useState<any[]>([]);

  // Modal State
  const [modalVisible, setModalVisible] = useState(false);
  const [selectionType, setSelectionType] = useState<'country' | 'state' | 'city' | null>(null);

  const [loading, setLoading] = useState(false);
  const navigation = useNavigation<any>();

  // Fetch Countries on Mount
  React.useEffect(() => {
      fetchCountries();
  }, []);

  const fetchCountries = async () => {
      try {
          const countries = await MainAPI.getCountries();
          setCountries(countries);
          
          // Pre-select India if available
          const india = countries.find((c: any) => c.name === 'India');
          if (india) {
              handleSelect(india, 'country');
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

  const openModal = (type: 'country' | 'state' | 'city') => {
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

  const handleSelect = (item: any, type: string) => {
      if (type === 'country') {
          setSelectedCountry(item);
          setSelectedState(null);
          setSelectedCity(null);
          setStates([]);
          setCities([]);
          fetchStates(item.id);
      } else if (type === 'state') {
          setSelectedState(item);
          setSelectedCity(null);
          setCities([]);
          fetchCities(item.id);
      } else if (type === 'city') {
          setSelectedCity(item);
      }
      setModalVisible(false);
      setSelectionType(null);
  };

  const handleSignup = async () => {
    if (!name || (!email && !phoneNumber) || !password) {
      Alert.alert('Error', 'Please fill in required basic fields');
      return;
    }

    setLoading(true);
    try {
      const nameParts = name.trim().split(' ');
      const firstName = nameParts[0];
      const lastName = nameParts.slice(1).join(' ') || '';

      const { user, token } = await MainAPI.register({
        firstName,
        lastName,
        email,
        phoneNumber,
        password,
        address,
        postCode,
        country: selectedCountry?.name || '',
        state: selectedState?.name || '',
        city: selectedCity?.name || ''
      });

      if (setIsAuthenticated) {
        setIsAuthenticated(true);
      }
      
      if (Platform.OS === 'web') {
          alert('Account created successfully!');
          navigation.navigate('MainTabs');
      } else {
          Alert.alert('Success', 'Account created successfully!', [
              { text: 'OK', onPress: () => navigation.navigate('MainTabs') }
          ]);
      }

    } catch (error: any) {
      console.error(error);
      Alert.alert('Signup Failed', error.message || 'Could not create account');
    } finally {
      setLoading(false);
    }
  };
  
    // Render Modal Item
    const renderModalItem = ({ item }: any) => (
        <TouchableOpacity style={styles.modalItem} onPress={() => handleSelect(item, selectionType!)}>
            <Text style={styles.modalItemText}>{item.name}</Text>
            {(selectionType === 'country' && selectedCountry?.id === item.id) ||
             (selectionType === 'state' && selectedState?.id === item.id) ||
             (selectionType === 'city' && selectedCity?.id === item.id) ? (
                <Ionicons name="checkmark" size={20} color="#f59e0b" />
            ) : null}
        </TouchableOpacity>
    );

    const getListData = () => {
        if (selectionType === 'country') return countries;
        if (selectionType === 'state') return states;
        if (selectionType === 'city') return cities;
        return [];
    };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      <View style={styles.topBar}>
        <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Ionicons name="arrow-back" size={24} color="#555" />
        </TouchableOpacity>
        <Text style={styles.topBarBrand}>Menha Boutique</Text>
      </View>
      <KeyboardAvoidingView 
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={{ flex: 1 }}
      >
          <ScrollView contentContainerStyle={styles.formContainer} showsVerticalScrollIndicator={false}>
                    <Text style={styles.title}>Create Account</Text>
                    <Text style={styles.subtitle}>Join Menha Boutique</Text>

                    <View style={styles.inputGroup}>
                        <Text style={styles.label}>Full Name</Text>
                        <TextInput
                            style={styles.input}
                            placeholder="Full Name"
                            placeholderTextColor="#999"
                            value={name}
                            onChangeText={setName}
                        />
                    </View>

                    <View style={styles.inputGroup}>
                        <Text style={styles.label}>Email Address</Text>
                        <TextInput
                            style={styles.input}
                            placeholder="Email Address"
                            placeholderTextColor="#999"
                            value={email}
                            onChangeText={setEmail}
                            keyboardType="email-address"
                            autoCapitalize="none"
                        />
                    </View>

                    <View style={styles.inputGroup}>
                        <Text style={styles.label}>Phone Number</Text>
                        <TextInput
                            style={styles.input}
                            placeholder="Phone Number"
                            placeholderTextColor="#999"
                            value={phoneNumber}
                            onChangeText={setPhoneNumber}
                            keyboardType="phone-pad"
                        />
                    </View>

                    <View style={styles.inputGroup}>
                        <Text style={styles.label}>Password</Text>
                        <TextInput
                            style={styles.input}
                            placeholder="Password"
                            placeholderTextColor="#999"
                            value={password}
                            onChangeText={setPassword}
                            secureTextEntry
                        />
                    </View>

                    {/* Address Section */}
                    <Text style={styles.sectionHeader}>Address Details</Text>
                    
                    <View style={styles.inputGroup}>
                        <Text style={styles.label}>Address Line</Text>
                        <TextInput
                            style={styles.input}
                            placeholder="Address Line"
                            placeholderTextColor="#999"
                            value={address}
                            onChangeText={setAddress}
                        />
                    </View>

                    <View style={styles.row}>
                        <TouchableOpacity 
                            style={[styles.input, { flex: 1, marginRight: 10, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }]} 
                            onPress={() => openModal('country')}
                        >
                             <Text style={[!selectedCountry && { color: '#999' }]}>
                                 {selectedCountry ? selectedCountry.name : "Country"}
                             </Text>
                             <Ionicons name="chevron-down" size={16} color="#999" />
                        </TouchableOpacity>
                        
                        <TouchableOpacity 
                            style={[styles.input, { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }]}
                            onPress={() => openModal('state')}
                        >
                             <Text style={[!selectedState && { color: '#999' }]}>
                                 {selectedState ? selectedState.name : "State"}
                             </Text>
                             <Ionicons name="chevron-down" size={16} color="#999" />
                        </TouchableOpacity>
                    </View>

                    <View style={[styles.row, { marginTop: 15 }]}>
                        <TouchableOpacity 
                            style={[styles.input, { flex: 1, marginRight: 10, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }]}
                            onPress={() => openModal('city')}
                        >
                             <Text style={[!selectedCity && { color: '#999' }]}>
                                 {selectedCity ? selectedCity.name : "City"}
                             </Text>
                             <Ionicons name="chevron-down" size={16} color="#999" />
                        </TouchableOpacity>

                        <TextInput
                            style={[styles.input, { flex: 1 }]}
                            placeholder="Pincode"
                            placeholderTextColor="#999"
                            value={postCode}
                            onChangeText={setPostCode}
                            keyboardType="number-pad"
                        />
                    </View>

                    <TouchableOpacity 
                        onPress={handleSignup} 
                        disabled={loading}
                        style={styles.signupBtnContainer}
                    >
                        <LinearGradient
                            colors={['#004D40', '#004D40']}
                            start={{ x: 0, y: 0 }}
                            end={{ x: 1, y: 0 }}
                            style={styles.button}
                        >
                            {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Sign Up</Text>}
                        </LinearGradient>
                    </TouchableOpacity>

                    <TouchableOpacity style={styles.linkButton} onPress={() => navigation.navigate('Login')}>
                        <Text style={styles.linkText}>Already have an account? <Text style={styles.linkHighlight}>Login</Text></Text>
                    </TouchableOpacity>
                </ScrollView>
            </KeyboardAvoidingView>

            {/* Selection Modal */}
            {modalVisible && (
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContainer}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Select {selectionType ? selectionType.charAt(0).toUpperCase() + selectionType.slice(1) : ''}</Text>
                            <TouchableOpacity onPress={() => setModalVisible(false)} style={styles.closeBtn}>
                                <Ionicons name="close" size={24} color="#333" />
                            </TouchableOpacity>
                        </View>
                        {getListData().length === 0 ? (
                            <View style={styles.emptyState}>
                                <ActivityIndicator size="small" color="#f59e0b" />
                                <Text style={styles.emptyText}>Loading...</Text>
                            </View>
                        ) : (
                            <React.Fragment> {/* Use ScrollView or FlatList from React Native directly imported if not available, assumes FlatList available */}
                                {/* Using Map for inline simplicity if FlatList import issue, but FlatList is standard */}
                                {/* I'll assume FlatList is not imported and use ScrollView with map for safety in this edit block or I need to import FlatList */}
                                {/* Wait, I can't add imports easily with replace_file_content if I don't target top. */}
                                {/* I will rely on ScrollView for now as lists might not be huge, or target top to add FlatList */}
                                {/* Actually I will just replace the whole component so I can manage imports? */}
                                {/* No, replace_file_content is partial. */}
                                {/* I will use ScrollView. */}
                                <ScrollView style={{ maxHeight: 300 }}>
                                    {getListData().map((item) => (
                                        <View key={item.id}>
                                            {renderModalItem({ item })}
                                        </View>
                                    ))}
                                </ScrollView>
                            </React.Fragment>
                        )}
                    </View>
                </View>
            )}

    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#fff',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  topBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 15,
    paddingVertical: 15,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  backBtn: {
    padding: 5,
    marginRight: 10,
  },
  topBarBrand: {
    fontSize: 18,
    fontWeight: '800',
    color: '#004D40',
  },
  formContainer: {
      flexGrow: 1,
      padding: 20,
      paddingTop: 10,
      maxWidth: 500,
      alignSelf: 'center',
      width: '100%',
  },
  title: {
    fontSize: 30,
    fontWeight: '800',
    marginBottom: 5,
    color: '#00251a',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 30,
    textAlign: 'center',
  },
  inputGroup: {
    width: '100%',
    marginBottom: 15,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#00251a',
    marginBottom: 8,
  },
  input: {
    width: '100%',
    height: 55,
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 12,
    paddingHorizontal: 15,
    fontSize: 16,
    color: '#333',
    backgroundColor: '#fff',
  },
  signupBtnContainer: {
      marginTop: 20,
      shadowColor: '#004D40',
      shadowOffset: { width: 0, height: 4 },
      shadowOpacity: 0.2,
      shadowRadius: 8,
      elevation: 3,
  },
  button: {
    height: 55,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#004D40',
  },
  buttonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  linkButton: {
    marginTop: 25,
    alignItems: 'center',
  },
  linkText: {
    color: '#666',
    fontSize: 15,
  },
  linkHighlight: {
      fontWeight: '700',
      color: '#004D40',
  },
  sectionHeader: {
    fontSize: 18,
    fontWeight: '700',
    color: '#004D40',
    marginTop: 10,
    marginBottom: 15,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  modalOverlay: {
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0,0,0,0.7)',
      justifyContent: 'center',
      alignItems: 'center',
      zIndex: 100,
  },
  modalContainer: {
      width: '85%',
      backgroundColor: '#fff',
      borderRadius: 12,
      padding: 20,
      maxHeight: '70%',
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
      paddingVertical: 12,
      borderBottomWidth: 1,
      borderBottomColor: '#f9f9f9',
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
      marginTop: 10,
      color: '#666',
  }
});

export default SignupScreen;
