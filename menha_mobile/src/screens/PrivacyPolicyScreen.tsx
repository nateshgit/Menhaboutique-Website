import React from 'react';
import { View, Text, StyleSheet, SafeAreaView, Platform, StatusBar as RNStatusBar, TouchableOpacity, ScrollView } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';

const PrivacyPolicyScreen = () => {
    const navigation = useNavigation<any>();

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="#333" />
                </TouchableOpacity>
                <Text style={styles.title}>Privacy Policy</Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView contentContainerStyle={styles.content}>
                <Text style={styles.heading}>Privacy Policy for Menha Boutique</Text>
                <Text style={styles.paragraph}>
                    At Menha Boutique, accessible from our mobile application, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by Menha Boutique and how we use it.
                </Text>

                <Text style={styles.heading2}>Information We Collect</Text>
                <Text style={styles.paragraph}>
                    The personal information that you are asked to provide, and the reasons why you are asked to provide it, will be made clear to you at the point we ask you to provide your personal information.
                </Text>
                <Text style={styles.paragraph}>
                    If you contact us directly, we may receive additional information about you such as your name, email address, phone number, the contents of the message and/or attachments you may send us, and any other information you may choose to provide.
                </Text>
                <Text style={styles.paragraph}>
                    When you register for an Account, we may ask for your contact information, including items such as name, company name, address, email address, and telephone number.
                </Text>

                <Text style={styles.heading2}>How We Use Your Information</Text>
                <Text style={styles.paragraph}>
                    We use the information we collect in various ways, including to:
                </Text>
                <View style={styles.bulletList}>
                    <Text style={styles.bulletItem}>• Provide, operate, and maintain our application</Text>
                    <Text style={styles.bulletItem}>• Improve, personalize, and expand our application</Text>
                    <Text style={styles.bulletItem}>• Understand and analyze how you use our application</Text>
                    <Text style={styles.bulletItem}>• Develop new products, services, features, and functionality</Text>
                    <Text style={styles.bulletItem}>• Communicate with you, either directly or through one of our partners, including for customer service, to provide you with updates and other information relating to the application, and for marketing and promotional purposes</Text>
                    <Text style={styles.bulletItem}>• Send you emails</Text>
                    <Text style={styles.bulletItem}>• Find and prevent fraud</Text>
                </View>

                <Text style={styles.heading2}>Security</Text>
                <Text style={styles.paragraph}>
                    We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and we cannot guarantee its absolute security.
                </Text>

                <Text style={styles.heading2}>Changes to This Privacy Policy</Text>
                <Text style={styles.paragraph}>
                    We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes. We will notify you of any changes by posting the new Privacy Policy on this page.
                </Text>

                <Text style={styles.heading2}>Contact Us</Text>
                <Text style={styles.paragraph}>
                    If you have any questions or suggestions about our Privacy Policy, do not hesitate to contact us at info@menhaboutique.com.
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

export default PrivacyPolicyScreen;
