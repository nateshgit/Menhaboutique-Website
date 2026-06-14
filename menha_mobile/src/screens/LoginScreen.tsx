import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, StatusBar, KeyboardAvoidingView, Platform, ScrollView, SafeAreaView } from 'react-native';
import { MainAPI } from '../services/api';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';

const LoginScreen = ({ route }: any) => {
  const { setIsAuthenticated } = route.params || {};
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const navigation = useNavigation<any>();

  const handleLogin = async () => {
    if (!identifier || !password) {
      Alert.alert('Error', 'Please enter both email/phone and password');
      return;
    }

    setLoading(true);
    try {
      const { user, token } = await MainAPI.login(identifier, password);
      
      if (setIsAuthenticated) {
        setIsAuthenticated(true);
      }
      
      // Handle Redirect Logic
      const { redirect, cartItems, totalAmount } = route.params || {};
      if (redirect === 'Checkout') {
          navigation.replace('Checkout', { cartItems, totalAmount });
      } else if (navigation.canGoBack()) {
        navigation.goBack();
      } else {
        navigation.navigate('MainTabs'); 
      }
    } catch (error: any) {
      console.error(error);
      Alert.alert('Login Failed', error.message || 'Invalid credentials');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />
      
      {/* Top Bar */}
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
          <ScrollView 
              contentContainerStyle={{ flexGrow: 1, padding: 20 }}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
          >
              <View style={styles.formContainer}>
                  {/* Header Icon */}
                  <View style={styles.iconCircle}>
                    <Ionicons name="person" size={40} color={COLORS.primary} />
                  </View>
                  
                  <Text style={styles.title}>Welcome!</Text>
                  <Text style={styles.subtitle}>Sign in to continue</Text>

                  <View style={styles.inputGroup}>
                      <Text style={styles.label}>Email or Mobile Number</Text>
                      <TextInput
                          style={styles.input}
                          placeholder="Email or Mobile"
                          placeholderTextColor="#999"
                          value={identifier}
                          onChangeText={setIdentifier}
                          autoCapitalize="none"
                      />
                  </View>

                  <View style={styles.inputGroup}>
                      <Text style={styles.label}>Password</Text>
                      <View style={styles.passwordContainer}>
                          <TextInput
                              style={styles.passwordInput}
                              placeholder="••••••••"
                              placeholderTextColor="#999"
                              value={password}
                              onChangeText={setPassword}
                              secureTextEntry={!showPassword}
                          />
                          <TouchableOpacity onPress={() => setShowPassword(!showPassword)} style={styles.eyeIcon}>
                              <Ionicons name={showPassword ? "eye-off-outline" : "eye-outline"} size={20} color="#666" />
                          </TouchableOpacity>
                      </View>
                  </View>

                  <TouchableOpacity 
                      onPress={handleLogin} 
                      disabled={loading}
                      style={styles.loginBtnContainer}
                  >
                      {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Login</Text>}
                  </TouchableOpacity>

                  <TouchableOpacity 
                      style={styles.forgotPasswordContainer} 
                      onPress={() => navigation.navigate('ForgotPassword')}
                  >
                      <Text style={styles.forgotPasswordText}>Forgot Password?</Text>
                  </TouchableOpacity>

                  <TouchableOpacity style={styles.linkButton} onPress={() => navigation.navigate('Signup')}>
                      <Text style={styles.linkText}>Don't have an account? <Text style={styles.linkHighlight}>Sign up</Text></Text>
                  </TouchableOpacity>
              </View>
          </ScrollView>
      </KeyboardAvoidingView>
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
    color: COLORS.primary,
  },
  formContainer: {

    paddingTop: 20,
    alignItems: 'center',
    maxWidth: 500,
    width: '100%',
    alignSelf: 'center',
  },
  iconCircle: {
    width: 80,
    height: 80,
    backgroundColor: '#f5f5f5',
    borderRadius: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  title: {
    fontSize: 30,
    fontWeight: '800',
    color: COLORS.primaryDark || '#00251a',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 40,
  },
  inputGroup: {
    width: '100%',
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.primaryDark || '#00251a',
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
  passwordContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 12,
    backgroundColor: '#fff',
    height: 55,
  },
  passwordInput: {
    flex: 1,
    height: '100%',
    paddingHorizontal: 15,
    fontSize: 16,
    color: '#333',
  },
  eyeIcon: {
    padding: 15,
  },
  loginBtnContainer: {
    width: '100%',
    height: 55,
    backgroundColor: COLORS.primary,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 10,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 3,
  },
  buttonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  forgotPasswordContainer: {
    marginTop: 20,
    alignItems: 'center',
  },
  forgotPasswordText: {
    color: '#666',
    fontSize: 14,
  },
  linkButton: {
    marginTop: 25,
    padding: 10,
  },
  linkText: {
    color: '#666',
    fontSize: 15,
  },
  linkHighlight: {
    color: COLORS.primary,
    fontWeight: '700',
  },
});

export default LoginScreen;
