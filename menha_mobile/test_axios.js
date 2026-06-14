const axios = require('axios');

const BACKEND_URL = 'https://menhaboutique.com/api';

const api = axios.create({
  baseURL: `${BACKEND_URL}/supabase_shim.php`,
  headers: {
    'Content-Type': 'application/json',
  },
});

async function run() {
  try {
    // 1. Fetch Banners
    console.log('Fetching Banners...');
    const bannersRes = await api.get('/banners?select=*&is_active=eq.true&order=sequence.asc');
    console.log('Banners Status:', bannersRes.status);
    console.log('Banners Is Array?', Array.isArray(bannersRes.data));
    console.log('Banners Length:', bannersRes.data.length);
    if (bannersRes.data.length > 0) {
      console.log('First Banner Keys:', Object.keys(bannersRes.data[0]));
    }

    // 2. Fetch Categories
    console.log('\nFetching Categories...');
    const catsRes = await api.get('/categories?select=*&order=sequence.asc');
    console.log('Categories Status:', catsRes.status);
    console.log('Categories Is Array?', Array.isArray(catsRes.data));
    console.log('Categories Length:', catsRes.data.length);
    if (catsRes.data.length > 0) {
      console.log('First Category Keys:', Object.keys(catsRes.data[0]));
    }

    // 3. Fetch Products
    console.log('\nFetching Products...');
    const prodsRes = await api.get('/products?select=*,product_attributes(*),categories(name)&order=sequence.asc');
    console.log('Products Status:', prodsRes.status);
    console.log('Products Is Array?', Array.isArray(prodsRes.data));
    console.log('Products Length:', prodsRes.data.length);
    if (prodsRes.data.length > 0) {
      console.log('First Product Keys:', Object.keys(prodsRes.data[0]));
    }

  } catch (error) {
    console.error('Error fetching:', error.message);
    if (error.response) {
      console.error('Response Status:', error.response.status);
      console.error('Response Data:', error.response.data);
    }
  }
}

run();

