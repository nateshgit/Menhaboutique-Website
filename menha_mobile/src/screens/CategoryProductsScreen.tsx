import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, StatusBar, TouchableOpacity, Platform, ScrollView } from 'react-native';
import { useRoute, useNavigation } from '@react-navigation/native';
import { MainAPI } from '../services/api';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';
import ProductList from '../components/ProductList';
import Loader from '../components/Loader';

const CategoryProductsScreen = () => {
    const route = useRoute<any>();
    const navigation = useNavigation<any>();
    const { categoryId, categoryName } = route.params || {};
    
    const [products, setProducts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchCategoryProducts();
    }, [categoryId]);

    const fetchCategoryProducts = async () => {
        try {
            setLoading(true);
            const allProducts = await MainAPI.fetchProducts();
            // Filter by category_id or category name
            const filtered = allProducts.filter((p: any) => 
                p.category_id === categoryId || 
                p.category?.id === categoryId
            );
            setProducts(filtered);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleProductPress = (item: any) => {
        navigation.navigate('ProductDetail', { productId: item.id });
    };

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor="#fff" />
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="#333" />
                </TouchableOpacity>
                <Text style={styles.title}>{categoryName || 'Category'}</Text>
                <View style={{ width: 24 }} />
            </View>

            {loading ? (
                <Loader fullScreen />
            ) : products.length === 0 ? (
                <View style={styles.center}>
                    <Ionicons name="basket-outline" size={60} color="#ccc" />
                    <Text style={styles.emptyText}>No products found in this category</Text>
                </View>
            ) : (
                <ProductList data={products} scrollEnabled={true} onPress={handleProductPress} />
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
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 15,
        paddingVertical: 12,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
    },
    backBtn: {
        padding: 5,
    },
    title: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
    },
    center: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        padding: 20,
    },
    emptyText: {
        marginTop: 10,
        fontSize: 16,
        color: '#888',
        textAlign: 'center',
    },
});

export default CategoryProductsScreen;
