import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, Platform, StatusBar as RNStatusBar, TouchableOpacity, ScrollView, TextInput } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { MainAPI } from '../services/api';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';
import ProductList from '../components/ProductList';
import Loader from '../components/Loader';

const ProductsScreen = () => {
    const navigation = useNavigation<any>();
    
    const [allProducts, setAllProducts] = useState<any[]>([]);
    const [filteredProducts, setFilteredProducts] = useState<any[]>([]);
    const [categories, setCategories] = useState<any[]>([]);
    const [activeCategoryId, setActiveCategoryId] = useState<string | null>(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            setLoading(true);
            const [productsRes, categoriesRes] = await Promise.all([
                MainAPI.fetchProducts(),
                MainAPI.fetchCategories()
            ]);
            setAllProducts(productsRes);
            setFilteredProducts(productsRes);
            setCategories(categoriesRes);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilter = (categoryId: string | null, query: string = searchQuery) => {
        setActiveCategoryId(categoryId);
        let result = allProducts;

        if (categoryId) {
            result = result.filter(p => p.category_id === categoryId || p.category?.id === categoryId);
        }

        if (query.trim()) {
            const lowerQuery = query.toLowerCase();
            result = result.filter(p => 
                (p.name && p.name.toLowerCase().includes(lowerQuery)) || 
                (p.title && p.title.toLowerCase().includes(lowerQuery)) ||
                (p.sku && p.sku.toLowerCase().includes(lowerQuery))
            );
        }

        setFilteredProducts(result);
    };

    const handleSearch = (text: string) => {
        setSearchQuery(text);
        handleFilter(activeCategoryId, text);
    };

    const handleProductPress = (item: any) => {
        navigation.navigate('ProductDetail', { productId: item.id });
    };

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Ionicons name="arrow-back" size={24} color="#333" />
                </TouchableOpacity>
                <Text style={styles.title}>All Products</Text>
                <View style={{ width: 24 }} />
            </View>

            <View style={styles.searchContainer}>
                <Ionicons name="search" size={20} color="#888" style={styles.searchIcon} />
                <TextInput 
                    style={styles.searchInput}
                    placeholder="Search products..."
                    value={searchQuery}
                    onChangeText={handleSearch}
                />
            </View>

            {/* Category Filter Row */}
            <View>
                <ScrollView 
                    horizontal 
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.categoryScroll}
                >
                    <TouchableOpacity 
                        style={[styles.catPill, activeCategoryId === null && styles.catPillActive]}
                        onPress={() => handleFilter(null)}
                    >
                        <Text style={[styles.catPillText, activeCategoryId === null && styles.catPillTextActive]}>All</Text>
                    </TouchableOpacity>
                    {categories.map(cat => (
                        <TouchableOpacity 
                            key={cat.id} 
                            style={[styles.catPill, activeCategoryId === cat.id && styles.catPillActive]}
                            onPress={() => handleFilter(cat.id)}
                        >
                            <Text style={[styles.catPillText, activeCategoryId === cat.id && styles.catPillTextActive]}>{cat.name}</Text>
                        </TouchableOpacity>
                    ))}
                </ScrollView>
            </View>

            {loading ? (
                <Loader />
            ) : filteredProducts.length === 0 ? (
                <View style={styles.center}>
                    <Ionicons name="basket-outline" size={60} color="#ccc" />
                    <Text style={styles.emptyText}>No products found</Text>
                </View>
            ) : (
                <ProductList data={filteredProducts} scrollEnabled={true} onPress={handleProductPress} />
            )}
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
    searchContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f5f5f5',
        marginHorizontal: 15,
        marginTop: 15,
        borderRadius: 10,
        paddingHorizontal: 10,
    },
    searchIcon: {
        marginRight: 8,
    },
    searchInput: {
        flex: 1,
        height: 45,
        fontSize: 16,
        color: '#333',
    },
    categoryScroll: {
        paddingHorizontal: 15,
        paddingVertical: 15,
    },
    catPill: {
        paddingHorizontal: 16,
        paddingVertical: 8,
        borderRadius: 20,
        backgroundColor: '#f0f0f0',
        marginRight: 10,
    },
    catPillActive: {
        backgroundColor: COLORS.primary,
    },
    catPillText: {
        color: '#666',
        fontSize: 14,
        fontWeight: '500',
    },
    catPillTextActive: {
        color: '#fff',
    },
    center: {
        padding: 40,
        alignItems: 'center',
    },
    emptyText: {
        marginTop: 10,
        fontSize: 16,
        color: '#888',
    },
});

export default ProductsScreen;
