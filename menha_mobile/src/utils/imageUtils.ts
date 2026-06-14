import { Image } from 'react-native';

/**
 * Resolves a raw image path/URL to a fully-qualified URL with optional resize.
 * Appends ?w=<width>&q=80 so the server/CDN returns a smaller image.
 */
export const resolveImageUrl = (
  path: string | null | undefined,
  width?: number,
): string => {
  if (!path) return '';

  if (path.startsWith('data:')) return path;

  let baseUrl = '';

  if (path.startsWith('http')) {
    if (path.includes('menhaboutique.com')) {
      baseUrl = path;
    } else {
      // External URL – return as-is, no resize params
      return path;
    }
  } else {
    const cleanPath = path.replace(/^\//, '');
    baseUrl = `https://menhaboutique.com/${cleanPath}`;
  }

  if (width && width > 0) {
    const sep = baseUrl.includes('?') ? '&' : '?';
    return `${baseUrl}${sep}w=${width}&q=80&fit=cover`;
  }

  return baseUrl;
};

/**
 * Bulk-prefetch an array of URLs into the native image cache.
 * Runs all fetches in parallel; errors are silently swallowed.
 * Call this the moment API data arrives so images are cached by render time.
 *
 * @param urls   Array of fully-resolved image URLs
 * @param limit  Max concurrent fetches (default 10)
 */
export const prefetchImages = (urls: string[], limit = 10): void => {
  const validUrls = urls.filter((u) => u && u.startsWith('http'));
  // Fire in batches to avoid overwhelming the network stack
  for (let i = 0; i < validUrls.length; i += limit) {
    const batch = validUrls.slice(i, i + limit);
    batch.forEach((url) => Image.prefetch(url).catch(() => {}));
  }
};

/**
 * Build a list of resolved image URLs from a product array, ready to prefetch.
 */
export const getProductImageUrls = (products: any[], width = 300): string[] =>
  products
    .map((p) => {
      const raw =
        p.primary_image ||
        p.primaryImage ||
        (p.images && p.images.length > 0
          ? p.images[0].image_url || p.images[0].imageUrl || p.images[0].url
          : null) ||
        p.image;
      return resolveImageUrl(raw, width);
    })
    .filter(Boolean);
