import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, Platform, StatusBar as RNStatusBar, TouchableOpacity, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';

const TermsScreen = () => {
    const navigation = useNavigation<any>();

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="#333" />
                </TouchableOpacity>
                <Text style={styles.title}>Terms & Conditions</Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.heading}>Terms and Conditions</Text>
                <Text style={styles.paragraph}>
                    Welcome to Menha Boutique!
                </Text>
                <Text style={styles.paragraph}>
                    These terms and conditions outline the rules and regulations for the use of Menha Boutique's Application.
                </Text>
                <Text style={styles.paragraph}>
                    By accessing this application we assume you accept these terms and conditions. Do not continue to use Menha Boutique if you do not agree to take all of the terms and conditions stated on this page.
                </Text>

                <Text style={styles.heading2}>License</Text>
                <Text style={styles.paragraph}>
                    Unless otherwise stated, Menha Boutique and/or its licensors own the intellectual property rights for all material on Menha Boutique. All intellectual property rights are reserved. You may access this from Menha Boutique for your own personal use subjected to restrictions set in these terms and conditions.
                </Text>

                <Text style={styles.heading2}>You must not:</Text>
                <View style={styles.bulletList}>
                    <Text style={styles.bulletItem}>• Republish material from Menha Boutique</Text>
                    <Text style={styles.bulletItem}>• Sell, rent or sub-license material from Menha Boutique</Text>
                    <Text style={styles.bulletItem}>• Reproduce, duplicate or copy material from Menha Boutique</Text>
                    <Text style={styles.bulletItem}>• Redistribute content from Menha Boutique</Text>
                </View>

                <Text style={styles.heading2}>User Accounts</Text>
                <Text style={styles.paragraph}>
                    When you create an account with us, you must provide us information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service.
                </Text>

                <Text style={styles.heading2}>Purchases</Text>
                <Text style={styles.paragraph}>
                    If you wish to purchase any product or service made available through the Application ("Purchase"), you may be asked to supply certain information relevant to your Purchase including, without limitation, your credit card number, the expiration date of your credit card, your billing address, and your shipping information.
                </Text>

                <Text style={styles.heading2}>Changes</Text>
                <Text style={styles.paragraph}>
                    We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material we will try to provide at least 30 days notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.
                </Text>

                <Text style={styles.heading2}>Contact Us</Text>
                <Text style={styles.paragraph}>
                    If you have any questions about these Terms, please contact us.
                </Text>
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
        paddingBottom: 40,
    },
    heading: {
        fontSize: 22,
        fontWeight: 'bold',
        color: '#333',
        marginBottom: 15,
    },
    heading2: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
        marginTop: 20,
        marginBottom: 10,
    },
    paragraph: {
        fontSize: 15,
        lineHeight: 24,
        color: '#555',
        marginBottom: 12,
    },
    bulletList: {
        marginLeft: 10,
        marginBottom: 15,
    },
    bulletItem: {
        fontSize: 15,
        lineHeight: 24,
        color: '#555',
        marginBottom: 6,
    },
});

export default TermsScreen;
