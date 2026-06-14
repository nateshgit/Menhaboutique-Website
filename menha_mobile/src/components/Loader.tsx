import React, { useEffect, useRef } from 'react';
import { View, StyleSheet, Animated, Easing } from 'react-native';
import { COLORS } from '../constants/theme';

interface LoaderProps {
  size?: 'small' | 'large';
  fullScreen?: boolean;
}

export const Loader: React.FC<LoaderProps> = ({ size = 'large', fullScreen = false }) => {
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Breathing/pulsing animation
    const pulseLoop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, {
          toValue: 1.12,
          duration: 900,
          easing: Easing.bezier(0.4, 0, 0.2, 1),
          useNativeDriver: true,
        }),
        Animated.timing(pulseAnim, {
          toValue: 0.92,
          duration: 900,
          easing: Easing.bezier(0.4, 0, 0.2, 1),
          useNativeDriver: true,
        }),
      ])
    );
    pulseLoop.start();

    // Rotating ring animation
    const rotateLoop = Animated.loop(
      Animated.timing(rotateAnim, {
        toValue: 1,
        duration: 1800,
        easing: Easing.linear,
        useNativeDriver: true,
      })
    );
    rotateLoop.start();

    return () => {
      pulseLoop.stop();
      rotateLoop.stop();
    };
  }, [pulseAnim, rotateAnim]);

  const rotation = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  if (size === 'small') {
    return (
      <View style={styles.smallContainer}>
        <Animated.Image
          source={require('../../assets/logo.png')}
          style={[
            styles.smallLogo,
            {
              transform: [{ scale: pulseAnim }],
            },
          ]}
          resizeMode="contain"
        />
      </View>
    );
  }

  const containerStyle = fullScreen ? styles.fullScreenContainer : styles.container;

  return (
    <View style={containerStyle}>
      <View style={styles.loaderWrapper}>
        {/* Glowing/pulsing background ring */}
        <Animated.View
          style={[
            styles.ring,
            {
              transform: [{ rotate: rotation }],
            },
          ]}
        />
        {/* Pulsing Logo in the center */}
        <Animated.Image
          source={require('../../assets/logo.png')}
          style={[
            styles.logo,
            {
              transform: [{ scale: pulseAnim }],
            },
          ]}
          resizeMode="contain"
        />
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  fullScreenContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#ffffff',
  },
  loaderWrapper: {
    width: 100,
    height: 100,
    justifyContent: 'center',
    alignItems: 'center',
    position: 'relative',
  },
  ring: {
    position: 'absolute',
    width: 84,
    height: 84,
    borderRadius: 42,
    borderWidth: 3,
    borderColor: 'transparent',
    borderTopColor: COLORS.primary,
    borderRightColor: COLORS.accent,
    borderBottomColor: 'rgba(26, 71, 42, 0.2)', // COLORS.primary with transparency
    borderLeftColor: 'rgba(233, 30, 99, 0.2)', // COLORS.accent with transparency
  },
  logo: {
    width: 58,
    height: 58,
    borderRadius: 29,
    backgroundColor: '#ffffff',
  },
  smallContainer: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  smallLogo: {
    width: 24,
    height: 24,
    borderRadius: 12,
  },
});

export default Loader;
