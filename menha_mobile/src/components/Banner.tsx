import React, { useState, useEffect, useRef } from 'react';
import { View, Dimensions, FlatList, StyleSheet, TouchableOpacity, Text, Image } from 'react-native';
import CachedImage from './CachedImage';
import { resolveImageUrl, prefetchImages } from '../utils/imageUtils';
import { COLORS } from '../constants/theme';

const { width } = Dimensions.get('window');
const BANNER_HEIGHT = 200;

interface BannerItem {
  id?: any;
  image_url?: string;
  imageUrl?: string;
  image?: string;
  images?: { url: string }[];
  discount?: string | number;
}

interface BannerProps {
  data: BannerItem[];
  onPress: (item: BannerItem) => void;
}

const Banner: React.FC<BannerProps> = ({ data, onPress }) => {
  const [currentIndex, setCurrentIndex] = useState(0);
  const flatListRef = useRef<FlatList>(null);
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null);

  // ─── Prefetch ALL banner images the moment data arrives ────────────────────
  useEffect(() => {
    if (!data || data.length === 0) return;
    const urls = data.map((item) => {
      const raw = item.image_url || item.imageUrl || item.image || item.images?.[0]?.url;
      return resolveImageUrl(raw, width); // full screen width
    });
    prefetchImages(urls, data.length); // all at once (banners are usually ≤5)
  }, [data]);

  // ─── Auto-scroll timer ─────────────────────────────────────────────────────
  const startTimer = () => {
    if (timerRef.current) clearInterval(timerRef.current);
    if (data.length <= 1) return;
    timerRef.current = setInterval(() => {
      setCurrentIndex((prev) => {
        const next = (prev + 1) % data.length;
        try {
          flatListRef.current?.scrollToOffset({ offset: next * width, animated: true });
        } catch (_) {}
        return next;
      });
    }, 4000);
  };

  useEffect(() => {
    startTimer();
    return () => { if (timerRef.current) clearInterval(timerRef.current); };
  }, [data.length]);

  const resolveUrl = (item: BannerItem) => {
    const raw = item.image_url || item.imageUrl || item.image || item.images?.[0]?.url;
    return resolveImageUrl(raw, width);
  };

  const renderItem = ({ item }: { item: BannerItem }) => {
    const imageUrl = resolveUrl(item);
    return (
      <TouchableOpacity activeOpacity={0.9} onPress={() => onPress(item)}>
        <View style={styles.cardContainer}>
          <CachedImage
            source={{ uri: imageUrl }}
            style={styles.image}
            containerStyle={styles.imageContainer}
            resizeMode="cover"
          />
          {Boolean(item.discount) && item.discount !== '' && (
            <View style={styles.overlay}>
              <Text style={styles.discount}>{item.discount}% OFF</Text>
            </View>
          )}
        </View>
      </TouchableOpacity>
    );
  };

  if (!data || data.length === 0) return null;

  return (
    <View style={styles.container}>
      <FlatList
        ref={flatListRef}
        data={data}
        renderItem={renderItem}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        keyExtractor={(item, index) => (item.id ? item.id.toString() : `banner-${index}`)}
        getItemLayout={(_, index) => ({ length: width, offset: width * index, index })}
        onMomentumScrollEnd={(event) => {
          const index = Math.round(event.nativeEvent.contentOffset.x / width);
          if (index !== currentIndex) {
            setCurrentIndex(index);
            startTimer();
          }
        }}
        scrollEventThrottle={16}
        bounces={false}
        decelerationRate="fast"
        removeClippedSubviews={false}
        initialNumToRender={data.length}
        maxToRenderPerBatch={data.length}
        windowSize={data.length + 1}
      />
      {data.length > 1 && (
        <View style={styles.pagination}>
          {data.map((_, index) => (
            <View
              key={index}
              style={[
                styles.dot,
                {
                  backgroundColor: index === currentIndex ? COLORS.primary : 'rgba(0,0,0,0.2)',
                  width: index === currentIndex ? 20 : 8,
                },
              ]}
            />
          ))}
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    marginBottom: 20,
    backgroundColor: '#ebebeb',
  },
  cardContainer: {
    width,
    height: BANNER_HEIGHT,
    position: 'relative',
    backgroundColor: '#ebebeb',
    overflow: 'hidden',
  },
  imageContainer: {
    width,
    height: BANNER_HEIGHT,
  },
  image: {
    width,
    height: BANNER_HEIGHT,
  },
  overlay: {
    position: 'absolute',
    bottom: 12,
    right: 12,
  },
  discount: {
    backgroundColor: '#E53935',
    color: '#fff',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
    fontSize: 12,
    fontWeight: 'bold',
  },
  pagination: {
    position: 'absolute',
    bottom: 10,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  dot: {
    height: 8,
    borderRadius: 4,
    marginHorizontal: 3,
  },
});

export default Banner;
