/**
 * CachedImage
 * A drop-in replacement for React Native's <Image> that adds:
 *  • Animated shimmer skeleton while the image is fetching
 *  • Smooth fade-in once the image has loaded
 *  • Graceful error fallback
 *
 * Usage:
 *   <CachedImage source={{ uri: url }} style={styles.image} resizeMode="cover" />
 *
 * IMPORTANT: The parent View must have explicit width/height for the shimmer
 * and fade-in to render correctly.
 */
import React, { useState, useRef, useCallback } from 'react';
import {
  Image,
  View,
  Animated,
  StyleSheet,
  ImageProps,
  ImageStyle,
  StyleProp,
  ViewStyle,
  LayoutChangeEvent,
} from 'react-native';

interface CachedImageProps extends Omit<ImageProps, 'style'> {
  style?: StyleProp<ImageStyle>;
  containerStyle?: StyleProp<ViewStyle>;
  shimmerColor?: string;
  shimmerHighlight?: string;
}

const CachedImage: React.FC<CachedImageProps> = ({
  source,
  style,
  containerStyle,
  shimmerColor = '#ebebeb',
  shimmerHighlight = '#f5f5f5',
  resizeMode = 'cover',
  ...rest
}) => {
  const [loaded, setLoaded] = useState(false);
  const [hasError, setHasError] = useState(false);
  const [width, setWidth] = useState(0);

  // Fade-in opacity for the image
  const opacity = useRef(new Animated.Value(0)).current;

  // Shimmer translateX — pixel-based so useNativeDriver works on Android
  const shimmerAnim = useRef(new Animated.Value(0)).current;
  const shimmerLoopRef = useRef<Animated.CompositeAnimation | null>(null);

  /** Called once we know the container width, so we can animate across it */
  const startShimmer = useCallback(
    (containerW: number) => {
      if (containerW <= 0) return;
      shimmerAnim.setValue(-containerW);
      shimmerLoopRef.current = Animated.loop(
        Animated.timing(shimmerAnim, {
          toValue: containerW * 1.5,
          duration: 1000,
          useNativeDriver: true,
        }),
      );
      shimmerLoopRef.current.start();
    },
    [shimmerAnim],
  );

  const onLayout = useCallback(
    (e: LayoutChangeEvent) => {
      const w = e.nativeEvent.layout.width;
      if (w > 0 && w !== width) {
        setWidth(w);
        if (!loaded) startShimmer(w);
      }
    },
    [width, loaded, startShimmer],
  );

  const stopShimmerAndFadeIn = useCallback(() => {
    shimmerLoopRef.current?.stop();
    setLoaded(true);
    Animated.timing(opacity, {
      toValue: 1,
      duration: 200,
      useNativeDriver: true,
    }).start();
  }, [opacity]);

  const onLoad = useCallback(() => {
    stopShimmerAndFadeIn();
  }, [stopShimmerAndFadeIn]);

  const onError = useCallback(() => {
    shimmerLoopRef.current?.stop();
    setHasError(true);
    setLoaded(true);
    opacity.setValue(1);
  }, [opacity]);

  return (
    <View
      style={[styles.wrapper, containerStyle as any]}
      onLayout={onLayout}
    >
      {/* Shimmer — only shown while the image is loading */}
      {!loaded && width > 0 && (
        <View
          style={[StyleSheet.absoluteFill, { backgroundColor: shimmerColor, overflow: 'hidden' }]}
          pointerEvents="none"
        >
          <Animated.View
            style={{
              position: 'absolute',
              top: 0,
              bottom: 0,
              width: width * 0.55,
              backgroundColor: shimmerHighlight,
              opacity: 0.8,
              transform: [{ translateX: shimmerAnim }],
            }}
          />
        </View>
      )}

      {/* Error state */}
      {hasError && (
        <View style={[StyleSheet.absoluteFill, styles.errorBox]} />
      )}

      {/* The actual image — fades in on load */}
      {!hasError && (
        <Animated.Image
          source={source}
          style={[style, { opacity }]}
          resizeMode={resizeMode}
          onLoad={onLoad}
          onError={onError}
          {...rest}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  wrapper: {
    overflow: 'hidden',
    backgroundColor: '#ebebeb',
  },
  errorBox: {
    backgroundColor: '#e0e0e0',
  },
});

export default CachedImage;
