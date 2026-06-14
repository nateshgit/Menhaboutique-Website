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
    const url = '/products?select=*,product_attributes(*),categories(name)&order=sequence.asc';
    console.log('Fetching from:', api.defaults.baseURL + url);
    const res = await api.get(url);
    console.log('Status:', res.status);
    console.log('Is Array?', Array.isArray(res.data));
    console.log('Length:', res.data.length);
    console.log('First Item Name/Title:', res.data[0] ? (res.data[0].name || res.data[0].title) : 'none');
  } catch (error) {
    console.error('Error fetching:', error.message);
    if (error.response) {
      console.error('Response Status:', error.response.status);
      console.error('Response Data:', error.response.data);
    }
  }
}

run();
