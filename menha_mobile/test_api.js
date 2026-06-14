const { MainAPI } = require('./src/services/api');

async function run() {
  const products = await MainAPI.fetchProducts();
  console.log(products.slice(0, 2));
}

run();
