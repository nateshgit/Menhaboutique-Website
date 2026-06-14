import React, { useEffect, useState, useCallback } from 'react';
import {
  View, ScrollView, RefreshControl, StyleSheet, SafeAreaView,
  Platform, StatusBar as RNStatusBar, Text, FlatList
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../constants/theme';
import { resolveImageUrl, prefetchImages, getProductImageUrls } from '../utils/imageUtils';
import { MainAPI } from '../services/api';

import Header from '../components/Header';
import Banner from '../components/Banner';
import CategoryRow from '../components/CategoryRow';
import ProductList from '../components/ProductList';
import ProductCard from '../components/ProductCard';
import CachedImage from '../components/CachedImage';
import SectionHeader from '../components/SectionHeader';
import Loader from '../components/Loader';

interface Product {
  id: string;
  name: string;
  title?: string;
  price: number;
  newPrice?: number;
  oldPrice?: number;
  description: string;
  image?: string;
  images?: { url: string }[];
  rating?: number;
  reviews?: number;
  sale?: string;
  category?: string;
  weight?: string;
  location?: string;
  category_name?: string;
  categories?: any;
}

interface Category {
  id: string;
  name: string;
  image?: string;
}

const HomeScreen = () => {
  const navigation = useNavigation<any>();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [banners, setBanners] = useState<any[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [filteredProducts, setFilteredProducts] = useState<Product[]>([]);
  const [bestSelling, setBestSelling] = useState<Product[]>([]);
  const [homeReviews, setHomeReviews] = useState<any[]>([]);
  const [searchQuery, setSearchQuery] = useState('');

  const fetchData = async () => {
    try {
      // ── Fetch all data in parallel ──────────────────────────────────────────
      const [bannersData, categoriesList, productsData] = await Promise.all([
        MainAPI.fetchBanners(),
        MainAPI.fetchCategories(),
        MainAPI.fetchProducts(),
      ]);

      // ── Map banners ────────────────────────────────────────────────────────
      const mappedBanners = bannersData.map((b: any) => ({
        id: b.id,
        image_url: b.image_url || b.imageUrl || b.image,
        image: b.image_url || b.imageUrl || b.image,
        name: b.title || '',
        link: b.link || b.link_url,
        discount: b.discount || '',
      }));
      setBanners(mappedBanners);
      setCategories(categoriesList);
      setProducts(productsData);
      setFilteredProducts(productsData);
      setBestSelling(productsData.slice(0, 6));

      // ── Aggressive parallel prefetch: banners + categories + first 20 products
      //    All fired immediately so they're cached before the user sees any image ─
      const bannerUrls = mappedBanners.map((b: any) =>
        resolveImageUrl(b.image_url || b.image, 800),
      );
      const categoryUrls = categoriesList.map((c: any) =>
        resolveImageUrl(c.image, 150),
      );
      const firstProductUrls = getProductImageUrls(productsData.slice(0, 20), 300);

      // Fire all in one shot — they run in parallel natively
      prefetchImages([...bannerUrls, ...categoryUrls, ...firstProductUrls], 15);

      // ── Fetch home reviews separately (non-blocking) ───────────────────────
      MainAPI.fetchHomeReviews()
        .then((reviewsData: any) => setHomeReviews(reviewsData))
        .catch(() => {});
    } catch (error) {
      console.error('Error fetching data:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const onRefresh = useCallback(() => {
    setRefreshing(true);
    fetchData();
  }, []);

  const handleProductPress = (item: Product) => {
    navigation.navigate('ProductDetail', { productId: item.id });
  };

  const handleCategoryPress = (item: Category) => {
    navigation.navigate('CategoryProducts', { categoryId: item.id, categoryName: item.name });
  };

  const handleSearch = (query: string) => {
    setSearchQuery(query);
    if (!query.trim()) {
      setFilteredProducts(products);
    } else {
      const lowerQuery = query.toLowerCase();
      setFilteredProducts(
        products.filter(
          (p) =>
            p.name?.toLowerCase().includes(lowerQuery) ||
            p.title?.toLowerCase().includes(lowerQuery) ||
            p.description?.toLowerCase().includes(lowerQuery) ||
            p.category_name?.toLowerCase().includes(lowerQuery) ||
            (p.categories?.name && p.categories.name.toLowerCase().includes(lowerQuery)),
        ),
      );
    }
  };

  const renderHeaderComponent = () => {
    if (searchQuery.trim() !== '') {
      return (
        <SectionHeader
          title={`Search Results (${filteredProducts.length})`}
        />
      );
    }

    return (
      <View>
        {/* Banners */}
        {banners.length > 0 && (
          <Banner data={banners} onPress={() => {}} />
        )}

        {/* Categories */}
        {categories.length > 0 && (
          <View style={{ marginBottom: 10 }}>
            <SectionHeader
              title="Shop By Category"
              onSeeAll={() => navigation.navigate('Category')}
            />
            <CategoryRow data={categories} onPress={handleCategoryPress} />
          </View>
        )}

        {/* Best Selling */}
        {bestSelling.length > 0 && (
          <View style={{ marginBottom: 10 }}>
            <SectionHeader
              title="Best Selling"
              onSeeAll={() => navigation.navigate('Products')}
            />
            <ProductList data={bestSelling} scrollEnabled={false} onPress={handleProductPress} />
          </View>
        )}

        {/* All Items Header */}
        <SectionHeader
          title="All Items"
          onSeeAll={() => navigation.navigate('Products')}
        />
      </View>
    );
  };

  const renderFooterComponent = () => {
    if (searchQuery.trim() !== '') return null;
    if (homeReviews.length === 0) return null;

    return (
      <View style={styles.reviewsSection}>
        <SectionHeader title="Happy Customers" />
        <Text style={styles.reviewsSubtitle}>Real stories from our happy community</Text>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.reviewsScrollContent}
        >
          {homeReviews.map((rev) => {
            const firstLetter = rev.reviewer_name
              ? rev.reviewer_name.charAt(0).toUpperCase()
              : 'C';
            const rating = parseInt(rev.rating) || 5;
            const stars = Array.from({ length: 5 }, (_, i) => i < rating);
            return (
              <View key={rev.id} style={styles.reviewCard}>
                {rev.media_url && rev.media_type === 'image' && (
                  <CachedImage 
                    source={{ uri: rev.media_url }} 
                    style={styles.reviewMedia as any} 
                    containerStyle={styles.reviewMediaContainer}
                    resizeMode="cover"
                  />
                )}
                <View style={styles.reviewBody}>
                  <View style={styles.starsContainer}>
                    {stars.map((filled, idx) => (
                      <Ionicons
                        key={idx}
                        name={filled ? 'star' : 'star-outline'}
                        size={16}
                        color={filled ? '#ffb300' : '#ccc'}
                        style={{ marginRight: 2 }}
                      />
                    ))}
                  </View>
                  {rev.review_text ? (
                    <Text style={styles.reviewText} numberOfLines={4}>
                      "{rev.review_text}"
                    </Text>
                  ) : null}
                  <View style={styles.reviewAuthor}>
                    <View style={styles.reviewAvatar}>
                      <Text style={styles.reviewAvatarText}>{firstLetter}</Text>
                    </View>
                    <Text style={styles.reviewerName}>{rev.reviewer_name}</Text>
                  </View>
                </View>
              </View>
            );
          })}
        </ScrollView>
      </View>
    );
  };

  if (loading) {
    return <Loader fullScreen />;
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.container}>
        <Header onSearch={handleSearch} />
        
        <FlatList
          data={filteredProducts}
          renderItem={({ item }) => <ProductCard item={item} onPress={handleProductPress} />}
          keyExtractor={(item, index) => (item.id || index).toString()}
          numColumns={2}
          columnWrapperStyle={styles.row}
          contentContainerStyle={styles.scrollContent}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          ListHeaderComponent={renderHeaderComponent}
          ListFooterComponent={renderFooterComponent}
          initialNumToRender={6}
          maxToRenderPerBatch={4}
          windowSize={5}
          removeClippedSubviews={Platform.OS === 'android'}
        />
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#fff',
    paddingTop: Platform.OS === 'android' ? RNStatusBar.currentHeight : 0,
  },
  container: { flex: 1, backgroundColor: '#fff' },
  scrollContent: { paddingBottom: 120 },
  row: {
    justifyContent: 'space-between',
    paddingHorizontal: 10,
  },
  reviewsSection: {
    marginTop: 25,
    backgroundColor: '#f9fbf9',
    paddingVertical: 20,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: '#e8f0e8',
  },
  reviewsSubtitle: {
    fontSize: 14,
    color: '#666',
    paddingHorizontal: 15,
    marginBottom: 15,
    marginTop: -5,
  },
  reviewsScrollContent: { paddingHorizontal: 15, paddingBottom: 10 },
  reviewCard: {
    width: 280,
    backgroundColor: '#fff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginRight: 15,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 2,
  },
  reviewMediaContainer: {
    width: 278,
    height: 160,
  },
  reviewMedia: { 
    width: 278, 
    height: 160, 
    backgroundColor: '#f5f5f5' 
  },
  reviewBody: { padding: 15 },
  starsContainer: { flexDirection: 'row', alignItems: 'center', marginBottom: 8 },
  reviewText: {
    fontSize: 14,
    color: '#4a5568',
    lineHeight: 20,
    marginBottom: 15,
    fontStyle: 'italic',
  },
  reviewAuthor: { flexDirection: 'row', alignItems: 'center', marginTop: 'auto' as any },
  reviewAvatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: COLORS.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 10,
  },
  reviewAvatarText: { color: '#fff', fontSize: 13, fontWeight: 'bold' },
  reviewerName: {
    fontSize: 13,
    fontWeight: '600',
    color: COLORS.primaryDark || '#0d2b18',
  },
});

export default HomeScreen;
