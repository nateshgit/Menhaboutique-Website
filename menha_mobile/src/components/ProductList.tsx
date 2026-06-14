import React, { useCallback, useRef } from 'react';
import {
  View, FlatList, StyleSheet, Dimensions, Platform
} from 'react-native';
import ProductCard from './ProductCard';
import { resolveImageUrl, prefetchImages } from '../utils/imageUtils';

const { width } = Dimensions.get('window');
const CARD_MARGIN = 10;
const CONTAINER_PADDING = 10;
const COLUMN_WIDTH = (width - CONTAINER_PADDING * 2 - CARD_MARGIN) / 2;

interface ProductListProps {
  data: any[];
  onPress: (item: any) => void;
  scrollEnabled?: boolean;
}

const ProductList: React.FC<ProductListProps> = ({ data, onPress, scrollEnabled = false }) => {
  const prefetchedIndices = useRef<Set<number>>(new Set());

  // Prefetch images for upcoming rows as user scrolls
  const onViewableItemsChanged = useCallback(
    ({ viewableItems }: { viewableItems: any[] }) => {
      if (viewableItems.length === 0) return;
      // Get the highest visible index and prefetch the next 8 items ahead
      const maxVisible = Math.max(...viewableItems.map((v) => v.index ?? 0));
      const prefetchStart = maxVisible + 1;
      const prefetchEnd = Math.min(prefetchStart + 8, data.length);
      const toPrefetch: string[] = [];
      for (let i = prefetchStart; i < prefetchEnd; i++) {
        if (!prefetchedIndices.current.has(i)) {
          prefetchedIndices.current.add(i);
          const p = data[i];
          if (p) {
            const raw =
              p.primary_image ||
              p.primaryImage ||
              (p.images?.length > 0
                ? p.images[0].image_url || p.images[0].imageUrl || p.images[0].url
                : null) ||
              p.image;
            const url = resolveImageUrl(raw, 300);
            if (url) toPrefetch.push(url);
          }
        }
      }
      if (toPrefetch.length > 0) prefetchImages(toPrefetch);
    },
    [data],
  );

  const viewabilityConfig = useRef({ itemVisiblePercentThreshold: 30 }).current;

  // Render simple flex grid when not scrollable (to avoid nesting FlatList inside ScrollView)
  if (!scrollEnabled) {
    return (
      <View style={styles.container}>
        <View style={styles.gridContainer}>
          {data.map((item, idx) => (
            <ProductCard 
              key={item.id ? item.id.toString() : `prod-${idx}`} 
              item={item} 
              onPress={onPress} 
            />
          ))}
        </View>
      </View>
    );
  }

  // Render virtualized scrollable FlatList when scrollEnabled is true
  return (
    <View style={[styles.container, { flex: 1 }]}>
      <FlatList
        data={data}
        renderItem={({ item }) => <ProductCard item={item} onPress={onPress} />}
        keyExtractor={(item, index) => (item.id || index).toString()}
        numColumns={2}
        columnWrapperStyle={styles.row}
        contentContainerStyle={{ paddingBottom: 120 }}
        initialNumToRender={6}
        maxToRenderPerBatch={4}
        updateCellsBatchingPeriod={40}
        windowSize={7}
        removeClippedSubviews={Platform.OS === 'android'}
        onViewableItemsChanged={onViewableItemsChanged}
        viewabilityConfig={viewabilityConfig}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: CONTAINER_PADDING,
    marginTop: 10,
  },
  row: {
    justifyContent: 'space-between',
  },
  gridContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
});

export default ProductList;
