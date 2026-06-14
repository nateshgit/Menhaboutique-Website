import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, TextInput, Image, Platform, StatusBar } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';

import { useCart } from '../context/CartContext';

interface HeaderProps {
  onSearch?: (text: string) => void;
}

const Header: React.FC<HeaderProps> = ({ onSearch }) => {
  const navigation = useNavigation<any>();
  const { cartCount } = useCart();
  const [searchQuery, setSearchQuery] = React.useState('');

  const handleSearchSubmit = () => {
      if (onSearch) {
          onSearch(searchQuery);
      }
  };

  return (
    <View style={styles.mainContainer}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      
      <View style={styles.container}>
        {/* Top Row: Brand, Icons */}
        <View style={styles.topRow}>
          <View style={styles.brandContainer}>
              <Image 
                source={require('../../assets/logo.png')} 
                style={styles.logo}
                resizeMode="contain"
              />
              <Text style={styles.brandText}>Menha Boutique</Text>
          </View>
          
          <View style={styles.iconsRow}>
              <TouchableOpacity style={styles.iconButton} onPress={() => navigation.navigate('Wishlist')}>
                 <Ionicons name="heart-outline" size={24} color="#333" />
              </TouchableOpacity>
              
              <TouchableOpacity style={styles.iconButton} onPress={() => navigation.navigate('Cart')}>
                {cartCount > 0 && (
                    <View style={styles.cartBadge}>
                        <Text style={styles.cartBadgeText}>{cartCount}</Text>
                    </View>
                )}
                <Ionicons name="bag-handle-outline" size={24} color="#333" />
              </TouchableOpacity>
          </View>
        </View>
        
        {/* Search Bar */}
        <View style={styles.searchContainer}>
          <View style={styles.searchBox}>
            <TextInput 
              style={styles.input} 
              placeholder="Search for products..." 
              placeholderTextColor="#999"
              value={searchQuery}
              onChangeText={(text) => {
                  setSearchQuery(text);
                  if (onSearch) onSearch(text); // live search
              }}
              onSubmitEditing={handleSearchSubmit}
            />
            <TouchableOpacity onPress={handleSearchSubmit}>
                <Ionicons name="search-outline" size={20} color="#666" style={styles.searchIcon} />
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  mainContainer: {
    backgroundColor: '#fff',
  },

  container: {
    paddingHorizontal: 15,
    paddingBottom: 10,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
  },
  iconsRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  brandContainer: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-start',
    height: 50,
  },
  logo: {
      width: 40,
      height: 40,
      marginRight: 10,
  },
  brandText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#333',
    letterSpacing: 0.5,
  },
  iconButton: {
    padding: 5,
    marginLeft: 5,
    position: 'relative',
  },
  cartBadge: {
    position: 'absolute',
    top: 0,
    right: 0,
    backgroundColor: '#E53935',
    borderRadius: 7,
    width: 14,
    height: 14,
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1,
  },
  cartBadgeText: {
    color: '#fff',
    fontSize: 9,
    fontWeight: 'bold',
  },
  searchContainer: {
    marginTop: 5,
    marginBottom: 5,
  },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
    borderRadius: 25, // Rounder search bar
    paddingHorizontal: 15,
    height: 44,
    borderWidth: 1,
    borderColor: '#eee',
  },
  input: {
    flex: 1,
    height: '100%',
    fontSize: 14,
    color: '#333',
  },
  searchIcon: {
    marginLeft: 10,
  }
});

export default Header;
