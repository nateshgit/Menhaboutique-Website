import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image, ScrollView, Switch, StatusBar, SafeAreaView, Alert, Platform, Modal, TextInput, ActivityIndicator } from 'react-native';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { COLORS, THEME } from '../constants/theme';
import * as ImagePicker from 'expo-image-picker';
import { MainAPI } from '../services/api';
import { resolveImageUrl } from '../utils/imageUtils';

const ProfileScreen = () => {
  const navigation = useNavigation<any>();

  const [user, setUser] = useState({
      name: 'Guest User',
      email: 'guest@example.com',
      firstName: '',
      lastName: '',
      phoneNumber: '',
      avatar: ''
  });

  const [isEditModalVisible, setIsEditModalVisible] = useState(false);
  const [editFirstName, setEditFirstName] = useState('');
  const [editLastName, setEditLastName] = useState('');
  const [editPhoneNumber, setEditPhoneNumber] = useState('');
  const [editAvatarBase64, setEditAvatarBase64] = useState<string | null>(null);
  const [editAvatarUri, setEditAvatarUri] = useState<string | null>(null);
  const [isUpdating, setIsUpdating] = useState(false);

  useFocusEffect(
    React.useCallback(() => {
      loadUser();
    }, [])
  );

  const loadUser = async () => {
    try {
      const userInfo = await AsyncStorage.getItem('user_info');
      if (userInfo) {
          const parsedUser = JSON.parse(userInfo);
          const firstName = parsedUser.first_name || parsedUser.firstName || '';
          const lastName = parsedUser.last_name || parsedUser.lastName || '';
          const name = parsedUser.name || (firstName + ' ' + lastName).trim() || 'User';
          setUser({
              name: name,
              email: parsedUser.email || '',
              firstName: firstName,
              lastName: lastName,
              phoneNumber: parsedUser.phone_number || parsedUser.phoneNumber || '',
              avatar: parsedUser.avatar_url || parsedUser.avatarUrl || ''
          });
      } else {
          setUser({
              name: 'Guest User',
              email: 'guest@example.com',
              firstName: '',
              lastName: '',
              phoneNumber: '',
              avatar: ''
          });
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleLogout = async () => {
    await AsyncStorage.removeItem('auth_token');
    await AsyncStorage.removeItem('user_info');
    navigation.reset({
      index: 0,
      routes: [{ name: 'Login' }],
    });
  };

  const handlePickImage = async () => {
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (status !== 'granted') {
          Alert.alert('Permission Required', 'We need camera roll permissions to pick a profile picture!');
          return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          allowsEditing: true,
          aspect: [1, 1],
          quality: 0.7,
          base64: true
      });

      if (!result.canceled && result.assets && result.assets.length > 0) {
          const asset = result.assets[0];
          setEditAvatarUri(asset.uri);
          if (asset.base64) {
              let ext = 'png';
              if (asset.uri.endsWith('.jpg') || asset.uri.endsWith('.jpeg')) {
                  ext = 'jpeg';
              } else if (asset.uri.endsWith('.webp')) {
                  ext = 'webp';
              }
              setEditAvatarBase64(`data:image/${ext};base64,${asset.base64}`);
          }
      }
  };

  const handleSaveChanges = async () => {
      if (!editFirstName.trim()) {
          Alert.alert('Required', 'First name is required');
          return;
      }
      setIsUpdating(true);
      try {
          const payload = {
              firstName: editFirstName,
              lastName: editLastName,
              phoneNumber: editPhoneNumber,
              avatarData: editAvatarBase64
          };

          const response = await MainAPI.updateProfile(payload);
          if (response.success && response.user) {
              const updatedUser = response.user;
              await AsyncStorage.setItem('user_info', JSON.stringify(updatedUser));
              setUser({
                  name: ((updatedUser.first_name || '') + ' ' + (updatedUser.last_name || '')).trim() || 'User',
                  email: updatedUser.email || '',
                  firstName: updatedUser.first_name || '',
                  lastName: updatedUser.last_name || '',
                  phoneNumber: updatedUser.phone_number || '',
                  avatar: updatedUser.avatar_url || ''
              });
              setIsEditModalVisible(false);
              Alert.alert('Success', 'Profile updated successfully!');
          } else {
              throw new Error(response.error || 'Failed to update profile');
          }
      } catch (error: any) {
          Alert.alert('Error', error.message || 'An error occurred while saving your changes.');
      } finally {
          setIsUpdating(false);
      }
  };

  const openEditModal = () => {
      setEditFirstName(user.firstName);
      setEditLastName(user.lastName);
      setEditPhoneNumber(user.phoneNumber);
      const hasAvatar = user.avatar && user.avatar !== 'null' && user.avatar !== 'undefined';
      setEditAvatarUri(hasAvatar ? resolveImageUrl(user.avatar) : null);
      setEditAvatarBase64(null);
      setIsEditModalVisible(true);
  };

  const menuItems = [
    { title: 'My Orders', icon: 'cube-outline', screen: 'Orders' },
    { title: 'Shipping Addresses', icon: 'location-outline', screen: 'Address' },
    { title: 'My Wishlist', icon: 'heart-outline', screen: 'Wishlist' },
    { title: 'Contact Us', icon: 'call-outline', screen: 'Contact' },
    { title: 'Privacy Policy', icon: 'shield-checkmark-outline', screen: 'PrivacyPolicy' },
    { title: 'Terms & Conditions', icon: 'document-text-outline', screen: 'Terms' },
  ];

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      {/* Header Profile Section */}
      <View style={styles.header}>
        <View style={styles.avatarContainer}>
          {user.avatar && user.avatar !== 'null' && user.avatar !== 'undefined' ? (
              <Image source={{ uri: resolveImageUrl(user.avatar) }} style={styles.avatar} />
          ) : (
              <View style={styles.initialsAvatar}>
                  <Text style={styles.initialsText}>{user.email ? user.email.charAt(0).toUpperCase() : 'U'}</Text>
              </View>
          )}
        </View>
        <Text style={styles.name}>{user.name}</Text>
        <Text style={styles.email}>{user.email}</Text>
        
        <TouchableOpacity 
          style={styles.editButton}
          onPress={() => {
              if (user.email === 'guest@example.com') {
                  navigation.navigate('Login');
              } else {
                  openEditModal();
              }
          }}
        >
            <LinearGradient
                colors={THEME.gradients.primary as any}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.editBtnGradient}
            >
                <Text style={styles.editButtonText}>Edit Profile</Text>
            </LinearGradient>
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.menuContainer} showsVerticalScrollIndicator={false}>
        {menuItems.map((item, index) => (
          <TouchableOpacity 
            key={index} 
            style={styles.menuItem} 
            onPress={() => {
                if (item.screen) {
                    navigation.navigate(item.screen);
                } else {
                    Alert.alert('Coming Soon', `${item.title} feature is coming soon!`);
                }
            }}
          >
            <View style={styles.iconBox}>
                <Ionicons name={item.icon as any} size={22} color={COLORS.primary} />
            </View>
            <Text style={styles.menuTitle}>{item.title}</Text>
            <Ionicons name="chevron-forward" size={18} color="#ccc" />
          </TouchableOpacity>
        ))}

        <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
            <View style={[styles.iconBox, styles.logoutIconBox]}>
                <Ionicons name="log-out-outline" size={22} color={COLORS.danger} />
            </View>
            <Text style={[styles.menuTitle, styles.logoutText]}>Log Out</Text>
        </TouchableOpacity>
        
        <View style={styles.versionContainer}>
            <Text style={styles.versionText}>App Version 1.0.0</Text>
        </View>
      </ScrollView>

      <Modal
        visible={isEditModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setIsEditModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContainer}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Edit Profile</Text>
              <TouchableOpacity onPress={() => setIsEditModalVisible(false)} style={styles.closeBtn}>
                <Ionicons name="close" size={24} color="#333" />
              </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={styles.modalScrollContent}>
              {/* Avatar Selector */}
              <TouchableOpacity style={styles.modalAvatarContainer} onPress={handlePickImage} activeOpacity={0.8}>
                {editAvatarUri ? (
                    <Image source={{ uri: editAvatarUri }} style={styles.modalAvatar} />
                ) : (
                    <View style={[styles.initialsAvatar, { width: 80, height: 80, borderRadius: 40 }]}>
                        <Text style={[styles.initialsText, { fontSize: 32 }]}>{user.email ? user.email.charAt(0).toUpperCase() : 'U'}</Text>
                    </View>
                )}
                <View style={styles.cameraIconBadge}>
                    <Ionicons name="camera" size={12} color="#fff" />
                </View>
              </TouchableOpacity>
              <Text style={styles.avatarHintText}>Tap to change photo</Text>

              {/* Form inputs */}
              <View style={styles.formGroup}>
                <Text style={styles.inputLabel}>First Name</Text>
                <TextInput
                  style={styles.modalInput}
                  value={editFirstName}
                  onChangeText={setEditFirstName}
                  placeholder="Enter First Name"
                  placeholderTextColor="#bbb"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.inputLabel}>Last Name</Text>
                <TextInput
                  style={styles.modalInput}
                  value={editLastName}
                  onChangeText={setEditLastName}
                  placeholder="Enter Last Name"
                  placeholderTextColor="#bbb"
                />
              </View>

              <View style={styles.formGroup}>
                <Text style={styles.inputLabel}>Phone Number</Text>
                <TextInput
                  style={styles.modalInput}
                  value={editPhoneNumber}
                  onChangeText={setEditPhoneNumber}
                  placeholder="Enter Phone Number"
                  placeholderTextColor="#bbb"
                  keyboardType="phone-pad"
                />
              </View>

              {/* Action Buttons */}
              <View style={styles.modalActions}>
                <TouchableOpacity 
                  style={[styles.modalBtn, styles.cancelBtn]} 
                  onPress={() => setIsEditModalVisible(false)}
                >
                  <Text style={styles.cancelBtnText}>Cancel</Text>
                </TouchableOpacity>

                <TouchableOpacity 
                  style={[styles.modalBtn, styles.saveBtn, isUpdating && { opacity: 0.7 }]} 
                  onPress={handleSaveChanges}
                  disabled={isUpdating}
                >
                  {isUpdating ? (
                      <ActivityIndicator color="#fff" size="small" />
                  ) : (
                      <Text style={styles.saveBtnText}>Save</Text>
                  )}
                </TouchableOpacity>
              </View>
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
    backgroundColor: '#fff',
    paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0,
  },
  header: {
    alignItems: 'center',
    paddingVertical: 30,
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  avatarContainer: {
    width: 90,
    height: 90,
    borderRadius: 45,
    backgroundColor: '#f5f5f5',
    marginBottom: 15,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#fff',
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  avatar: {
    width: '100%',
    height: '100%',
    borderRadius: 45,
  },
  name: {
    fontSize: 20,
    fontWeight: '700',
    color: '#333',
    marginBottom: 5,
  },
  email: {
    fontSize: 14,
    color: '#888',
    marginBottom: 20,
  },
  editButton: {
    borderRadius: 20,
    overflow: 'hidden',
  },
  editBtnGradient: {
    paddingHorizontal: 25,
    paddingVertical: 10,
    borderRadius: 20,
  },
  editButtonText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '600',
    letterSpacing: 0.5,
  },
  menuContainer: {
    flex: 1,
    paddingTop: 10,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 18,
    paddingHorizontal: 25,
    backgroundColor: '#fff',
    marginBottom: 1,
  },
  iconBox: {
      width: 40,
      height: 40,
      borderRadius: 12,
      backgroundColor: '#e6f4ea',
      justifyContent: 'center',
      alignItems: 'center',
      marginRight: 20,
  },
  menuTitle: {
    flex: 1,
    fontSize: 16,
    color: '#333',
    fontWeight: '500',
  },
  logoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 18,
    paddingHorizontal: 25,
    marginTop: 20,
  },
  logoutIconBox: {
      backgroundColor: '#ffebee',
  },
  logoutText: {
    color: COLORS.danger,
    fontWeight: '600',
  },
  versionContainer: {
      padding: 30,
      alignItems: 'center',
  },
  versionText: {
      color: '#ccc',
      fontSize: 12,
  },
  initialsAvatar: {
    width: '100%',
    height: '100%',
    borderRadius: 45,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
  },
  initialsText: {
    color: '#fff',
    fontSize: 36,
    fontWeight: 'bold',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContainer: {
    width: '90%',
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 20,
    maxHeight: '85%',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 5,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
    paddingBottom: 10,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#333',
  },
  closeBtn: {
    padding: 5,
  },
  modalScrollContent: {
    alignItems: 'center',
    paddingBottom: 10,
    width: '100%',
  },
  modalAvatarContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#f0f0f0',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
    position: 'relative',
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  modalAvatar: {
    width: '100%',
    height: '100%',
    borderRadius: 40,
  },
  cameraIconBadge: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    backgroundColor: COLORS.primary,
    width: 24,
    height: 24,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 2,
    borderColor: '#fff',
  },
  avatarHintText: {
    fontSize: 12,
    color: '#888',
    marginBottom: 20,
  },
  formGroup: {
    width: '100%',
    marginBottom: 15,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: '#666',
    marginBottom: 6,
    paddingLeft: 4,
  },
  modalInput: {
    width: '100%',
    backgroundColor: '#f9f9f9',
    borderRadius: 12,
    paddingHorizontal: 15,
    height: 50,
    borderWidth: 1,
    borderColor: '#eee',
    fontSize: 15,
    color: '#333',
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: '100%',
    marginTop: 25,
    marginBottom: 10,
  },
  modalBtn: {
    flex: 1,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
  },
  cancelBtn: {
    backgroundColor: '#f5f5f5',
    marginRight: 10,
  },
  cancelBtnText: {
    color: '#666',
    fontSize: 14,
    fontWeight: '600',
  },
  saveBtn: {
    backgroundColor: COLORS.primary,
    marginLeft: 10,
  },
  saveBtnText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '600',
  },
});

export default ProfileScreen;
