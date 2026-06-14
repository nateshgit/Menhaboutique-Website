import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, SafeAreaView, StatusBar, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Modal } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { MainAPI } from '../services/api';
import { COLORS, THEME } from '../constants/theme';

const AddAddressScreen = () => {
    const navigation = useNavigation<any>();
    const route = useRoute<any>();
    const editAddress = route.params?.address;

    const [loading, setLoading] = useState(false);
    const [firstName, setFirstName] = useState(editAddress?.first_name || '');
    const [lastName, setLastName] = useState(editAddress?.last_name || '');
    const [addressLine, setAddressLine] = useState(editAddress?.address_line1 || editAddress?.address || '');
    const [addressLine2, setAddressLine2] = useState(editAddress?.address_line2 || '');
    const [phone, setPhone] = useState(editAddress?.phone_number || '');
    const [type, setType] = useState(editAddress?.address_type || 'Home');
    
    // Location States
    const [selectedCountry, setSelectedCountry] = useState<any>(null);
    const [selectedState, setSelectedState] = useState<any>(null);
    const [selectedCity, setSelectedCity] = useState<any>(null);
    const [countries, setCountries] = useState<any[]>([]);
    const [states, setStates] = useState<any[]>([]);
    const [cities, setCities] = useState<any[]>([]);
    
    const [countryName, setCountryName] = useState(editAddress?.country || 'India');
    const [stateName, setStateName] = useState(editAddress?.state || '');
    const [cityName, setCityName] = useState(editAddress?.city || '');
    const [postalCode, setPostalCode] = useState(editAddress?.postal_code || editAddress?.zip_code || '');

    const [modalVisible, setModalVisible] = useState(false);
    const [selectionType, setSelectionType] = useState<'country' | 'state' | 'city' | null>(null);

    useEffect(() => {
        fetchCountries();
    }, []);

    const fetchCountries = async () => {
        try {
            const list = await MainAPI.getCountries();
            setCountries(list);
            // If editing, we might need to find IDs to fetch states/cities
            if (editAddress) {
                const foundCountry = list.find((c: any) => c.name === editAddress.country);
                if (foundCountry) {
                    setSelectedCountry(foundCountry);
                    fetchStates(foundCountry.id);
                }
            } else {
                const india = list.find((c: any) => c.name === 'India');
                if (india) {
                    setSelectedCountry(india);
                    setCountryName(india.name);
                    fetchStates(india.id);
                }
            }
        } catch (error) {
            console.log(error);
        }
    };

    const fetchStates = async (countryId: string) => {
        try {
            const list = await MainAPI.getStates(countryId);
            setStates(list);
            if (editAddress && stateName) {
                const foundState = list.find((s: any) => s.name === stateName);
                if (foundState) {
                    setSelectedState(foundState);
                    fetchCities(foundState.id);
                }
            }
        } catch (error) {
            console.log(error);
        }
    };

    const fetchCities = async (stateId: string) => {
        try {
            const list = await MainAPI.getCities(stateId);
            setCities(list);
            if (editAddress && cityName) {
                const foundCity = list.find((c: any) => c.name === cityName);
                if (foundCity) setSelectedCity(foundCity);
            }
        } catch (error) {
            console.log(error);
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

    const handleSelectLocation = (item: any, type: string) => {
        if (type === 'country') {
            setSelectedCountry(item);
            setCountryName(item.name);
            setSelectedState(null);
            setStateName('');
            setSelectedCity(null);
            setCityName('');
            setStates([]);
            setCities([]);
            fetchStates(item.id);
        } else if (type === 'state') {
            setSelectedState(item);
            setStateName(item.name);
            setSelectedCity(null);
            setCityName('');
            setCities([]);
            fetchCities(item.id);
        } else if (type === 'city') {
            setSelectedCity(item);
            setCityName(item.name);
        }
        setModalVisible(false);
    };

    const getLocationListData = () => {
        if (selectionType === 'country') return countries;
        if (selectionType === 'state') return states;
        if (selectionType === 'city') return cities;
        return [];
    };

    const handleSave = async () => {
        if (!firstName || !addressLine || !cityName || !stateName || !postalCode || !phone) {
            Alert.alert('Error', 'Please fill all required fields');
            return;
        }

        const phoneRegex = /^[0-9]{10}$/;
        if (!phoneRegex.test(phone)) {
            Alert.alert('Error', 'Phone number must be exactly 10 digits containing only numbers');
            return;
        }

        setLoading(true);
        try {
            const userJson = await AsyncStorage.getItem('user_info');
            if (!userJson) throw new Error('User not found');
            const user = JSON.parse(userJson);

            const payload = {
                user_id: user.id,
                first_name: firstName,
                last_name: lastName,
                email: user.email, // Added email from user info
                address_line1: addressLine,
                address_line2: addressLine2,
                city: cityName,
                state: stateName,
                zip_code: postalCode,
                country: countryName,
                phone_number: phone,
                address_type: type, // Renamed from type to address_type
                is_default: editAddress?.is_default || false,
                updated_at: new Date().toISOString()
            };

            if (editAddress) {
                const res = await api.patch(`/addresses?id=eq.${editAddress.id}`, payload);
                if (res.status >= 400) throw new Error('Update failed');
                Alert.alert('Success', 'Address updated successfully');
            } else {
                const res = await api.post('/addresses', payload);
                if (res.status >= 400) throw new Error('Creation failed');
                Alert.alert('Success', 'Address added successfully');
            }
            navigation.goBack();
        } catch (error: any) {
            console.error('Save error:', error.response?.data || error.message);
            Alert.alert('Error', 'Failed to save address. Please check your data.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor="#fff" />
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="#333" />
                </TouchableOpacity>
                <Text style={styles.title}>{editAddress ? 'Edit Address' : 'Add New Address'}</Text>
                <View style={{ width: 40 }} />
            </View>

            <KeyboardAvoidingView 
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                style={{ flex: 1 }}
            >
                <ScrollView contentContainerStyle={styles.scrollContent}>
                    <View style={styles.section}>
                        <Text style={styles.sectionTitle}>Contact Details</Text>
                        <View style={styles.row}>
                            <View style={[styles.inputBox, { flex: 1, marginRight: 10 }]}>
                                <Text style={styles.label}>First Name *</Text>
                                <TextInput 
                                    style={styles.input} 
                                    value={firstName} 
                                    onChangeText={setFirstName} 
                                    placeholder="First Name"
                                />
                            </View>
                            <View style={[styles.inputBox, { flex: 1 }]}>
                                <Text style={styles.label}>Last Name</Text>
                                <TextInput 
                                    style={styles.input} 
                                    value={lastName} 
                                    onChangeText={setLastName} 
                                    placeholder="Last Name"
                                />
                            </View>
                        </View>

                        <View style={styles.inputBox}>
                            <Text style={styles.label}>Phone Number *</Text>
                            <TextInput 
                                style={styles.input} 
                                value={phone} 
                                onChangeText={setPhone} 
                                placeholder="Phone Number"
                                keyboardType="phone-pad"
                            />
                        </View>
                    </View>

                    <View style={styles.section}>
                        <Text style={styles.sectionTitle}>Address Details</Text>
                        <View style={styles.inputBox}>
                            <Text style={styles.label}>Address Line *</Text>
                            <TextInput 
                                style={styles.input} 
                                value={addressLine} 
                                onChangeText={setAddressLine} 
                                placeholder="House No, Street, Area"
                            />
                        </View>

                        <View style={styles.inputBox}>
                            <Text style={styles.label}>Address Line 2</Text>
                            <TextInput 
                                style={styles.input} 
                                value={addressLine2} 
                                onChangeText={setAddressLine2} 
                                placeholder="Apartment, suite, unit, building, floor, etc. (optional)"
                            />
                        </View>

                        <View style={styles.row}>
                            <TouchableOpacity 
                                style={[styles.inputBox, { flex: 1, marginRight: 10 }]}
                                onPress={() => openModal('country')}
                            >
                                <Text style={styles.label}>Country *</Text>
                                <View style={styles.selector}>
                                    <Text style={[styles.selectorText, !countryName && styles.placeholder]}>{countryName || 'Select Country'}</Text>
                                    <Ionicons name="chevron-down" size={16} color="#999" />
                                </View>
                            </TouchableOpacity>
                            <TouchableOpacity 
                                style={[styles.inputBox, { flex: 1 }]}
                                onPress={() => openModal('state')}
                            >
                                <Text style={styles.label}>State *</Text>
                                <View style={styles.selector}>
                                    <Text style={[styles.selectorText, !stateName && styles.placeholder]}>{stateName || 'Select State'}</Text>
                                    <Ionicons name="chevron-down" size={16} color="#999" />
                                </View>
                            </TouchableOpacity>
                        </View>

                        <View style={styles.row}>
                            <TouchableOpacity 
                                style={[styles.inputBox, { flex: 1, marginRight: 10 }]}
                                onPress={() => openModal('city')}
                            >
                                <Text style={styles.label}>City *</Text>
                                <View style={styles.selector}>
                                    <Text style={[styles.selectorText, !cityName && styles.placeholder]}>{cityName || 'Select City'}</Text>
                                    <Ionicons name="chevron-down" size={16} color="#999" />
                                </View>
                            </TouchableOpacity>
                            <View style={[styles.inputBox, { flex: 1 }]}>
                                <Text style={styles.label}>Pincode *</Text>
                                <TextInput 
                                    style={styles.input} 
                                    value={postalCode} 
                                    onChangeText={setPostalCode} 
                                    placeholder="Pincode"
                                    keyboardType="number-pad"
                                />
                            </View>
                        </View>
                    </View>

                    <View style={styles.section}>
                        <Text style={styles.sectionTitle}>Address Type</Text>
                        <View style={styles.typeRow}>
                            {['Home', 'Office', 'Other'].map((t) => (
                                <TouchableOpacity 
                                    key={t} 
                                    style={[styles.typeBtn, type === t && styles.typeBtnSelected]}
                                    onPress={() => setType(t)}
                                >
                                    <Text style={[styles.typeText, type === t && styles.typeTextSelected]}>{t}</Text>
                                </TouchableOpacity>
                            ))}
                        </View>
                    </View>
                </ScrollView>
            </KeyboardAvoidingView>

            <View style={styles.footer}>
                <TouchableOpacity onPress={handleSave} disabled={loading}>
                    <LinearGradient
                        colors={THEME.gradients.primary as any}
                        start={{ x: 0, y: 0 }}
                        end={{ x: 1, y: 0 }}
                        style={styles.saveBtn}
                    >
                        {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Save Address</Text>}
                    </LinearGradient>
                </TouchableOpacity>
            </View>

            <Modal visible={modalVisible} transparent animationType="slide">
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContainer}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Select {selectionType}</Text>
                            <TouchableOpacity onPress={() => setModalVisible(false)}>
                                <Ionicons name="close" size={24} color="#333" />
                            </TouchableOpacity>
                        </View>
                        <ScrollView style={styles.modalList}>
                            {getLocationListData().map((item: any) => (
                                <TouchableOpacity 
                                    key={item.id} 
                                    style={styles.modalItem}
                                    onPress={() => handleSelectLocation(item, selectionType!)}
                                >
                                    <Text style={styles.modalItemText}>{item.name}</Text>
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    </View>
                </View>
            </Modal>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f8f9fa',
        paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 15,
        paddingVertical: 12,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
    },
    backBtn: {
        padding: 5,
    },
    title: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
    },
    scrollContent: {
        padding: 15,
    },
    section: {
        backgroundColor: '#fff',
        borderRadius: 12,
        padding: 15,
        marginBottom: 15,
    },
    sectionTitle: {
        fontSize: 14,
        fontWeight: 'bold',
        color: '#888',
        marginBottom: 15,
        textTransform: 'uppercase',
    },
    row: {
        flexDirection: 'row',
    },
    inputBox: {
        marginBottom: 15,
    },
    label: {
        fontSize: 12,
        color: '#666',
        marginBottom: 5,
        fontWeight: '600',
    },
    input: {
        borderWidth: 1,
        borderColor: '#eee',
        borderRadius: 8,
        paddingHorizontal: 12,
        paddingVertical: 10,
        fontSize: 15,
        color: '#333',
        backgroundColor: '#fafafa',
    },
    selector: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        borderWidth: 1,
        borderColor: '#eee',
        borderRadius: 8,
        paddingHorizontal: 12,
        paddingVertical: 10,
        backgroundColor: '#fafafa',
    },
    selectorText: {
        fontSize: 15,
        color: '#333',
    },
    placeholder: {
        color: '#999',
    },
    typeRow: {
        flexDirection: 'row',
    },
    typeBtn: {
        paddingHorizontal: 20,
        paddingVertical: 8,
        borderRadius: 20,
        borderWidth: 1,
        borderColor: '#eee',
        marginRight: 10,
        backgroundColor: '#fff',
    },
    typeBtnSelected: {
        borderColor: COLORS.primary,
        backgroundColor: COLORS.primary + '10',
    },
    typeText: {
        fontSize: 14,
        color: '#666',
    },
    typeTextSelected: {
        color: COLORS.primary,
        fontWeight: 'bold',
    },
    footer: {
        padding: 15,
        backgroundColor: '#fff',
        borderTopWidth: 1,
        borderTopColor: '#eee',
    },
    saveBtn: {
        paddingVertical: 15,
        borderRadius: 30,
        alignItems: 'center',
    },
    saveBtnText: {
        color: '#fff',
        fontSize: 16,
        fontWeight: 'bold',
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContainer: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 20,
        borderTopRightRadius: 20,
        height: '60%',
        padding: 20,
    },
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 20,
    },
    modalTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
        textTransform: 'capitalize',
    },
    modalList: {
        flex: 1,
    },
    modalItem: {
        paddingVertical: 15,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
    },
    modalItemText: {
        fontSize: 16,
        color: '#333',
    },
});

export default AddAddressScreen;
