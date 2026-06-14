import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, FlatList, SafeAreaView, Alert, Platform, StatusBar } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api, { MainAPI } from '../services/api';

const AddressScreen = () => {
  const navigation = useNavigation<any>();

  const [addresses, setAddresses] = React.useState<any[]>([]);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    fetchAddresses();
  }, []);

  const fetchAddresses = async () => {
    try {
      const data = await MainAPI.getAddresses();
      setAddresses(data);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    const performDelete = async () => {
        try {
            setLoading(true);
            console.log('Attempting to delete address:', id);
            const res = await MainAPI.deleteAddress(id);
            console.log('Delete response status:', res.status);
            
            Alert.alert('Success', 'Address deleted successfully');
            fetchAddresses();
        } catch (err: any) {
            const errorMsg = err.response?.data?.message || err.message;
            console.error('Delete error details:', err.response?.data || err.message);
            Alert.alert('Error', `Could not delete address: ${errorMsg}`);
        } finally {
            setLoading(false);
        }
    };

    if (Platform.OS === 'web') {
        if (window.confirm('Are you sure you want to delete this address?')) {
            performDelete();
        }
    } else {
        Alert.alert(
            'Delete Address',
            'Are you sure you want to delete this address?',
            [
                { text: 'Cancel', style: 'cancel' },
                { text: 'Delete', style: 'destructive', onPress: performDelete }
            ]
        );
    }
  };

  const renderItem = ({ item }: { item: any }) => (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={styles.tagContainer}>
            <Ionicons name={item.address_type === 'Home' ? 'home' : 'briefcase'} size={16} color="#555" />
            <Text style={styles.tagText}>{item.address_type || 'Address'}</Text>
        </View>
        {item.isDefault && <Text style={styles.defaultText}>DEFAULT</Text>}
      </View>
      <Text style={styles.details}>{item.address || item.address_line1}{item.address_line2 ? `, ${item.address_line2}` : ''}, {item.city}, {item.state} - {item.zip_code || item.postal_code}</Text>
      <Text style={styles.phone}>Phone: {item.phone_number}</Text>
      
      <View style={styles.actions}>
          <TouchableOpacity 
            style={styles.actionBtn}
            onPress={() => navigation.navigate('AddAddress', { address: item })}
          >
              <Text style={styles.actionText}>Edit</Text>
          </TouchableOpacity>
          <View style={styles.divider} />
          <TouchableOpacity 
            style={styles.actionBtn}
            onPress={() => handleDelete(item.id)}
          >
              <Text style={[styles.actionText, {color: '#d32f2f'}]}>Delete</Text>
          </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.topHeader}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={{padding: 10}}>
              <Ionicons name="arrow-back" size={24} color="#333" />
          </TouchableOpacity>
          <Text style={styles.title}>My Addresses</Text>
          <View style={{width: 44}} /> 
      </View>

      <FlatList 
        data={addresses}
        renderItem={renderItem}
        keyExtractor={item => item.id}
        contentContainerStyle={styles.listContent}
        ListEmptyComponent={
            <View style={styles.center}>
                <Ionicons name="location-outline" size={60} color="#ccc" />
                <Text style={styles.emptyText}>No addresses found</Text>
            </View>
        }
      />

      <View style={styles.footer}>
          <TouchableOpacity 
            style={styles.addButton}
            onPress={() => navigation.navigate('AddAddress')}
          >
              <Text style={styles.addButtonText}>+ Add New Address</Text>
          </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8f9fa',
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
  },
  title: {
      fontSize: 18,
      fontWeight: '600',
      color: '#333',
  },
  listContent: {
      padding: 15,
  },
  card: {
      backgroundColor: '#fff',
      borderRadius: 12,
      padding: 15,
      marginBottom: 15,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 1 },
      shadowOpacity: 0.05,
      shadowRadius: 2,
      elevation: 2,
  },
  header: {
      flexDirection: 'row',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: 10,
  },
  tagContainer: {
      flexDirection: 'row',
      alignItems: 'center',
      backgroundColor: '#f0f0f0',
      paddingHorizontal: 8,
      paddingVertical: 4,
      borderRadius: 4,
  },
  tagText: {
      fontSize: 12,
      fontWeight: '600',
      color: '#555',
      marginLeft: 4,
      textTransform: 'uppercase',
  },
  defaultText: {
      fontSize: 10,
      fontWeight: 'bold',
      color: '#1a472a',
      backgroundColor: '#e6f4ea',
      paddingHorizontal: 6,
      paddingVertical: 2,
      borderRadius: 4,
  },
  details: {
      fontSize: 14,
      color: '#444',
      lineHeight: 20,
      marginBottom: 5,
  },
  phone: {
      fontSize: 14,
      color: '#666',
      marginBottom: 15,
  },
  actions: {
      flexDirection: 'row',
      borderTopWidth: 1,
      borderTopColor: '#f0f0f0',
      paddingTop: 10,
  },
  actionBtn: {
      flex: 1,
      alignItems: 'center',
  },
  actionText: {
      fontSize: 14,
      fontWeight: '600',
      color: '#f97316',
  },
  divider: {
      width: 1,
      backgroundColor: '#f0f0f0',
  },
  center: {
      alignItems: 'center',
      marginTop: 50,
  },
  emptyText: {
      marginTop: 10,
      color: '#999',
      fontSize: 16,
  },
  footer: {
      padding: 15,
      backgroundColor: '#fff',
      borderTopWidth: 1,
      borderTopColor: '#eee',
  },
  addButton: {
      backgroundColor: '#f97316',
      paddingVertical: 14,
      borderRadius: 8,
      alignItems: 'center',
  },
  addButtonText: {
      color: '#fff',
      fontSize: 16,
      fontWeight: '600',
  },
});

export default AddressScreen;
