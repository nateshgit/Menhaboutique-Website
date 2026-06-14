import React, { useEffect, useState } from 'react';
import {
  View, Text, StyleSheet, FlatList, TouchableOpacity,
  Image, SafeAreaView, Platform, StatusBar as RNStatusBar, Dimensions,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { MainAPI } from '../services/api';
import { resolveImageUrl, prefetchImages } from '../utils/imageUtils';
import { COLORS } from '../constants/theme';
import Loader from '../components/Loader';
import CachedImage from '../components/CachedImage';

const { width } = Dimensions.get('window');
const NUM_COLS = 2;
const CARD_MARGIN = 8;
const CARD_SIZE = (width - CARD_MARGIN * (NUM_COLS + 1)) / NUM_COLS;

interface Category {
  id: string;
  name: string;
  image?: string;
}

const CategoriesScreen = () => {
  const navigation = useNavigation<any>();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const data = await MainAPI.fetchCategories();
      setCategories(data);
      // Prefetch all category images immediately
      const urls = data.map((c: any) => resolveImageUrl(c.image, 400)).filter(Boolean);
      prefetchImages(urls);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handleCategoryPress = (item: Category) => {
    navigation.navigate('CategoryProducts', { categoryId: item.id, categoryName: item.name });
  };

  const renderItem = ({ item }: { item: Category }) => (
    <TouchableOpacity
      style={styles.card}
      onPress={() => handleCategoryPress(item)}
      activeOpacity={0.8}
    >
      <CachedImage
        source={{ uri: resolveImageUrl(item.image, 400) }}
        style={styles.image}
        containerStyle={styles.imageContainer}
        resizeMode="cover"
      />
      <View style={styles.textContainer}>
        <Text style={styles.name} numberOfLines={2}>
          {item.name}
        </Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return <Loader fullScreen />;
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>All Categories</Text>
      </View>
      <FlatList
        data={categories}
        renderItem={renderItem}
        numColumns={NUM_COLS}
        contentContainerStyle={styles.listContent}
        keyExtractor={(item) => item.id.toString()}
        initialNumToRender={8}
        maxToRenderPerBatch={6}
        windowSize={5}
        removeClippedSubviews={true}
      />
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
    paddingVertical: 15,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
    backgroundColor: '#fff',
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#333',
  },
  listContent: {
    padding: CARD_MARGIN,
    paddingBottom: 120,
  },
  card: {
    flex: 1,
    margin: CARD_MARGIN,
    backgroundColor: '#fff',
    borderRadius: 12,
    overflow: 'hidden',
    elevation: 3,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    borderWidth: 1,
    borderColor: '#f5f5f5',
  },
  imageContainer: {
    width: '100%',
    height: CARD_SIZE * 0.75,
    backgroundColor: '#ebebeb',
  },
  image: {
    width: '100%',
    height: CARD_SIZE * 0.75,
  },
  textContainer: {
    padding: 12,
    alignItems: 'center',
  },
  name: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    textAlign: 'center',
  },
});

export default CategoriesScreen;
