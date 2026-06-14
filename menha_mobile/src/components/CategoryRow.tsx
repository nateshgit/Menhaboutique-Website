import React, { useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet } from 'react-native';
import CachedImage from './CachedImage';
import { resolveImageUrl, prefetchImages } from '../utils/imageUtils';

interface Category {
  id: string;
  name: string;
  image?: string;
}

interface CategoryRowProps {
  data: Category[];
  onPress: (category: Category) => void;
}

const CategoryRow: React.FC<CategoryRowProps> = ({ data, onPress }) => {
  // Prefetch all category circle images immediately on mount
  useEffect(() => {
    if (!data || data.length === 0) return;
    const urls = data.map((c) => resolveImageUrl(c.image, 150)).filter(Boolean);
    prefetchImages(urls);
  }, [data]);

  if (!data || data.length === 0) return null;

  const renderItem = ({ item }: { item: Category }) => (
    <TouchableOpacity style={styles.item} onPress={() => onPress(item)} activeOpacity={0.8}>
      <View style={styles.imageContainer}>
        <CachedImage
          source={{ uri: resolveImageUrl(item.image, 150) }}
          style={styles.image}
          containerStyle={styles.imageContainer}
          resizeMode="cover"
        />
      </View>
      <Text style={styles.name} numberOfLines={1}>
        {item.name}
      </Text>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={data}
        renderItem={renderItem}
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.listContent}
        keyExtractor={(item) => item.id.toString()}
        initialNumToRender={8}
        maxToRenderPerBatch={8}
        removeClippedSubviews={false}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    paddingVertical: 15,
    backgroundColor: '#fff',
  },
  listContent: {
    paddingHorizontal: 10,
  },
  item: {
    alignItems: 'center',
    marginHorizontal: 10,
    width: 80,
  },
  imageContainer: {
    width: 70,
    height: 70,
    borderRadius: 35,
    borderWidth: 2,
    borderColor: '#eee',
    overflow: 'hidden',
    marginBottom: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  image: {
    width: 70,
    height: 70,
  },
  name: {
    fontSize: 12,
    color: '#3d4750',
    fontWeight: '600',
    textAlign: 'center',
  },
});

export default CategoryRow;
