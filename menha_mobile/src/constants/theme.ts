export const COLORS = {
  primary: '#1a472a', // Deep Green from Logo/Brand
  primaryLight: '#2e7d32', // Lighter green for gradients
  primaryDark: '#0d2b18',
  
  accent: '#e91e63', // Pink/Red from the dress/bow in logo
  accentLight: '#ff4081',
  
  white: '#ffffff',
  black: '#000000',
  gray: '#999999',
  lightGray: '#f5f5f5',
  border: '#f0f0f0',
  
  danger: '#d32f2f',
  success: '#1e8e3e',
  warning: '#f59e0b',
  info: '#007AFF',
  
  text: '#333333',
  textLight: '#888888',
};

export const THEME = {
  colors: COLORS,
  gradients: {
    primary: [COLORS.primary, COLORS.primaryLight],
    accent: [COLORS.accent, COLORS.accentLight],
  },
};
