import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, StatusBar, KeyboardAvoidingView, Platform, ScrollView, SafeAreaView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';
import { MainAPI } from '../services/api';

const ForgotPasswordScreen = () => {
  const [step, setStep] = useState(1); // 1: Request OTP, 2: Reset Password with OTP
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  
  const navigation = useNavigation<any>();

  const handleRequestOtp = async () => {
    if (!email) {
      Alert.alert('Error', 'Please enter your email address');
      return;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.trim())) {
      Alert.alert('Error', 'Please enter a valid email address');
      return;
    }

    setLoading(true);
    try {
      await MainAPI.requestPasswordReset(email.trim());
      Alert.alert('Success', 'If an account exists with this email, a 6-character OTP code has been sent.');
      setStep(2);
    } catch (error: any) {
      console.error(error);
      Alert.alert('Error', error.message || 'Something went wrong. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdatePassword = async () => {
    if (!otp) {
      Alert.alert('Error', 'Please enter the OTP code');
      return;
    }
    if (otp.length < 6) {
      Alert.alert('Error', 'OTP code must be at least 6 characters');
      return;
    }
    if (!newPassword || newPassword.length < 6) {
      Alert.alert('Error', 'Password must be at least 6 characters long');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }

    setLoading(true);
    try {
      await MainAPI.updatePassword(email.trim(), otp.trim().toUpperCase(), newPassword);
      Alert.alert(
        'Success',
        'Your password has been updated successfully! Please login with your new password.',
        [{ text: 'OK', onPress: () => navigation.navigate('Login') }]
      );
    } catch (error: any) {
      console.error(error);
      Alert.alert('Error', error.message || 'Verification failed. Invalid or expired OTP.');
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
                    <Ionicons name={step === 1 ? "lock-closed" : "keypad"} size={40} color={COLORS.primary} />
                  </View>

                  <Text style={styles.title}>{step === 1 ? "Forgot Password" : "Reset Password"}</Text>
                  <Text style={styles.subtitle}>
                    {step === 1 
                      ? "Enter your registered email address to receive a verification OTP code" 
                      : "Enter the verification code sent to your email along with your new password"}
                  </Text>

                  {step === 1 ? (
                    /* Step 1: Request OTP */
                    <View style={{ width: '100%' }}>
                      <View style={styles.inputGroup}>
                          <Text style={styles.label}>Email Address</Text>
                          <TextInput
                              style={styles.input}
                              placeholder="e.g. name@example.com"
                              placeholderTextColor="#999"
                              value={email}
                              onChangeText={setEmail}
                              autoCapitalize="none"
                              keyboardType="email-address"
                          />
                      </View>

                      <TouchableOpacity 
                          onPress={handleRequestOtp} 
                          disabled={loading}
                          style={styles.loginBtnContainer}
                      >
                          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Send Reset OTP</Text>}
                      </TouchableOpacity>
                    </View>
                  ) : (
                    /* Step 2: Verification & Password Update */
                    <View style={{ width: '100%' }}>
                      <View style={styles.infoBanner}>
                        <Text style={styles.infoText}>
                          We sent a 6-character OTP code to <Text style={{ fontWeight: 'bold' }}>{email}</Text>. Please check your inbox or spam folder.
                        </Text>
                      </View>

                      <View style={styles.inputGroup}>
                          <Text style={styles.label}>One-Time Password (OTP)</Text>
                          <TextInput
                              style={[styles.input, styles.otpInput]}
                              placeholder="Enter 6-character OTP"
                              placeholderTextColor="#999"
                              value={otp}
                              onChangeText={setOtp}
                              autoCapitalize="characters"
                              maxLength={10}
                          />
                      </View>

                      <View style={styles.inputGroup}>
                          <Text style={styles.label}>New Password</Text>
                          <View style={styles.passwordWrapper}>
                            <TextInput
                                style={[styles.input, { flex: 1, borderWidth: 0 }]}
                                placeholder="Min 6 characters"
                                placeholderTextColor="#999"
                                value={newPassword}
                                onChangeText={setNewPassword}
                                secureTextEntry={!showPassword}
                                autoCapitalize="none"
                            />
                            <TouchableOpacity style={styles.eyeBtn} onPress={() => setShowPassword(!showPassword)}>
                              <Ionicons name={showPassword ? "eye-off" : "eye"} size={22} color="#777" />
                            </TouchableOpacity>
                          </View>
                      </View>

                      <View style={styles.inputGroup}>
                          <Text style={styles.label}>Confirm New Password</Text>
                          <TextInput
                              style={styles.input}
                              placeholder="Re-enter new password"
                              placeholderTextColor="#999"
                              value={confirmPassword}
                              onChangeText={setConfirmPassword}
                              secureTextEntry={!showPassword}
                              autoCapitalize="none"
                          />
                      </View>

                      <TouchableOpacity 
                          onPress={handleUpdatePassword} 
                          disabled={loading}
                          style={styles.loginBtnContainer}
                      >
                          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Update Password</Text>}
                      </TouchableOpacity>

                      <TouchableOpacity 
                          onPress={() => setStep(1)} 
                          style={styles.backLink}
                      >
                          <Text style={styles.backLinkText}>Try a different email</Text>
                      </TouchableOpacity>
                    </View>
                  )}
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
    color: COLORS.primaryDark || '#0d2b18',
    marginBottom: 5,
  },
  subtitle: {
    fontSize: 15,
    color: '#666',
    marginBottom: 30,
    textAlign: 'center',
    lineHeight: 22,
  },
  inputGroup: {
    width: '100%',
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.primaryDark || '#0d2b18',
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
  otpInput: {
    textAlign: 'center',
    letterSpacing: 4,
    fontSize: 18,
    fontWeight: 'bold',
  },
  passwordWrapper: {
    flexDirection: 'row',
    width: '100%',
    height: 55,
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 12,
    backgroundColor: '#fff',
    alignItems: 'center',
    paddingRight: 10,
  },
  eyeBtn: {
    padding: 10,
  },
  infoBanner: {
    backgroundColor: '#f0fdf4',
    padding: 15,
    borderRadius: 12,
    marginBottom: 25,
    width: '100%',
    borderWidth: 1,
    borderColor: '#bbf7d0',
  },
  infoText: {
    color: '#166534',
    fontSize: 14,
    textAlign: 'center',
    lineHeight: 20,
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
  backLink: {
    marginTop: 20,
    alignSelf: 'center',
    padding: 10,
  },
  backLinkText: {
    color: '#666',
    fontSize: 15,
    textDecorationLine: 'underline',
  },
});

export default ForgotPasswordScreen;
