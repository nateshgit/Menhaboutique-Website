import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, Platform, StatusBar as RNStatusBar, TouchableOpacity, ScrollView, Linking } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';

const ContactScreen = () => {
    const navigation = useNavigation<any>();

    const contactInfo = [
        { icon: 'location', title: 'Address', detail: 'No 9. East Street, Madhampatti, Coimbatore 641010', action: null },
        { icon: 'call', title: 'Call Us', detail: '9500600525', action: () => Linking.openURL('tel:9500600525') },
        { icon: 'logo-whatsapp', title: 'WhatsApp', detail: '8973355559', action: () => Linking.openURL('whatsapp://send?phone=+918973355559') },
        { icon: 'headset', title: 'Customer Care', detail: '7708853119', action: () => Linking.openURL('tel:7708853119') },
        { icon: 'mail', title: 'Email', detail: 'info@menhaboutique.com', action: () => Linking.openURL('mailto:info@menhaboutique.com') },
    ];

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="#333" />
                </TouchableOpacity>
                <Text style={styles.title}>Contact Us</Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView contentContainerStyle={styles.content}>
                <View style={styles.headerSection}>
                    <Ionicons name="chatbubbles-outline" size={60} color={COLORS.primary} />
                    <Text style={styles.headerText}>Get in Touch</Text>
                    <Text style={styles.subHeaderText}>We'd love to hear from you. Here's how you can reach us.</Text>
                </View>

                <View style={styles.infoContainer}>
                    {contactInfo.map((info, index) => (
                        <TouchableOpacity 
                            key={index} 
                            style={styles.infoCard} 
                            onPress={info.action ? info.action : undefined}
                            activeOpacity={info.action ? 0.7 : 1}
                        >
                            <View style={styles.iconContainer}>
                                <Ionicons name={info.icon as any} size={24} color={COLORS.primary} />
                            </View>
                            <View style={styles.textContainer}>
                                <Text style={styles.infoTitle}>{info.title}</Text>
                                <Text style={styles.infoDetail}>{info.detail}</Text>
                            </View>
                            {info.action && (
                                <Ionicons name="chevron-forward" size={20} color="#ccc" />
                            )}
                        </TouchableOpacity>
                    ))}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    safeArea: {
        flex: 1,
        backgroundColor: '#fff',
        paddingTop: Platform.OS === 'android' ? RNStatusBar.currentHeight : 0,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 15,
        paddingVertical: 12,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
        backgroundColor: '#fff',
    },
    backBtn: {
        padding: 5,
    },
    title: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
    },
    content: {
        padding: 20,
    },
    headerSection: {
        alignItems: 'center',
        marginBottom: 30,
        marginTop: 10,
    },
    headerText: {
        fontSize: 24,
        fontWeight: 'bold',
        color: '#333',
        marginTop: 10,
    },
    subHeaderText: {
        fontSize: 14,
        color: '#666',
        textAlign: 'center',
        marginTop: 8,
        paddingHorizontal: 20,
        lineHeight: 20,
    },
    infoContainer: {
        backgroundColor: '#fff',
        borderRadius: 12,
        borderWidth: 1,
        borderColor: '#f0f0f0',
        overflow: 'hidden',
    },
    infoCard: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
    },
    iconContainer: {
        width: 44,
        height: 44,
        borderRadius: 22,
        backgroundColor: 'rgba(0, 77, 64, 0.1)',
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 15,
    },
    textContainer: {
        flex: 1,
    },
    infoTitle: {
        fontSize: 13,
        color: '#888',
        marginBottom: 2,
    },
    infoDetail: {
        fontSize: 15,
        color: '#333',
        fontWeight: '500',
    },
});

export default ContactScreen;
